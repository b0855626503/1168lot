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

class AutoResultPipelineUnhandledExceptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::dropIfExists('lotto_result_fetch_logs');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('logs');

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

        Schema::create('logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('emp_code')->nullable();
            $table->string('mode', 32)->nullable();
            $table->string('menu', 255)->nullable();
            $table->unsignedBigInteger('record')->nullable();
            $table->longText('item_before')->nullable();
            $table->longText('item')->nullable();
            $table->string('ip', 64)->nullable();
            $table->string('user_create', 64)->nullable();
            $table->dateTime('date_update')->nullable();
            $table->dateTime('date_create')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_v2_exception_after_attempt_increment_is_logged_and_persisted(): void
    {
        DB::table('lotto_markets')->insert([
            'id' => 1,
            'name' => 'หวยทดสอบ',
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 1,
            'market_id' => 1,
            'draw_date' => '2026-04-04',
            'result_at' => '2026-04-04 10:00:00',
            'status' => 'closed',
            'result_fetch_status' => null,
            'result_fetch_attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
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
        $resolver->shouldReceive('persistSnapshot')->once()->andReturnUsing(function (LottoDraw $draw, LottoResultSource $source): void {
            DB::table('lotto_draws')
                ->where('id', (int) $draw->id)
                ->update([
                    'result_source_id' => (int) $source->id,
                    'result_source_snapshot_json' => json_encode([
                        'id' => (int) $source->id,
                        'resolved_at' => now()->toDateTimeString(),
                    ], JSON_UNESCAPED_UNICODE),
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
                throw new \RuntimeException('v2 blew up');
            }
        };

        $result = $service->processDraw(
            LottoDraw::query()->findOrFail(1),
            false,
            false,
            'run_test_1',
            '2026-04-04'
        );

        $this->assertSame('VALIDATION_ERROR', $result['status']);
        $this->assertSame(1, $result['attempt_no']);
        $this->assertSame('v2 blew up', $result['error_message']);

        $log = DB::table('lotto_result_fetch_logs')->first();
        $this->assertNotNull($log);
        $this->assertSame(1, (int) $log->draw_id);
        $this->assertSame(7, (int) $log->source_id);
        $this->assertSame('VALIDATION_ERROR', $log->status);
        $this->assertSame('v2_cutover', $log->pipeline_stage);
        $this->assertSame('UNHANDLED_EXCEPTION', $log->error_code);
        $this->assertSame('PIPELINE', $log->error_stage);
        $this->assertSame('v2 blew up', $log->error_message);

        $draw = DB::table('lotto_draws')->where('id', 1)->first();
        $this->assertSame(1, (int) $draw->result_fetch_attempts);
        $this->assertSame('VALIDATION_ERROR', $draw->result_fetch_status);
        $this->assertSame('v2 blew up', $draw->result_fetch_error);
        $this->assertSame(0, DB::table('logs')->count());
    }
}
