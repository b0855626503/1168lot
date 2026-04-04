<?php

namespace Tests\Feature\Game;

use Gametech\Game\Repositories\GameRepository;
use Gametech\Game\Repositories\GameSeamlessRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
