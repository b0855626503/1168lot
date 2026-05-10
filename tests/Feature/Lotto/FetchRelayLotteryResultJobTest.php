<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Jobs\FetchRelayLotteryResultJob;
use Gametech\Lotto\Services\AutoResult\AutoResultPipelineService;
use Gametech\Lotto\Services\Relay\LotteryRelayRuntime;
use Gametech\Lotto\Services\Relay\LotteryRelayTypeRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class FetchRelayLotteryResultJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('lottery_result_relay.enabled', true);
        config()->set('lottery_result_relay.mode', 'clone');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code')->nullable();
            $table->boolean('is_enabled')->default(true);
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
            $table->string('status', 32);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_job_resolves_draw_by_type_and_business_date_then_reuses_pipeline(): void
    {
        DB::table('lotto_markets')->insert([
            'id' => 12,
            'code' => 'downjone-stock',
            'is_enabled' => 1,
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 88,
            'market_id' => 12,
            'draw_date' => '2026-04-11',
            'status' => 'closed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pipeline = Mockery::mock(AutoResultPipelineService::class);
        $pipeline->shouldReceive('processDraw')
            ->once()
            ->withArgs(function ($draw, $dryRun, $manualRetry, $runId, $expectedDrawDate): bool {
                return (int) $draw->id === 88
                    && $dryRun === false
                    && $manualRetry === false
                    && $runId === 'relay_dji:2026-04-11:abcdef'
                    && $expectedDrawDate === '2026-04-11';
            })
            ->andReturn(['status' => 'APPLIED']);

        $this->app->instance(AutoResultPipelineService::class, $pipeline);

        $job = new FetchRelayLotteryResultJob('dji', '2026-04-11', 'dji:2026-04-11:abcdef', 'checksum-1');
        $job->handle(
            $this->app->make(LotteryRelayRuntime::class),
            $this->app->make(LotteryRelayTypeRegistry::class),
            $pipeline
        );

        $this->assertTrue(true);
    }

    public function test_job_noops_with_audit_log_when_draw_not_found(): void
    {
        DB::table('lotto_markets')->insert([
            'id' => 12,
            'code' => 'downjone-stock',
            'is_enabled' => 1,
        ]);

        Log::shouldReceive('channel')
            ->once()
            ->with('daily')
            ->andReturnSelf();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $event, array $context): bool {
                return $event === 'LOTTERY_RELAY_FETCH_DRAW_NOT_FOUND_NOOP'
                    && ($context['event_id'] ?? null) === 'baac:2026-05-09:abc'
                    && ($context['type'] ?? null) === 'baac'
                    && ($context['date'] ?? null) === '2026-05-09'
                    && ($context['action'] ?? null) === 'no_op';
            });

        $pipeline = Mockery::mock(AutoResultPipelineService::class);
        $pipeline->shouldReceive('processDraw')->never();

        $job = new FetchRelayLotteryResultJob('baac', '2026-05-09', 'baac:2026-05-09:abc', 'checksum-404');
        $job->handle(
            $this->app->make(LotteryRelayRuntime::class),
            $this->app->make(LotteryRelayTypeRegistry::class),
            $pipeline
        );

        $this->assertTrue(true);
    }
}
