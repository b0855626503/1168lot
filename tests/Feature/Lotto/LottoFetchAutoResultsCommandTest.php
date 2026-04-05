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
            ['id' => 1, 'market_id' => 1, 'result_at' => now()->subMinutes(4), 'status' => 'closed', 'result_fetch_status' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'market_id' => 2, 'result_at' => now()->subMinutes(3), 'status' => 'closed', 'result_fetch_status' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'market_id' => 3, 'result_at' => now()->subMinutes(2), 'status' => 'closed', 'result_fetch_status' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'market_id' => 4, 'result_at' => now()->subMinute(), 'status' => 'closed', 'result_fetch_status' => null, 'created_at' => now(), 'updated_at' => now()],
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
}
