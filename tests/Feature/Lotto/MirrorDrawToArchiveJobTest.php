<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Jobs\MirrorDrawToArchiveJob;
use Gametech\Lotto\Models\LottoResultArchive;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class MirrorDrawToArchiveJobTest extends TestCase
{
    private int $drawId;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('lotto_result_archive_logs');
        Schema::dropIfExists('lotto_result_archives');
        Schema::dropIfExists('lotto_draw_bet_settings');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');

        Schema::create('lotto_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_enabled')->default(true);
        });

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained('lotto_groups')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_enabled')->default(true);
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date');
            $table->dateTime('open_at')->nullable();
            $table->dateTime('close_at')->nullable();
            $table->string('status')->default('draft');
            $table->json('result_number')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_draw_bet_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('draw_id');
            $table->string('bet_type');
            $table->unique(['draw_id', 'bet_type']);
        });

        Schema::create('lotto_result_archives', function (Blueprint $table): void {
            $table->id();
            $table->string('market_code', 50);
            $table->date('draw_date');
            $table->string('draw_key', 50);
            $table->json('result_set');
            $table->string('result_hash', 64);
            $table->unsignedBigInteger('source_draw_id')->nullable();
            $table->string('source_type', 30)->default('internal_mirror');
            $table->unsignedInteger('correction_count')->default(0);
            $table->json('previous_result_set')->nullable();
            $table->json('source_info_json')->nullable();
            $table->dateTime('corrected_at')->nullable();
            $table->timestamps();
            $table->unique(['market_code', 'draw_date', 'draw_key']);
        });

        Schema::create('lotto_result_archive_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('archive_id')->nullable();
            $table->string('market_code', 50);
            $table->date('draw_date');
            $table->string('draw_key', 50);
            $table->string('action', 30);
            $table->string('run_id', 64);
            $table->string('status', 20);
            $table->json('old_result_set')->nullable();
            $table->json('new_result_set')->nullable();
            $table->json('changed_keys')->nullable();
            $table->json('source_info_json')->nullable();
            $table->text('error_message')->nullable();
            $table->json('trace_json')->nullable();
            $table->dateTime('created_at');
        });

        DB::table('lotto_groups')->insert(['id' => 1, 'name' => 'Test', 'code' => 'test-group']);
        DB::table('lotto_markets')->insert(['id' => 1, 'group_id' => 1, 'name' => 'Test Market', 'code' => 'test-market']);

        $this->drawId = DB::table('lotto_draws')->insertGetId([
            'market_id' => 1,
            'draw_date' => '2026-05-12',
            'status' => 'resulted',
            'result_number' => json_encode(['top_3' => '832', 'bottom_2' => '47']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_draw_bet_settings')->insert([
            ['draw_id' => $this->drawId, 'bet_type' => 'top_3'],
            ['draw_id' => $this->drawId, 'bet_type' => 'bottom_2'],
        ]);
    }

    public function test_job_mirrors_resulted_draw(): void
    {
        $job = new MirrorDrawToArchiveJob($this->drawId, (string) Str::uuid());
        $job->handle(app(\Gametech\Lotto\Services\ArchiveNormalizerService::class), app(\Gametech\Lotto\Services\ArchiveWriterService::class));

        $count = LottoResultArchive::where('source_draw_id', $this->drawId)->count();
        $this->assertGreaterThan(0, $count);
    }

    public function test_job_skips_non_resulted_draw(): void
    {
        DB::table('lotto_draws')->where('id', $this->drawId)->update(['status' => 'closed']);

        $job = new MirrorDrawToArchiveJob($this->drawId, (string) Str::uuid());
        $job->handle(app(\Gametech\Lotto\Services\ArchiveNormalizerService::class), app(\Gametech\Lotto\Services\ArchiveWriterService::class));

        $count = LottoResultArchive::where('source_draw_id', $this->drawId)->count();
        $this->assertSame(0, $count);
    }

    public function test_job_is_idempotent_on_retry(): void
    {
        $runId1 = (string) Str::uuid();
        $job1 = new MirrorDrawToArchiveJob($this->drawId, $runId1);
        $job1->handle(app(\Gametech\Lotto\Services\ArchiveNormalizerService::class), app(\Gametech\Lotto\Services\ArchiveWriterService::class));

        $count1 = LottoResultArchive::where('source_draw_id', $this->drawId)->count();

        $job2 = new MirrorDrawToArchiveJob($this->drawId, (string) Str::uuid());
        $job2->handle(app(\Gametech\Lotto\Services\ArchiveNormalizerService::class), app(\Gametech\Lotto\Services\ArchiveWriterService::class));

        $count2 = LottoResultArchive::where('source_draw_id', $this->drawId)->count();

        $this->assertSame($count1, $count2);

        $archive = LottoResultArchive::where('source_draw_id', $this->drawId)->first();
        $this->assertSame(0, $archive->correction_count);
    }
}
