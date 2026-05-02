<?php

namespace Tests\Feature\FrontendApi;

use Gametech\Core\Core;
use Gametech\FrontendApi\Http\Controllers\Api\V1\AuthController;
use Gametech\FrontendApi\Services\FrontendTokenService;
use Gametech\FrontendApi\Services\RegisterBankAccountNameService;
use Gametech\Game\Repositories\GameRepository;
use Gametech\Marketing\Services\MarketingClickTrackingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
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
        Schema::dropIfExists('registration_link_clicks');

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

        Schema::create('registration_link_clicks', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('converted_member_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->string('register_type')->nullable();
        });

        Event::fake();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('banks_account');
        Schema::dropIfExists('members');
        Schema::dropIfExists('registration_link_clicks');

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

        $response = $this->register($this->validPayload('0899999999', '0899999999'));

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'ไม่สามารถเชื่อมต่อระบบเกมเพื่อสร้างบัญชีได้ในขณะนี้');
        $response->assertJsonPath('error_code', 'REGISTER_GAME_ACCOUNT_CONNECT_FAILED');
        $response->assertJsonPath('details.stage', 'game_account_create');
        $response->assertJsonPath('details.reason', 'connect_failed');
        $response->assertJsonPath('details.upstream_message', 'เชื่อมต่อไม่ได้');
    }

    public function test_register_requires_username_not_to_be_phone_number(): void
    {
        $this->mockCoreConfig('N');

        $response = $this->registerWithUsername($this->validPayloadWithUsername('0899999999', '0899999999'));

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'ข้อมูลสมัครสมาชิกไม่ถูกต้อง');
        $response->assertJsonPath('error_fields.0', 'user_name');
    }

    public function test_register_seamless_success_uses_info_level_trace_logs(): void
    {
        $this->mockCoreConfig('Y');

        Log::spy();

        $memberRepo = Mockery::mock();
        $memberRepo->shouldReceive('create')
            ->once()
            ->andReturn((object) ['code' => 9876]);
        $this->app->instance('Gametech\Member\Repositories\MemberRepository', $memberRepo);

        $gameRepo = Mockery::mock();
        $gameRepo->shouldReceive('findOneWhere')
            ->once()
            ->andReturn((object) ['code' => 1, 'id' => 'seamless']);
        $this->app->instance('Gametech\Game\Repositories\GameRepository', $gameRepo);

        $gameUserRepo = Mockery::mock();
        $gameUserRepo->shouldReceive('addGameUser')
            ->once()
            ->andReturn([
                'success' => true,
                'msg' => 'ok',
            ]);
        $this->app->instance('Gametech\Game\Repositories\GameUserRepository', $gameUserRepo);

        $response = $this->register($this->validPayload('0899999999', '0899999999'));

        $response->assertOk();
        $response->assertJsonPath('success', true);

        Log::shouldHaveReceived('info')->with('frontend_api_register.entry', Mockery::type('array'))->once();
        Log::shouldHaveReceived('info')->with('frontend_api_register.seamless_branch_entered', Mockery::type('array'))->once();
        Log::shouldHaveReceived('info')->with('frontend_api_register.seamless_game_lookup', Mockery::type('array'))->once();
        Log::shouldHaveReceived('info')->with('frontend_api_register.seamless_add_game_user_result', Mockery::type('array'))->once();
        Log::shouldNotHaveReceived('error', ['frontend_api_register.entry', Mockery::type('array')]);
        Log::shouldNotHaveReceived('error', ['frontend_api_register.seamless_branch_entered', Mockery::type('array')]);
        Log::shouldNotHaveReceived('error', ['frontend_api_register.seamless_game_lookup', Mockery::type('array')]);
        Log::shouldNotHaveReceived('error', ['frontend_api_register.seamless_add_game_user_result', Mockery::type('array')]);
    }

    public function test_register_marks_conversion_when_click_id_is_valid(): void
    {
        $this->mockCoreConfig('N');
        $this->mockNonSeamlessGameLookup();

        $memberRepo = Mockery::mock();
        $memberRepo->shouldReceive('create')
            ->once()
            ->andReturn((object) ['code' => 9876]);
        $this->app->instance('Gametech\Member\Repositories\MemberRepository', $memberRepo);

        $trackingService = Mockery::mock(MarketingClickTrackingService::class);
        $trackingService->shouldReceive('markConverted')
            ->once()
            ->with(11, '9876', 'phone', 'visitor-123')
            ->andReturnTrue();

        $response = $this->register(array_merge($this->validPayload(), [
            'click_id' => 11,
            'visitor_id' => 'visitor-123',
            'registration_code' => 'CODE-OPTIONAL',
        ]), $trackingService);

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }

    public function test_register_success_when_click_id_is_invalid(): void
    {
        $this->mockCoreConfig('N');
        $this->mockNonSeamlessGameLookup();
        Log::spy();

        $memberRepo = Mockery::mock();
        $memberRepo->shouldReceive('create')
            ->once()
            ->andReturn((object) ['code' => 1234]);
        $this->app->instance('Gametech\Member\Repositories\MemberRepository', $memberRepo);

        $trackingService = Mockery::mock(MarketingClickTrackingService::class);
        $trackingService->shouldReceive('markConverted')
            ->once()
            ->andThrow(new \RuntimeException('invalid click'));

        $response = $this->register(array_merge($this->validPayload(), [
            'click_id' => 'bad-id',
            'visitor_id' => 'visitor-xyz',
        ]), $trackingService);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        Log::shouldHaveReceived('warning')
            ->with('frontend_api_register.click_conversion_failed', Mockery::type('array'))
            ->once();
    }

    public function test_register_without_click_id_does_not_attempt_conversion(): void
    {
        $this->mockCoreConfig('N');
        $this->mockNonSeamlessGameLookup();

        $memberRepo = Mockery::mock();
        $memberRepo->shouldReceive('create')
            ->once()
            ->andReturn((object) ['code' => 5555]);
        $this->app->instance('Gametech\Member\Repositories\MemberRepository', $memberRepo);

        $trackingService = Mockery::mock(MarketingClickTrackingService::class);
        $trackingService->shouldNotReceive('markConverted');

        $response = $this->register($this->validPayload(), $trackingService);

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function register(array $payload, ?MarketingClickTrackingService $clickTrackingService = null): TestResponse
    {
        $request = Request::create('/api/v1/auth/register', 'POST', $payload);

        return TestResponse::fromBaseResponse(
            (new AuthController(
                Mockery::mock(FrontendTokenService::class),
                Mockery::mock(RegisterBankAccountNameService::class),
                $clickTrackingService
            ))->register($request)
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function registerWithUsername(array $payload, ?MarketingClickTrackingService $clickTrackingService = null): TestResponse
    {
        $request = Request::create('/api/v1/auth/register-with-username', 'POST', $payload);

        return TestResponse::fromBaseResponse(
            (new AuthController(
                Mockery::mock(FrontendTokenService::class),
                Mockery::mock(RegisterBankAccountNameService::class),
                $clickTrackingService
            ))->registerWithUsername($request)
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

    private function mockNonSeamlessGameLookup(): void
    {
        $gameRepo = Mockery::mock(GameRepository::class);
        $gameRepo->shouldReceive('findOneWhere')
            ->once()
            ->with([
                'enable' => 'Y',
                'status_open' => 'Y',
            ])
            ->andReturn(null);
        $this->app->instance(GameRepository::class, $gameRepo);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(string $userName = '0888888888', string $tel = '0888888888'): array
    {
        return [
            'user_name' => $userName,
            'tel' => $tel,
            'password' => 'test123',
            'password_confirm' => 'test123',
            'name' => 'Api Test',
            'acc_no' => '123456789012',
            'bank' => 1,
            'refer' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayloadWithUsername(string $userName = 'member88', string $tel = '0888888888'): array
    {
        return [
            'user_name' => $userName,
            'tel' => $tel,
            'password' => 'test123',
            'password_confirm' => 'test123',
            'name' => 'Api Test',
            'acc_no' => '123456789012',
            'bank' => 1,
            'refer' => 1,
        ];
    }
}
