<?php

namespace Tests\Feature\Game;

use Gametech\Game\Repositories\GameRepository;
use Gametech\Game\Repositories\GameSeamlessRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class GameUserRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('games_user');

        Schema::create('games_user', function (Blueprint $table): void {
            $table->bigIncrements('code');
            $table->unsignedBigInteger('game_code');
            $table->unsignedBigInteger('member_code');
            $table->string('user_name');
            $table->string('user_pass')->default('');
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('enable', 1)->default('Y');
            $table->string('user_create');
            $table->string('user_update');
            $table->dateTime('date_create')->nullable();
            $table->dateTime('date_update')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('games_user');

        Mockery::close();

        parent::tearDown();
    }

    public function test_add_game_user_accepts_array_source_without_trying_to_save_it(): void
    {
        $repository = $this->makeRepositoryWithDriverResult([
            'success' => true,
            'user_name' => 'array-user',
            'user_pass' => '',
        ]);

        $result = $repository->addGameUser(1, 30, [
            'username' => 'array-user',
            'user_create' => 'Api Test',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('array-user', $result['data']->user_name);
        $this->assertDatabaseHas('games_user', [
            'member_code' => 30,
            'game_code' => 1,
            'user_name' => 'array-user',
            'user_create' => 'Api Test',
            'user_update' => 'Api Test',
        ]);
    }

    public function test_add_game_user_updates_object_source_when_available(): void
    {
        $repository = $this->makeRepositoryWithDriverResult([
            'success' => true,
            'user_name' => 'object-user',
            'user_pass' => 'secret',
        ]);

        $source = new class
        {
            public string $username = 'object-user';
            public string $user_create = 'Api Test';
            public string $user_update = 'Api Update';
            public string $game_user = '';
            public bool $saved = false;

            public function save(): void
            {
                $this->saved = true;
            }
        };

        $result = $repository->addGameUser(1, 31, $source);

        $this->assertTrue($result['success']);
        $this->assertSame('object-user', $source->game_user);
        $this->assertTrue($source->saved);
        $this->assertDatabaseHas('games_user', [
            'member_code' => 31,
            'game_code' => 1,
            'user_name' => 'object-user',
            'user_pass' => 'secret',
            'user_create' => 'Api Test',
            'user_update' => 'Api Update',
        ]);
    }

    public function test_auto_login_seamless_by_game_user_uses_preloaded_username(): void
    {
        $gameRepository = Mockery::mock(GameRepository::class);
        $gameSeamlessRepository = Mockery::mock(GameSeamlessRepository::class);
        $gameSeamlessRepository->shouldReceive('findOneWhere')
            ->once()
            ->with(['id' => 'PGSOFT', 'enable' => 'Y'])
            ->andReturn((object) [
                'method' => 'seamless',
            ]);

        $this->app->instance(GameRepository::class, $gameRepository);
        $this->app->instance(GameSeamlessRepository::class, $gameSeamlessRepository);

        $driver = Mockery::mock();
        $driver->shouldReceive('login')
            ->once()
            ->with(Mockery::on(function (array $payload): bool {
                return ($payload['username'] ?? null) === 'object-user'
                    && ($payload['productId'] ?? null) === 'PGSOFT'
                    && ($payload['gameCode'] ?? null) === 'treasures-aztec';
            }))
            ->andReturn([
                'success' => false,
                'msg' => 'provider rejected',
            ]);

        $this->app->bind('Gametech\Game\Repositories\Games\SeamlessRepository', static function () use ($driver) {
            return $driver;
        });

        $repository = $this->app->make(GameUserRepository::class);
        $result = $repository->autoLoginSeamlessByGameUser((object) [
            'user_name' => 'object-user',
        ], 'PGSOFT', 'treasures-aztec');

        $this->assertFalse($result['success']);
        $this->assertSame('provider rejected', $result['msg']);
    }

    public function test_query_seamless_bet_records_returns_success_payload(): void
    {
        $repository = $this->app->make(GameUserRepository::class);

        $driver = Mockery::mock();
        $driver->shouldReceive('queryBetRecords')
            ->once()
            ->with(Mockery::on(function (array $payload): bool {
                return ($payload['productId'] ?? null) === 'PGSOFT'
                    && ($payload['nextId'] ?? null) === 'cursor-1';
            }))
            ->andReturn([
                'success' => true,
                'msg' => 'OK',
                'req_id' => 'req-1',
                'next_id' => 'cursor-2',
                'has_next' => true,
                'transactions' => [
                    ['id' => 'txn-1', 'stake' => 100.5, 'payout' => 50.0],
                ],
                'summary' => ['count' => 1, 'stake' => 100.5, 'payout' => 50.0, 'scope' => 'chunk'],
            ]);

        $this->app->bind('Gametech\Game\Repositories\Games\AmbexapiRepository', static function () use ($driver) {
            return $driver;
        });

        $result = $repository->querySeamlessBetRecords([
            'productId' => 'PGSOFT',
            'startTime' => '2026-05-11T10:00:00+07:00',
            'endTime' => '2026-05-11T10:59:59+07:00',
            'nextId' => 'cursor-1',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('req-1', $result['req_id']);
        $this->assertSame('cursor-2', $result['next_id']);
        $this->assertTrue($result['has_next']);
        $this->assertCount(1, $result['transactions']);
    }

    public function test_query_seamless_bet_records_returns_empty_transactions_chunk(): void
    {
        $repository = $this->app->make(GameUserRepository::class);

        $driver = Mockery::mock();
        $driver->shouldReceive('queryBetRecords')
            ->once()
            ->andReturn([
                'success' => true,
                'msg' => 'OK',
                'req_id' => 'req-2',
                'next_id' => null,
                'has_next' => false,
                'transactions' => [],
                'summary' => ['count' => 0, 'stake' => 0.0, 'payout' => 0.0, 'scope' => 'chunk'],
            ]);

        $this->app->bind('Gametech\Game\Repositories\Games\AmbexapiRepository', static function () use ($driver) {
            return $driver;
        });

        $result = $repository->querySeamlessBetRecords([
            'productId' => 'PGSOFT',
            'startTime' => '2026-05-11T10:00:00+07:00',
            'endTime' => '2026-05-11T10:30:00+07:00',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame([], $result['transactions']);
        $this->assertFalse($result['has_next']);
    }

    public function test_query_seamless_bet_records_returns_provider_error_without_throwing(): void
    {
        $repository = $this->app->make(GameUserRepository::class);

        $driver = Mockery::mock();
        $driver->shouldReceive('queryBetRecords')
            ->once()
            ->andReturn([
                'success' => false,
                'msg' => 'เชื่อมต่อไม่ได้',
                'req_id' => null,
                'next_id' => null,
                'has_next' => false,
                'transactions' => [],
                'summary' => ['count' => 0, 'stake' => 0.0, 'payout' => 0.0, 'scope' => 'chunk'],
            ]);

        $this->app->bind('Gametech\Game\Repositories\Games\AmbexapiRepository', static function () use ($driver) {
            return $driver;
        });

        $result = $repository->querySeamlessBetRecords([
            'productId' => 'PGSOFT',
            'startTime' => '2026-05-11T10:00:00+07:00',
            'endTime' => '2026-05-11T10:30:00+07:00',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('เชื่อมต่อไม่ได้', $result['msg']);
        $this->assertSame([], $result['transactions']);
    }

    public function test_query_seamless_bet_records_rejects_range_more_than_one_hour(): void
    {
        $repository = $this->app->make(GameUserRepository::class);

        $result = $repository->querySeamlessBetRecords([
            'productId' => 'PGSOFT',
            'startTime' => '2026-05-11T10:00:00+07:00',
            'endTime' => '2026-05-11T11:01:00+07:00',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('range query must not exceed 1 hour', $result['msg']);
        $this->assertSame([], $result['transactions']);
    }

    private function makeRepositoryWithDriverResult(array $driverResult): GameUserRepository
    {
        $gameRepository = Mockery::mock(GameRepository::class);
        $gameRepository->shouldReceive('findOneByField')
            ->once()
            ->with('code', 1)
            ->andReturn((object) [
                'id' => 'seamless',
                'code' => 1,
            ]);

        $this->app->instance(GameRepository::class, $gameRepository);
        $this->app->instance(GameSeamlessRepository::class, Mockery::mock(GameSeamlessRepository::class));

        $driver = Mockery::mock();
        $driver->shouldReceive('addGameAccount')
            ->once()
            ->andReturn($driverResult);

        $this->app->bind('Gametech\Game\Repositories\Games\SeamlessRepository', static function () use ($driver) {
            return $driver;
        });

        return $this->app->make(GameUserRepository::class);
    }
}
