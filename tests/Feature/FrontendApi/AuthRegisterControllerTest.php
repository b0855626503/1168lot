<?php

namespace Tests\Feature\FrontendApi;

use Gametech\Core\Core;
use Gametech\FrontendApi\Http\Controllers\Api\V1\AuthController;
use Gametech\FrontendApi\Services\FrontendTokenService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class AuthRegisterControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('banks_account');
        Schema::dropIfExists('members');

        Schema::create('banks_account', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('acc_no')->nullable();
        });

        Schema::create('members', function (Blueprint $table): void {
            $table->bigIncrements('code');
            $table->integer('bank_code')->nullable();
            $table->string('acc_no')->nullable();
            $table->string('user_name')->nullable();
            $table->string('wallet_id')->nullable();
            $table->string('tel')->nullable();
            $table->string('referral_code')->nullable();
        });

        Event::fake();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('banks_account');
        Schema::dropIfExists('members');

        Mockery::close();

        parent::tearDown();
    }

    public function test_register_returns_specific_error_when_member_creation_fails(): void
    {
        $this->mockCoreConfig('N');

        $memberRepo = Mockery::mock();
        $memberRepo->shouldReceive('create')
            ->once()
            ->andThrow(new \RuntimeException('db unavailable'));
        $memberRepo->shouldNotReceive('delete');
        $this->app->instance('Gametech\Member\Repositories\MemberRepository', $memberRepo);

        $response = $this->register($this->validPayload());

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'ไม่สามารถสร้างข้อมูลสมาชิกได้ในขณะนี้');
        $response->assertJsonPath('error_code', 'REGISTER_MEMBER_CREATE_FAILED');
        $response->assertJsonPath('details.stage', 'member_create');
    }

    public function test_register_returns_specific_error_when_seamless_game_connection_fails(): void
    {
        $this->mockCoreConfig('Y');

        $memberRepo = Mockery::mock();
        $memberRepo->shouldReceive('create')
            ->once()
            ->andReturn((object) ['code' => 9876]);
        $memberRepo->shouldReceive('delete')
            ->once()
            ->with(9876)
            ->andReturnTrue();
        $this->app->instance('Gametech\Member\Repositories\MemberRepository', $memberRepo);

        $gameRepo = Mockery::mock();
        $gameRepo->shouldReceive('findOneWhere')
            ->once()
            ->with([
                'enable' => 'Y',
                'status_open' => 'Y',
                'id' => 'seamless',
            ])
            ->andReturn((object) ['code' => 1, 'id' => 'seamless']);
        $this->app->instance('Gametech\Game\Repositories\GameRepository', $gameRepo);

        $gameUserRepo = Mockery::mock();
        $gameUserRepo->shouldReceive('addGameUser')
            ->once()
            ->andReturn([
                'success' => false,
                'msg' => 'เชื่อมต่อไม่ได้',
            ]);
        $this->app->instance('Gametech\Game\Repositories\GameUserRepository', $gameUserRepo);

        $response = $this->register($this->validPayload('0899999999'));

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'ไม่สามารถเชื่อมต่อระบบเกมเพื่อสร้างบัญชีได้ในขณะนี้');
        $response->assertJsonPath('error_code', 'REGISTER_GAME_ACCOUNT_CONNECT_FAILED');
        $response->assertJsonPath('details.stage', 'game_account_create');
        $response->assertJsonPath('details.reason', 'connect_failed');
        $response->assertJsonPath('details.upstream_message', 'เชื่อมต่อไม่ได้');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function register(array $payload): TestResponse
    {
        $request = Request::create('/api/v1/auth/register', 'POST', $payload);

        return TestResponse::fromBaseResponse(
            (new AuthController(Mockery::mock(FrontendTokenService::class)))->register($request)
        );
    }

    private function mockCoreConfig(string $seamless): void
    {
        $core = Mockery::mock(Core::class);
        $core->shouldReceive('getConfigData')
            ->andReturn((object) [
                'seamless' => $seamless,
                'verify_open' => 'N',
                'freecredit_all' => 'N',
            ]);

        $this->app->instance(Core::class, $core);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(string $userName = '0888888888'): array
    {
        return [
            'user_name' => $userName,
            'password' => 'test123',
            'password_confirm' => 'test123',
            'name' => 'Api Test',
            'acc_no' => '123456789012',
            'bank' => 1,
            'refer' => 1,
        ];
    }
}
