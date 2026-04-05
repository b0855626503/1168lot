<?php

namespace Tests\Unit\Lotto;

use App\Jobs\SendTelegramBot;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Services\AutoResult\AutoResultPipelineService;
use Gametech\Lotto\Services\AutoResult\ResultApplier;
use Gametech\Lotto\Services\AutoResult\ResultCandidateSelector;
use Gametech\Lotto\Services\AutoResult\ResultFetcher;
use Gametech\Lotto\Services\AutoResult\ResultMapper;
use Gametech\Lotto\Services\AutoResult\ResultParser;
use Gametech\Lotto\Services\AutoResult\ResultRequestBuilder;
use Gametech\Lotto\Services\AutoResult\ResultSourceResolver;
use Gametech\Lotto\Services\AutoResult\ResultValidator;
use Gametech\Lotto\Services\AutoResultHardeningService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AutoResultExhaustedAlertTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        config()->set('lotto_auto_result.hardening.alerts.enabled', true);
        config()->set('lotto_auto_result.hardening.alerts.telegram_endpoint', 'notify/send');
        config()->set('lotto_auto_result.hardening.alerts.telegram_queue', 'broadcasts');
        config()->set('lotto_auto_result.hardening.alerts.dedupe_seconds', 21600);
        RateLimiter::clear('lotto:auto-result:exhausted-alert:draw:626');

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
            $table->dateTime('result_at')->nullable();
            $table->string('status', 32);
            $table->timestamps();
        });

        Schema::create('lotto_result_fetch_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('draw_id')->nullable();
            $table->unsignedBigInteger('market_id')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedInteger('attempt_no')->default(1);
            $table->string('status', 32);
            $table->string('pipeline_stage', 64)->nullable();
            $table->mediumText('error_message')->nullable();
            $table->dateTime('created_at')->nullable();
        });

        DB::table('lotto_markets')->insert([
            'id' => 1,
            'name' => 'ลาวพัฒนา',
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 626,
            'market_id' => 1,
            'draw_date' => '2026-04-05',
            'result_at' => '2026-04-05 20:35:00',
            'status' => 'closed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_result_fetch_logs')->insert([
            'draw_id' => 626,
            'market_id' => 1,
            'source_id' => 60,
            'attempt_no' => 27,
            'status' => 'NOT_READY',
            'pipeline_stage' => 'v2_cutover',
            'error_message' => 'ผลหวยยังไม่ออก',
            'created_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_mark_exhausted_dispatches_telegram_even_without_draw_hardening_columns(): void
    {
        Bus::fake();

        $service = new AutoResultPipelineService(
            Mockery::mock(ResultSourceResolver::class),
            Mockery::mock(ResultRequestBuilder::class),
            Mockery::mock(ResultFetcher::class),
            Mockery::mock(ResultParser::class),
            Mockery::mock(ResultCandidateSelector::class),
            Mockery::mock(ResultMapper::class),
            Mockery::mock(ResultValidator::class),
            Mockery::mock(ResultApplier::class),
            new AutoResultHardeningService()
        );

        $service->markExhausted(LottoDraw::query()->with('market')->findOrFail(626));

        $log = DB::table('lotto_result_fetch_logs')
            ->where('status', 'EXHAUSTED')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('retry_policy', $log->pipeline_stage);

        Bus::assertDispatched(SendTelegramBot::class, function (SendTelegramBot $job): bool {
            return $job->endpoint === 'notify/send'
                && $job->queue === 'broadcasts'
                && str_contains($job->message, 'ลาวพัฒนา')
                && str_contains($job->message, 'attempts=27')
                && str_contains($job->message, 'ผลหวยยังไม่ออก');
        });
    }
}
