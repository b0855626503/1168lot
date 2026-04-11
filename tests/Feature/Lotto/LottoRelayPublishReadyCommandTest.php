<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Services\Relay\LotteryRelayPublisher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class LottoRelayPublishReadyCommandTest extends TestCase
{
    private array $loggedInfoMessages = [];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('lottery_result_relay.enabled', true);
        config()->set('lottery_result_relay.mode', 'primary');
        config()->set('lottery_result_relay.log_channel', 'stack');

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
            $table->string('status', 32)->nullable();
            $table->string('result_fetch_status', 32)->nullable();
            $table->string('result_hash')->nullable();
            $table->dateTime('result_applied_at')->nullable();
            $table->timestamps();
        });
    }

    private function mockRelayLogger(): void
    {
        $this->loggedInfoMessages = [];

        Log::shouldReceive('channel')
            ->andReturnSelf();
        Log::shouldReceive('info')
            ->andReturnUsing(function (string $message, array $context = []): void {
                $this->loggedInfoMessages[] = [$message, $context];
            });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_command_backfills_publish_for_existing_applied_draws(): void
    {
        DB::table('lotto_markets')->insert([
            'id' => 1,
            'code' => 'downjone-stock',
            'is_enabled' => 1,
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 950,
            'market_id' => 1,
            'draw_date' => '2026-04-11',
            'status' => 'resulted',
            'result_fetch_status' => 'APPLIED',
            'result_hash' => 'checksum-950',
            'result_applied_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $publisher = Mockery::mock(LotteryRelayPublisher::class);
        $publisher->shouldReceive('publishIfReady')
            ->once()
            ->withArgs(function ($draw, $force): bool {
                return (int) $draw->id === 950 && $force === false;
            })
            ->andReturn('1712800000000-0');

        $this->app->instance(LotteryRelayPublisher::class, $publisher);
        $this->mockRelayLogger();

        $exitCode = Artisan::call('lotto:relay:publish-ready', [
            '--date' => '2026-04-11',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Published=1', Artisan::output());
        $this->assertContains(
            ['LOTTERY_RELAY_PUBLISH_READY_STARTED', [
                'draw_id' => null,
                'date' => '2026-04-11',
                'market_id' => null,
                'type' => null,
                'force' => false,
                'limit' => 100,
            ]],
            $this->loggedInfoMessages
        );
        $this->assertContains(
            ['LOTTERY_RELAY_PUBLISH_READY_RESULT', [
                'draw_id' => 950,
                'market_id' => 1,
                'market_code' => 'downjone-stock',
                'draw_date' => '2026-04-11',
                'status' => 'published',
                'stream_id' => '1712800000000-0',
                'force' => false,
            ]],
            $this->loggedInfoMessages
        );
        $this->assertContains(
            ['LOTTERY_RELAY_PUBLISH_READY_FINISHED', [
                'scanned' => 1,
                'published' => 1,
                'skipped' => 0,
                'force' => false,
            ]],
            $this->loggedInfoMessages
        );
    }

    public function test_command_filters_by_canonical_type(): void
    {
        DB::table('lotto_markets')->insert([
            ['id' => 1, 'code' => 'downjone-stock', 'is_enabled' => 1],
            ['id' => 2, 'code' => 'laos-vip', 'is_enabled' => 1],
        ]);

        DB::table('lotto_draws')->insert([
            [
                'id' => 950,
                'market_id' => 1,
                'draw_date' => '2026-04-11',
                'status' => 'resulted',
                'result_fetch_status' => 'APPLIED',
                'result_hash' => 'checksum-950',
                'result_applied_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 951,
                'market_id' => 2,
                'draw_date' => '2026-04-11',
                'status' => 'resulted',
                'result_fetch_status' => 'APPLIED',
                'result_hash' => 'checksum-951',
                'result_applied_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $publisher = Mockery::mock(LotteryRelayPublisher::class);
        $publisher->shouldReceive('publishIfReady')
            ->once()
            ->withArgs(function ($draw, $force): bool {
                return (int) $draw->id === 950 && $force === false;
            })
            ->andReturn('1712800000001-0');

        $this->app->instance(LotteryRelayPublisher::class, $publisher);
        $this->mockRelayLogger();

        $exitCode = Artisan::call('lotto:relay:publish-ready', [
            '--type' => 'dji',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Published=1', Artisan::output());
    }

    public function test_command_can_force_republish_even_when_checksum_marker_already_exists(): void
    {
        DB::table('lotto_markets')->insert([
            'id' => 1,
            'code' => 'downjone-stock',
            'is_enabled' => 1,
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 952,
            'market_id' => 1,
            'draw_date' => '2026-04-11',
            'status' => 'resulted',
            'result_fetch_status' => 'APPLIED',
            'result_hash' => 'checksum-952',
            'result_applied_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $publisher = Mockery::mock(LotteryRelayPublisher::class);
        $publisher->shouldReceive('publishIfReady')
            ->once()
            ->withArgs(function ($draw, $force): bool {
                return (int) $draw->id === 952 && $force === true;
            })
            ->andReturn('1712800000002-0');

        $this->app->instance(LotteryRelayPublisher::class, $publisher);
        $this->mockRelayLogger();

        $exitCode = Artisan::call('lotto:relay:publish-ready', [
            '--draw-id' => 952,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Published=1', Artisan::output());
        $this->assertContains(
            ['LOTTERY_RELAY_PUBLISH_READY_STARTED', [
                'draw_id' => 952,
                'date' => null,
                'market_id' => null,
                'type' => null,
                'force' => true,
                'limit' => 100,
            ]],
            $this->loggedInfoMessages
        );
    }
}
