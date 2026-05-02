<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Services\AutoResult\AutoResultPipelineService;
use Gametech\Lotto\Services\Relay\LotteryRelayRuntime;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class LottoCloneAutoPullThaiResultsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('group_id');
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->dateTime('result_at')->nullable();
            $table->string('status', 32);
            $table->json('result_number')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_skips_when_relay_mode_is_not_clone(): void
    {
        $relay = Mockery::mock(LotteryRelayRuntime::class);
        $relay->allows('isClone')->andReturn(false);
        $this->app->instance(LotteryRelayRuntime::class, $relay);

        $this->artisan('lotto:clone-auto-pull-thai-results')
            ->expectsOutput('Skipped: relay mode is not clone.')
            ->assertExitCode(0);
    }

    public function test_skips_when_clone_auto_pull_disabled(): void
    {
        $relay = Mockery::mock(LotteryRelayRuntime::class);
        $relay->allows('isClone')->andReturn(true);
        $this->app->instance(LotteryRelayRuntime::class, $relay);

        config()->set('lotto_auto_result.clone_auto_pull.enabled', false);

        $this->artisan('lotto:clone-auto-pull-thai-results')
            ->expectsOutput('Skipped: clone auto pull is disabled.')
            ->assertExitCode(0);
    }

    public function test_skips_when_no_group_ids_configured(): void
    {
        $relay = Mockery::mock(LotteryRelayRuntime::class);
        $relay->allows('isClone')->andReturn(true);
        $this->app->instance(LotteryRelayRuntime::class, $relay);

        config()->set('lotto_auto_result.clone_auto_pull.enabled', true);
        config()->set('lotto_auto_result.clone_auto_pull.group_ids', []);

        $this->artisan('lotto:clone-auto-pull-thai-results')
            ->expectsOutput('Skipped: no group IDs configured.')
            ->assertExitCode(0);
    }

    public function test_outputs_no_eligible_draws_when_none_found(): void
    {
        $relay = Mockery::mock(LotteryRelayRuntime::class);
        $relay->allows('isClone')->andReturn(true);
        $this->app->instance(LotteryRelayRuntime::class, $relay);

        config()->set('lotto_auto_result.clone_auto_pull.enabled', true);
        config()->set('lotto_auto_result.clone_auto_pull.group_ids', [999]);

        $this->artisan('lotto:clone-auto-pull-thai-results')
            ->expectsOutput('No eligible draws found.')
            ->assertExitCode(0);
    }

    public function test_skips_draw_already_resulted(): void
    {
        $relay = Mockery::mock(LotteryRelayRuntime::class);
        $relay->allows('isClone')->andReturn(true);
        $this->app->instance(LotteryRelayRuntime::class, $relay);

        $pipeline = Mockery::mock(AutoResultPipelineService::class);
        $pipeline->allows('processDraw')->never();
        $this->app->instance(AutoResultPipelineService::class, $pipeline);

        config()->set('lotto_auto_result.clone_auto_pull.enabled', true);
        config()->set('lotto_auto_result.clone_auto_pull.group_ids', [1]);
        config()->set('lotto_auto_result.clone_auto_pull.delay_minutes', 0);

        DB::table('lotto_markets')->insert(['id' => 1, 'group_id' => 1]);
        DB::table('lotto_draws')->insert([
            'id' => 1,
            'market_id' => 1,
            'result_at' => now()->subMinutes(5)->toDateTimeString(),
            'status' => 'resulted',
            'result_number' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $this->artisan('lotto:clone-auto-pull-thai-results')
            ->expectsOutput('No eligible draws found.')
            ->assertExitCode(0);
    }

    public function test_skips_draw_with_result_number(): void
    {
        $relay = Mockery::mock(LotteryRelayRuntime::class);
        $relay->allows('isClone')->andReturn(true);
        $this->app->instance(LotteryRelayRuntime::class, $relay);

        $pipeline = Mockery::mock(AutoResultPipelineService::class);
        $pipeline->allows('processDraw')->never();
        $this->app->instance(AutoResultPipelineService::class, $pipeline);

        config()->set('lotto_auto_result.clone_auto_pull.enabled', true);
        config()->set('lotto_auto_result.clone_auto_pull.group_ids', [1]);
        config()->set('lotto_auto_result.clone_auto_pull.delay_minutes', 0);

        DB::table('lotto_markets')->insert(['id' => 1, 'group_id' => 1]);
        DB::table('lotto_draws')->insert([
            'id' => 2,
            'market_id' => 1,
            'result_at' => now()->subMinutes(5)->toDateTimeString(),
            'status' => 'closed',
            'result_number' => json_encode(['first' => '123']),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $this->artisan('lotto:clone-auto-pull-thai-results')
            ->expectsOutput('No eligible draws found.')
            ->assertExitCode(0);
    }

    public function test_processes_eligible_draw(): void
    {
        $relay = Mockery::mock(LotteryRelayRuntime::class);
        $relay->allows('isClone')->andReturn(true);
        $this->app->instance(LotteryRelayRuntime::class, $relay);

        $pipeline = Mockery::mock(AutoResultPipelineService::class);
        $pipeline->allows('processDraw')->once()->andReturn(['status' => 'APPLIED']);
        $this->app->instance(AutoResultPipelineService::class, $pipeline);

        config()->set('lotto_auto_result.clone_auto_pull.enabled', true);
        config()->set('lotto_auto_result.clone_auto_pull.group_ids', [1]);
        config()->set('lotto_auto_result.clone_auto_pull.delay_minutes', 0);

        DB::table('lotto_markets')->insert(['id' => 1, 'group_id' => 1]);
        DB::table('lotto_draws')->insert([
            'id' => 3,
            'market_id' => 1,
            'result_at' => now()->subMinutes(2)->toDateTimeString(),
            'status' => 'closed',
            'result_number' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $this->artisan('lotto:clone-auto-pull-thai-results')
            ->assertExitCode(0);
    }

    public function test_command_is_registered_in_schedule(): void
    {
        $schedule = app(Schedule::class);

        $found = collect($schedule->events())
            ->contains(fn ($e) => str_contains((string) ($e->command ?? ''), 'clone-auto-pull-thai-results'));

        $this->assertTrue($found, 'lotto:clone-auto-pull-thai-results not found in schedule');
    }
}
