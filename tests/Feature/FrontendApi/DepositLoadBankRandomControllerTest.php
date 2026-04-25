<?php

namespace Tests\Feature\FrontendApi;

use Gametech\FrontendApi\Http\Controllers\Api\V1\DepositController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DepositLoadBankRandomControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('banks_account');
        Schema::dropIfExists('banks');

        Schema::create('banks', function (Blueprint $table): void {
            $table->unsignedInteger('code')->primary();
            $table->string('name_th')->nullable();
            $table->string('shortcode')->nullable();
            $table->string('filepic')->nullable();
        });

        Schema::create('banks_account', function (Blueprint $table): void {
            $table->unsignedInteger('code')->primary();
            $table->string('acc_no')->nullable();
            $table->string('acc_name')->nullable();
            $table->unsignedInteger('banks')->nullable();
            $table->unsignedTinyInteger('bank_type')->default(1);
            $table->string('enable', 1)->default('Y');
            $table->string('display_wallet', 1)->default('Y');
            $table->string('slip', 1)->default('N');
            $table->string('payment', 1)->default('N');
            $table->string('qrcode', 1)->default('N');
            $table->string('filepic')->nullable();
            $table->unsignedInteger('deposit_min')->default(0);
            $table->string('remark')->nullable();
            $table->string('visibility_scope')->nullable();
            $table->unsignedInteger('sort')->default(0);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('banks_account');
        Schema::dropIfExists('banks');

        parent::tearDown();
    }

    public function test_load_random_bank_returns_one_bank_account_from_available_bank_accounts(): void
    {
        $this->seedBankAccounts();

        $response = $this->loadRandomBank('bank');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertContains($response->json('bank.acc_no'), ['1111111111', '2222222222']);
        $this->assertSame('COMPANY', $response->json('bank.acc_name'));
        $this->assertSame('Kasikorn Bank', $response->json('bank.bank_name'));
        $this->assertIsString($response->json('bank.bank_pic'));
        $this->assertIsString($response->json('bank.qr_pic'));
        $this->assertArrayNotHasKey(0, $response->json('bank'));
    }

    public function test_load_random_bank_returns_empty_payload_when_no_accounts_are_available(): void
    {
        $response = $this->loadRandomBank('bank');

        $response->assertStatus(200);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('bank', '');
    }

    private function seedBankAccounts(): void
    {
        DB::table('banks')->insert([
            'code' => 1,
            'name_th' => 'Kasikorn Bank',
            'shortcode' => 'KBANK',
            'filepic' => 'kbank.png',
        ]);

        DB::table('banks_account')->insert([
            [
                'code' => 101,
                'acc_no' => '1111111111',
                'acc_name' => 'COMPANY',
                'banks' => 1,
                'bank_type' => 1,
                'enable' => 'Y',
                'display_wallet' => 'Y',
                'slip' => 'N',
                'payment' => 'N',
                'qrcode' => 'Y',
                'filepic' => 'qr-1.png',
                'deposit_min' => 100,
                'remark' => 'first',
                'visibility_scope' => null,
                'sort' => 1,
            ],
            [
                'code' => 102,
                'acc_no' => '2222222222',
                'acc_name' => 'COMPANY',
                'banks' => 1,
                'bank_type' => 1,
                'enable' => 'Y',
                'display_wallet' => 'Y',
                'slip' => 'N',
                'payment' => 'N',
                'qrcode' => 'N',
                'filepic' => 'qr-2.png',
                'deposit_min' => 200,
                'remark' => 'second',
                'visibility_scope' => 'all',
                'sort' => 2,
            ],
        ]);
    }

    private function loadRandomBank(string $method): TestResponse
    {
        $request = Request::create('/api/v1/deposit/loadbank/random', 'POST', [
            'method' => $method,
        ]);

        return TestResponse::fromBaseResponse(app(DepositController::class)->loadRandomBank($request));
    }
}
