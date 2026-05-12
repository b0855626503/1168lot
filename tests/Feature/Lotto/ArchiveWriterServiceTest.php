<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Models\LottoResultArchive;
use Gametech\Lotto\Repositories\ArchiveLogRepository;
use Gametech\Lotto\Repositories\ArchiveRepository;
use Gametech\Lotto\Services\ArchiveWriterService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class ArchiveWriterServiceTest extends TestCase
{
    private ArchiveWriterService $writer;

    private int $drawId;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('lotto_result_archive_logs');
        Schema::dropIfExists('lotto_result_archives');
        Schema::dropIfExists('lotto_draws');

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date');
            $table->string('status')->default('draft');
            $table->json('result_number')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_result_archives', function (Blueprint $table): void {
            $table->id();
            $table->string('market_code', 50);
            $table->date('draw_date');
            $table->string('draw_key', 50);
            $table->json('result_set');
            $table->string('result_hash', 64);
            $table->unsignedBigInteger('source_draw_id')->nullable();
            $table->foreign('source_draw_id')->references('id')->on('lotto_draws')->onDelete('set null');
            $table->string('source_type', 30)->default('internal_mirror');
            $table->unsignedInteger('correction_count')->default(0);
            $table->json('previous_result_set')->nullable();
            $table->json('source_info_json')->nullable();
            $table->dateTime('corrected_at')->nullable();
            $table->timestamps();

            $table->unique(['market_code', 'draw_date', 'draw_key']);
            $table->index(['market_code', 'draw_date']);
        });

        Schema::create('lotto_result_archive_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('archive_id')->nullable();
            $table->foreign('archive_id')->references('id')->on('lotto_result_archives')->onDelete('set null');
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

        $this->drawId = \DB::table('lotto_draws')->insertGetId([
            'market_id' => 1,
            'draw_date' => '2026-05-12',
            'status' => 'resulted',
            'result_number' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->writer = new ArchiveWriterService(
            new ArchiveRepository,
            new ArchiveLogRepository,
        );
    }

    public function test_write_archive_creates_new_row(): void
    {
        $runId = (string) Str::uuid();
        $rows = [
            [
                'market_code' => 'test-market',
                'draw_date' => '2026-05-12',
                'draw_key' => 'three_up',
                'result_set' => ['832'],
                'result_hash' => hash('sha256', '832'),
            ],
        ];

        $result = $this->writer->writeArchive($rows, 'internal_mirror', $this->drawId, $runId);

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(0, $result['corrected']);
        $this->assertSame(1, $result['logs']);

        $archive = LottoResultArchive::where('market_code', 'test-market')
            ->whereDate('draw_date', '2026-05-12')
            ->where('draw_key', 'three_up')
            ->first();

        $this->assertNotNull($archive);
        $this->assertSame(['832'], $archive->result_set);
        $this->assertSame(0, $archive->correction_count);
    }

    public function test_same_hash_retry_skips_without_incrementing_correction_count(): void
    {
        $rows = [
            [
                'market_code' => 'test-market',
                'draw_date' => '2026-05-12',
                'draw_key' => 'two_down',
                'result_set' => ['47'],
                'result_hash' => hash('sha256', '47'),
            ],
        ];

        $this->writer->writeArchive($rows, 'internal_mirror', $this->drawId, (string) Str::uuid());
        $result = $this->writer->writeArchive($rows, 'internal_mirror', $this->drawId, (string) Str::uuid());

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['skipped']);

        $archive = LottoResultArchive::where('market_code', 'test-market')
            ->whereDate('draw_date', '2026-05-12')
            ->where('draw_key', 'two_down')
            ->first();

        $this->assertSame(0, $archive->correction_count);
    }

    public function test_different_hash_triggers_correction(): void
    {
        $rows1 = [
            [
                'market_code' => 'test-market',
                'draw_date' => '2026-05-12',
                'draw_key' => 'three_front',
                'result_set' => ['290'],
                'result_hash' => hash('sha256', '290'),
            ],
        ];

        $this->writer->writeArchive($rows1, 'internal_mirror', $this->drawId, (string) Str::uuid());

        $rows2 = [
            [
                'market_code' => 'test-market',
                'draw_date' => '2026-05-12',
                'draw_key' => 'three_front',
                'result_set' => ['789', '012'],
                'result_hash' => hash('sha256', '012|789'),
            ],
        ];

        $result = $this->writer->writeArchive($rows2, 'internal_mirror', $this->drawId, (string) Str::uuid());

        $this->assertSame(1, $result['corrected']);

        $archive = LottoResultArchive::where('market_code', 'test-market')
            ->whereDate('draw_date', '2026-05-12')
            ->where('draw_key', 'three_front')
            ->first();

        $this->assertSame(1, $archive->correction_count);
        $this->assertSame(['789', '012'], $archive->result_set);
        $this->assertSame(['290'], $archive->previous_result_set);
        $this->assertNotNull($archive->corrected_at);
    }

    public function test_external_fetch_rejected_if_source_info_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('source_info_json is REQUIRED');

        $rows = [
            [
                'market_code' => 'test-market',
                'draw_date' => '2026-05-12',
                'draw_key' => 'three_up',
                'result_set' => ['123'],
                'result_hash' => hash('sha256', '123'),
            ],
        ];

        $this->writer->writeArchive($rows, 'external_fetch', null, (string) Str::uuid());
    }

    public function test_external_fetch_source_info_missing_field_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('source_info_json.source_url');

        $rows = [
            [
                'market_code' => 'test-market',
                'draw_date' => '2026-05-12',
                'draw_key' => 'three_up',
                'result_set' => ['123'],
                'result_hash' => hash('sha256', '123'),
                'source_info_json' => ['fetched_at' => '2026-05-12', 'parser_version' => '1.0'],
            ],
        ];

        $this->writer->writeArchive($rows, 'external_fetch', null, (string) Str::uuid());
    }

    public function test_external_does_not_overwrite_internal_by_default(): void
    {
        $internalRows = [
            [
                'market_code' => 'test-market',
                'draw_date' => '2026-05-12',
                'draw_key' => 'run_up',
                'result_set' => ['2'],
                'result_hash' => hash('sha256', '2'),
            ],
        ];

        $this->writer->writeArchive($internalRows, 'internal_mirror', $this->drawId, (string) Str::uuid());

        $externalRows = [
            [
                'market_code' => 'test-market',
                'draw_date' => '2026-05-12',
                'draw_key' => 'run_up',
                'result_set' => ['9'],
                'result_hash' => hash('sha256', '9'),
                'source_info_json' => [
                    'source_url' => 'https://example.com',
                    'fetched_at' => '2026-05-12T00:00:00Z',
                    'parser_version' => '1.0',
                ],
            ],
        ];

        $result = $this->writer->writeArchive($externalRows, 'external_fetch', null, (string) Str::uuid());

        $this->assertSame(1, $result['skipped']);

        $archive = LottoResultArchive::where('market_code', 'test-market')
            ->whereDate('draw_date', '2026-05-12')
            ->where('draw_key', 'run_up')
            ->first();

        $this->assertSame(['2'], $archive->result_set);
        $this->assertSame('internal_mirror', $archive->source_type);
    }

    public function test_external_overwrite_allowed_with_flag(): void
    {
        $internalRows = [
            [
                'market_code' => 'test-market',
                'draw_date' => '2026-05-12',
                'draw_key' => 'run_down',
                'result_set' => ['3'],
                'result_hash' => hash('sha256', '3'),
            ],
        ];

        $this->writer->writeArchive($internalRows, 'internal_mirror', $this->drawId, (string) Str::uuid());

        $externalRows = [
            [
                'market_code' => 'test-market',
                'draw_date' => '2026-05-12',
                'draw_key' => 'run_down',
                'result_set' => ['8'],
                'result_hash' => hash('sha256', '8'),
                'source_info_json' => [
                    'source_url' => 'https://example.com',
                    'fetched_at' => '2026-05-12T00:00:00Z',
                    'parser_version' => '1.0',
                ],
            ],
        ];

        $result = $this->writer->writeArchive($externalRows, 'external_fetch', null, (string) Str::uuid(), true);

        $this->assertSame(1, $result['corrected']);

        $archive = LottoResultArchive::where('market_code', 'test-market')
            ->whereDate('draw_date', '2026-05-12')
            ->where('draw_key', 'run_down')
            ->first();

        $this->assertSame(['8'], $archive->result_set);
    }
}
