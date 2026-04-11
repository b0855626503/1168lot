<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Services\AutoResult\AutoResultPipelineService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class LottoFetchAutoResultsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_result_sources');
        Schema::dropIfExists('lotto_result_fetch_logs');
        Schema::dropIfExists('logs');
        Schema::dropIfExists('configs');

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
            $table->unsignedBigInteger('result_source_id')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_result_sources', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->boolean('is_active')->default(true);
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

        Schema::create('configs', function (Blueprint $table): void {
            $table->bigIncrements('code');
            $table->string('seamless', 1)->nullable();
        });

        DB::table('configs')->insert([
            'code' => 1,
            'seamless' => 'N',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_command_continues_processing_other_draws_after_unhandled_exception(): void
    {
        DB::table('lotto_result_sources')->insert([
            ['id' => 1, 'market_id' => 1, 'is_active' => 1],
            ['id' => 2, 'market_id' => 2, 'is_active' => 1],
            ['id' => 3, 'market_id' => 3, 'is_active' => 1],
            ['id' => 4, 'market_id' => 4, 'is_active' => 1],
        ]);

        DB::table('lotto_draws')->insert([
            ['id' => 1, 'market_id' => 1, 'draw_date' => '2026-04-10', 'result_at' => now()->subMinutes(4), 'status' => 'closed', 'result_fetch_status' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'market_id' => 2, 'draw_date' => '2026-04-10', 'result_at' => now()->subMinutes(3), 'status' => 'closed', 'result_fetch_status' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'market_id' => 3, 'draw_date' => '2026-04-10', 'result_at' => now()->subMinutes(2), 'status' => 'closed', 'result_fetch_status' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'market_id' => 4, 'draw_date' => '2026-04-10', 'result_at' => now()->subMinute(), 'status' => 'closed', 'result_fetch_status' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $pipeline = Mockery::mock(AutoResultPipelineService::class);
        $pipeline->shouldReceive('markExhausted')->never();
        $pipeline->shouldReceive('processDraw')->once()->andReturn(['status' => 'APPLIED']);
        $pipeline->shouldReceive('processDraw')->once()->andReturn(['status' => 'VALIDATION_ERROR']);
        $pipeline->shouldReceive('processDraw')->once()->andThrow(new \RuntimeException('boom'));
        $pipeline->shouldReceive('processDraw')->once()->andReturn(['status' => 'APPLIED']);

        $this->app->instance(AutoResultPipelineService::class, $pipeline);

        $exitCode = Artisan::call('lotto:fetch-auto-results', ['--limit' => 10]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('processed=4', $output);
        $this->assertStringContainsString('unhandled_draw_exceptions=1', $output);
        $this->assertStringContainsString('- APPLIED: 2', $output);
        $this->assertStringContainsString('- UNHANDLED_EXCEPTION: 1', $output);
        $this->assertStringContainsString('- VALIDATION_ERROR: 1', $output);

        $log = DB::table('lotto_result_fetch_logs')
            ->where('pipeline_stage', 'process_draw_exception')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(3, (int) $log->draw_id);
        $this->assertSame('VALIDATION_ERROR', $log->status);
        $this->assertSame('UNHANDLED_EXCEPTION', $log->error_code);
        $this->assertSame('COMMAND', $log->error_stage);
        $this->assertSame('boom', $log->error_message);

        $draw = DB::table('lotto_draws')->where('id', 3)->first();
        $this->assertSame('VALIDATION_ERROR', $draw->result_fetch_status);
        $this->assertSame('boom', $draw->result_fetch_error);
        $this->assertSame(0, DB::table('logs')->count());
    }

    public function test_manual_retry_processes_single_exhausted_draw(): void
    {
        DB::table('lotto_result_sources')->insert([
            'id' => 1,
            'market_id' => 1,
            'is_active' => 1,
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 617,
            'market_id' => 1,
            'draw_date' => '2026-04-10',
            'result_at' => now()->subMinutes(5),
            'status' => 'closed',
            'result_fetch_status' => 'EXHAUSTED',
            'result_fetch_attempts' => 3,
            'result_fetch_error' => 'retry attempts exhausted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pipeline = Mockery::mock(AutoResultPipelineService::class);
        $pipeline->shouldReceive('markExhausted')->never();
        $pipeline->shouldReceive('processDraw')
            ->once()
            ->withArgs(function ($draw, $dryRun, $manualRetry, $runId, $expectedDrawDate): bool {
                return (int) $draw->id === 617
                    && $dryRun === false
                    && $manualRetry === true
                    && is_string($runId) && str_starts_with($runId, 'cmd_')
                    && $expectedDrawDate === null;
            })
            ->andReturn(['status' => 'APPLIED']);

        $this->app->instance(AutoResultPipelineService::class, $pipeline);

        $exitCode = Artisan::call('lotto:fetch-auto-results', [
            '--draw-id' => 617,
            '--manual-retry' => true,
            '--limit' => 1,
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('selected=1', $output);
        $this->assertStringContainsString('processed=1', $output);
        $this->assertStringContainsString('- APPLIED: 1', $output);
    }

    public function test_draw_id_can_reprocess_single_resulted_draw_for_backfill(): void
    {
        DB::table('lotto_result_sources')->insert([
            'id' => 1,
            'market_id' => 1,
            'is_active' => 1,
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 701,
            'market_id' => 1,
            'draw_date' => '2026-04-08',
            'result_at' => now()->subDays(2),
            'status' => 'resulted',
            'result_fetch_status' => 'APPLIED',
            'result_fetch_attempts' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pipeline = Mockery::mock(AutoResultPipelineService::class);
        $pipeline->shouldReceive('markExhausted')->never();
        $pipeline->shouldReceive('processDraw')
            ->once()
            ->withArgs(function ($draw, $dryRun, $manualRetry, $runId, $expectedDrawDate): bool {
                return (int) $draw->id === 701
                    && $dryRun === false
                    && $manualRetry === false
                    && is_string($runId) && str_starts_with($runId, 'cmd_')
                    && $expectedDrawDate === null;
            })
            ->andReturn(['status' => 'APPLIED']);

        $this->app->instance(AutoResultPipelineService::class, $pipeline);

        $exitCode = Artisan::call('lotto:fetch-auto-results', [
            '--draw-id' => 701,
            '--limit' => 1,
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('selected=1', $output);
        $this->assertStringContainsString('processed=1', $output);
        $this->assertStringContainsString('- APPLIED: 1', $output);
    }

    public function test_draw_date_can_backfill_all_markets_for_one_day(): void
    {
        DB::table('lotto_result_sources')->insert([
            ['id' => 1, 'market_id' => 1, 'is_active' => 1],
            ['id' => 2, 'market_id' => 2, 'is_active' => 1],
            ['id' => 3, 'market_id' => 3, 'is_active' => 1],
        ]);

        DB::table('lotto_draws')->insert([
            ['id' => 801, 'market_id' => 1, 'draw_date' => '2026-04-05', 'result_at' => now()->subDays(5), 'status' => 'resulted', 'result_fetch_status' => 'APPLIED', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 802, 'market_id' => 2, 'draw_date' => '2026-04-05', 'result_at' => now()->subDays(5), 'status' => 'closed', 'result_fetch_status' => 'EXHAUSTED', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 803, 'market_id' => 3, 'draw_date' => '2026-04-06', 'result_at' => now()->subDays(4), 'status' => 'closed', 'result_fetch_status' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $pipeline = Mockery::mock(AutoResultPipelineService::class);
        $pipeline->shouldReceive('markExhausted')->never();
        $pipeline->shouldReceive('processDraw')
            ->twice()
            ->withArgs(function ($draw, $dryRun, $manualRetry, $runId, $expectedDrawDate): bool {
                return in_array((int) $draw->id, [801, 802], true)
                    && $dryRun === false
                    && $manualRetry === false
                    && is_string($runId) && str_starts_with($runId, 'cmd_')
                    && $expectedDrawDate === null;
            })
            ->andReturn(['status' => 'APPLIED']);

        $this->app->instance(AutoResultPipelineService::class, $pipeline);

        $exitCode = Artisan::call('lotto:fetch-auto-results', [
            '--draw-date' => '2026-04-05',
            '--limit' => 10,
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('selected=2', $output);
        $this->assertStringContainsString('processed=2', $output);
        $this->assertStringContainsString('- APPLIED: 2', $output);
    }
}
