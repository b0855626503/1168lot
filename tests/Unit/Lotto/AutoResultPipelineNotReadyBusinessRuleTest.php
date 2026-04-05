<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoResultSource;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AutoResultPipelineNotReadyBusinessRuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

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
            $table->string('result_fetch_status', 32)->nullable();
            $table->unsignedInteger('result_fetch_attempts')->default(0);
            $table->dateTime('result_fetched_at')->nullable();
            $table->text('result_fetch_error')->nullable();
            $table->text('result_source_snapshot_json')->nullable();
            $table->unsignedBigInteger('result_source_id')->nullable();
            $table->string('result_source_version', 64)->nullable();
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
            $table->string('run_id', 64)->nullable();
            $table->string('error_code', 64)->nullable();
            $table->string('error_stage', 64)->nullable();
            $table->longText('trace_json')->nullable();
            $table->mediumText('error_message')->nullable();
            $table->boolean('is_dry_run')->default(false);
            $table->boolean('is_manual_settle')->default(false);
            $table->boolean('is_manual_retry')->default(false);
            $table->dateTime('created_at')->nullable();
        });

        DB::table('lotto_markets')->insert([
            'id' => 1,
            'name' => 'หวยทดสอบ',
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 1,
            'market_id' => 1,
            'draw_date' => '2026-04-05',
            'result_at' => '2026-04-05 20:35:00',
            'status' => 'closed',
            'result_fetch_status' => null,
            'result_fetch_attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_not_ready_business_rule_is_persisted_as_not_ready(): void
    {
        $source = new LottoResultSource([
            'market_id' => 1,
            'is_active' => true,
            'cutover_enabled' => true,
            'pipeline_version' => 'V2_CUTOVER',
            'fetch_strategy' => 'JSON_HTTP',
            'parser_type' => 'JSON_PATH',
            'timeout_seconds' => 10,
        ]);
        $source->id = 7;

        $resolver = Mockery::mock(ResultSourceResolver::class);
        $resolver->shouldReceive('resolveAll')->once()->andReturn([$source]);
        $resolver->shouldReceive('persistSnapshot')->once()->andReturnUsing(function (LottoDraw $draw, LottoResultSource $source): void {
            DB::table('lotto_draws')
                ->where('id', (int) $draw->id)
                ->update([
                    'result_source_id' => (int) $source->id,
                    'updated_at' => now(),
                ]);
        });

        $service = new class(
            $resolver,
            Mockery::mock(ResultRequestBuilder::class),
            Mockery::mock(ResultFetcher::class),
            Mockery::mock(ResultParser::class),
            Mockery::mock(ResultCandidateSelector::class),
            Mockery::mock(ResultMapper::class),
            Mockery::mock(ResultValidator::class),
            Mockery::mock(ResultApplier::class),
            new AutoResultHardeningService()
        ) extends AutoResultPipelineService {
            protected function runV2CutoverPipeline(
                LottoDraw $draw,
                LottoResultSource $source,
                string $runId,
                ?string $expectedDrawDate
            ): array {
                return [
                    'status' => 'NOT_READY',
                    'error_code' => 'NOT_READY_BUSINESS_RULE',
                    'error_stage' => 'READINESS',
                    'error_message' => 'ผลหวยยังไม่ออก',
                    'trace_json' => ['status' => 'NOT_READY'],
                ];
            }
        };

        $result = $service->processDraw(
            LottoDraw::query()->findOrFail(1),
            false,
            false,
            'run_test_not_ready',
            '2026-04-05'
        );

        $this->assertSame('NOT_READY', $result['status']);
        $this->assertSame('ผลหวยยังไม่ออก', $result['error_message']);

        $log = DB::table('lotto_result_fetch_logs')->first();
        $this->assertNotNull($log);
        $this->assertSame('NOT_READY', $log->status);
        $this->assertSame('NOT_READY_BUSINESS_RULE', $log->error_code);
        $this->assertSame('READINESS', $log->error_stage);

        $draw = DB::table('lotto_draws')->where('id', 1)->first();
        $this->assertSame(1, (int) $draw->result_fetch_attempts);
        $this->assertSame('NOT_READY', $draw->result_fetch_status);
        $this->assertSame('ผลหวยยังไม่ออก', $draw->result_fetch_error);
    }

    public function test_manual_retry_bypasses_exhausted_retry_policy_and_updates_terminal_fetch_state(): void
    {
        config()->set('lotto_auto_result.retry.max_attempts', 3);

        DB::table('lotto_draws')
            ->where('id', 1)
            ->update([
                'result_fetch_status' => 'EXHAUSTED',
                'result_fetch_attempts' => 3,
                'result_fetch_error' => 'retry attempts exhausted',
                'updated_at' => now(),
            ]);

        DB::table('lotto_result_fetch_logs')->insert([
            ['draw_id' => 1, 'market_id' => 1, 'source_id' => 7, 'attempt_no' => 1, 'status' => 'NOT_READY', 'created_at' => now()->subMinutes(15)],
            ['draw_id' => 1, 'market_id' => 1, 'source_id' => 7, 'attempt_no' => 2, 'status' => 'NOT_READY', 'created_at' => now()->subMinutes(10)],
            ['draw_id' => 1, 'market_id' => 1, 'source_id' => 7, 'attempt_no' => 3, 'status' => 'NOT_READY', 'created_at' => now()->subMinutes(5)],
        ]);

        $source = new LottoResultSource([
            'market_id' => 1,
            'is_active' => true,
            'cutover_enabled' => true,
            'pipeline_version' => 'V2_CUTOVER',
            'fetch_strategy' => 'JSON_HTTP',
            'parser_type' => 'JSON_PATH',
            'timeout_seconds' => 10,
        ]);
        $source->id = 7;

        $resolver = Mockery::mock(ResultSourceResolver::class);
        $resolver->shouldReceive('resolveAll')->once()->andReturn([$source]);
        $resolver->shouldReceive('persistSnapshot')->once()->andReturnNull();

        $applier = Mockery::mock(ResultApplier::class);
        $applier->shouldReceive('apply')
            ->once()
            ->andReturn([
                'status' => 'APPLIED',
                'result_hash' => 'hash123',
            ]);

        $service = new class(
            $resolver,
            Mockery::mock(ResultRequestBuilder::class),
            Mockery::mock(ResultFetcher::class),
            Mockery::mock(ResultParser::class),
            Mockery::mock(ResultCandidateSelector::class),
            Mockery::mock(ResultMapper::class),
            Mockery::mock(ResultValidator::class),
            $applier,
            new AutoResultHardeningService()
        ) extends AutoResultPipelineService {
            protected function runV2CutoverPipeline(
                LottoDraw $draw,
                LottoResultSource $source,
                string $runId,
                ?string $expectedDrawDate
            ): array {
                return [
                    'status' => 'VALID',
                    'validated' => [
                        'draw_date' => '2026-04-05',
                        'first_prize' => '95640',
                        'last_2_digits' => '95',
                    ],
                    'selection' => [],
                    'mapped' => [],
                    'trace_json' => ['status' => 'VALID'],
                ];
            }
        };

        $result = $service->processDraw(
            LottoDraw::query()->findOrFail(1),
            false,
            true,
            'run_manual_retry',
            '2026-04-05'
        );

        $this->assertSame('APPLIED', $result['status']);

        $log = DB::table('lotto_result_fetch_logs')
            ->where('run_id', 'run_manual_retry')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('APPLIED', $log->status);
        $this->assertSame('apply_v2', $log->pipeline_stage);
        $this->assertSame(1, (int) $log->is_manual_retry);

        $draw = DB::table('lotto_draws')->where('id', 1)->first();
        $this->assertSame(4, (int) $draw->result_fetch_attempts);
        $this->assertSame('APPLIED', $draw->result_fetch_status);
        $this->assertNull($draw->result_fetch_error);
    }
}
