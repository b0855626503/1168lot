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

    public function test_skips_draw_when_result_at_not_due_yet(): void
    {
        $relay = Mockery::mock(LotteryRelayRuntime::class);
        $relay->allows('isClone')->andReturn(true);
        $this->app->instance(LotteryRelayRuntime::class, $relay);

        $pipeline = Mockery::mock(AutoResultPipelineService::class);
        $pipeline->allows('processDraw')->never();
        $this->app->instance(AutoResultPipelineService::class, $pipeline);

        config()->set('lotto_auto_result.clone_auto_pull.enabled', true);
        config()->set('lotto_auto_result.clone_auto_pull.group_ids', [1]);
        config()->set('lotto_auto_result.clone_auto_pull.delay_minutes', 10);

        DB::table('lotto_markets')->insert(['id' => 10, 'group_id' => 1]);
        DB::table('lotto_draws')->insert([
            'id' => 10,
            'market_id' => 10,
            'result_at' => now()->subMinutes(1)->toDateTimeString(),
            'status' => 'closed',
            'result_number' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $this->artisan('lotto:clone-auto-pull-thai-results')
            ->expectsOutput('No eligible draws found.')
            ->assertExitCode(0);
    }

    public function test_processes_only_configured_group_ids(): void
    {
        $relay = Mockery::mock(LotteryRelayRuntime::class);
        $relay->allows('isClone')->andReturn(true);
        $this->app->instance(LotteryRelayRuntime::class, $relay);

        $pipeline = Mockery::mock(AutoResultPipelineService::class);
        $pipeline->shouldReceive('processDraw')
            ->once()
            ->withArgs(fn ($draw, bool $dryRun): bool => (int) $draw->id === 21 && $dryRun === false)
            ->andReturn(['status' => 'APPLIED']);
        $this->app->instance(AutoResultPipelineService::class, $pipeline);

        config()->set('lotto_auto_result.clone_auto_pull.enabled', true);
        config()->set('lotto_auto_result.clone_auto_pull.group_ids', [100]);
        config()->set('lotto_auto_result.clone_auto_pull.delay_minutes', 0);

        DB::table('lotto_markets')->insert([
            ['id' => 21, 'group_id' => 100],
            ['id' => 22, 'group_id' => 200],
        ]);

        DB::table('lotto_draws')->insert([
            [
                'id' => 21,
                'market_id' => 21,
                'result_at' => now()->subMinutes(5)->toDateTimeString(),
                'status' => 'closed',
                'result_number' => null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
            [
                'id' => 22,
                'market_id' => 22,
                'result_at' => now()->subMinutes(5)->toDateTimeString(),
                'status' => 'closed',
                'result_number' => null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
        ]);

        $this->artisan('lotto:clone-auto-pull-thai-results')
            ->expectsOutputToContain('Draw 21: APPLIED')
            ->assertExitCode(0);
    }

    public function test_skips_processing_when_lock_is_not_acquired(): void
    {
        $relay = Mockery::mock(LotteryRelayRuntime::class);
        $relay->allows('isClone')->andReturn(true);
        $this->app->instance(LotteryRelayRuntime::class, $relay);

        $pipeline = Mockery::mock(AutoResultPipelineService::class);
        $pipeline->shouldNotReceive('processDraw');
        $this->app->instance(AutoResultPipelineService::class, $pipeline);

        config()->set('lotto_auto_result.clone_auto_pull.enabled', true);
        config()->set('lotto_auto_result.clone_auto_pull.group_ids', [1]);
        config()->set('lotto_auto_result.clone_auto_pull.delay_minutes', 0);

        DB::table('lotto_markets')->insert(['id' => 31, 'group_id' => 1]);
        DB::table('lotto_draws')->insert([
            'id' => 31,
            'market_id' => 31,
            'result_at' => now()->subMinutes(5)->toDateTimeString(),
            'status' => 'closed',
            'result_number' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $lock = cache()->lock('lotto:clone-auto-pull:31', 300);
        $acquired = $lock->get();

        $this->assertTrue($acquired);

        try {
            $this->artisan('lotto:clone-auto-pull-thai-results')
                ->expectsOutputToContain('Done. processed=0 skipped=1')
                ->assertExitCode(0);
        } finally {
            $lock->release();
        }
    }

    public function test_command_does_not_directly_update_draw_result_or_wallet_settlement(): void
    {
        $source = file_get_contents(base_path('packages/Gametech/Lotto/src/Console/Commands/LottoCloneAutoPullThaiResultsCommand.php'));

        $this->assertIsString($source);
        $this->assertStringNotContainsString("DB::table('lotto_draws')->update", $source);
        $this->assertStringNotContainsString("DB::table('wallet_transactions')->update", $source);
        $this->assertStringContainsString('processDraw($fresh, $dryRun)', $source);
    }

    public function test_command_is_registered_in_schedule(): void
    {
        $schedule = app(Schedule::class);

        $found = collect($schedule->events())
            ->contains(fn ($e) => str_contains((string) ($e->command ?? ''), 'clone-auto-pull-thai-results'));

        $this->assertTrue($found, 'lotto:clone-auto-pull-thai-results not found in schedule');
    }
}
