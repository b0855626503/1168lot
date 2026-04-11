<?php

namespace Tests\Feature\FrontendApi;

use Gametech\FrontendApi\Http\Controllers\Api\V1\AuthController;
use Gametech\FrontendApi\Http\Requests\ResolveRegisterBankAccountRequest;
use Gametech\FrontendApi\Services\FrontendTokenService;
use Gametech\FrontendApi\Services\RegisterBankAccountNameService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class AuthRegisterBankAccountControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('banks_account');
        Schema::dropIfExists('members');

        Schema::create('banks_account', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->integer('banks')->nullable();
            $table->string('acc_no')->nullable();
        });

        Schema::create('members', function (Blueprint $table): void {
            $table->bigIncrements('code');
            $table->integer('bank_code')->nullable();
            $table->string('acc_no')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('banks_account');
        Schema::dropIfExists('members');

        Mockery::close();

        parent::tearDown();
    }

    public function test_resolve_register_bank_account_returns_account_name_payload(): void
    {
        $service = Mockery::mock(RegisterBankAccountNameService::class);
        $service->shouldReceive('resolve')
            ->once()
            ->with(2, '1234567890')
            ->andReturn([
                'account_name' => 'สมชาย ใจดี',
                'firstname' => 'สมชาย',
                'lastname' => 'ใจดี',
                'bank_shortcode' => 'KBANK',
            ]);

        $request = $this->makeValidatedRequest([
            'bank' => 2,
            'acc_no' => '1234567890',
        ]);

        $response = TestResponse::fromBaseResponse(
            (new AuthController(
                Mockery::mock(FrontendTokenService::class),
                $service
            ))->resolveRegisterBankAccount($request)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.valid', false);
        $response->assertJsonPath('data.bank', 2);
        $response->assertJsonPath('data.acc_no', '1234567890');
        $response->assertJsonPath('data.bank_shortcode', 'KBANK');
        $response->assertJsonPath('data.account_name', 'สมชาย ใจดี');
        $response->assertJsonPath('data.firstname', 'สมชาย');
        $response->assertJsonPath('data.lastname', 'ใจดี');
    }

    public function test_resolve_register_bank_account_request_rejects_duplicate_member_account_for_same_bank(): void
    {
        DB::table('members')->insert([
            'bank_code' => 4,
            'acc_no' => '9990001112',
        ]);

        $request = ResolveRegisterBankAccountRequest::create(
            '/api/v1/auth/register/bank-account-name',
            'POST',
            [
                'bank' => 4,
                'acc_no' => '9990001112',
            ]
        );

        $validator = Validator::make(
            [
                'bank' => 4,
                'acc_no' => '9990001112',
            ],
            $request->rules(),
            $request->messages()
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            ['เลขที่บัญชีนี้ถูกใช้งานแล้วในระบบสมาชิก'],
            $validator->errors()->get('acc_no')
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function makeValidatedRequest(array $payload): ResolveRegisterBankAccountRequest
    {
        $request = ResolveRegisterBankAccountRequest::create(
            '/api/v1/auth/register/bank-account-name',
            'POST',
            $payload
        );
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));
        $request->validateResolved();

        return $request;
    }
}
