<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Http\Controllers\Admin\LottoResultSourceController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminLottoResultSourceYeekeeGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('result_mode', 32)->default('normal');
        });

        Schema::create('lotto_result_sources', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(1);
            $table->string('source_type', 16)->default('api');
            $table->string('endpoint_url', 2048)->nullable();
            $table->string('http_method', 8)->default('GET');
            $table->string('lookup_date_mode', 64)->default('ROUND_DATE');
            $table->integer('lookup_date_offset_days')->default(0);
            $table->string('parser_type', 64)->default('JSON_PATH');
            $table->unsignedInteger('timeout_seconds')->default(10);
            $table->timestamps();
        });

        Schema::create('logs', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedBigInteger('emp_code')->nullable();
            $table->string('mode', 16)->nullable();
            $table->string('menu', 128)->nullable();
            $table->unsignedBigInteger('record')->nullable();
            $table->text('item_before')->nullable();
            $table->text('item')->nullable();
            $table->string('ip', 64)->nullable();
            $table->string('user_create', 64)->nullable();
            $table->dateTime('date_update')->nullable();
            $table->dateTime('date_create')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_result_sources');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('logs');

        parent::tearDown();
    }

    public function test_save_source_rejects_yeekee_market(): void
    {
        \DB::table('lotto_markets')->insert([
            'id' => 1,
            'name' => 'Yeekee',
            'code' => 'yeekee',
            'result_mode' => 'yeekee',
        ]);

        $request = Request::create('/admin/lotto/result-sources/save', 'POST', [
            'data' => [
                'market_id' => 1,
                'is_active' => true,
                'priority' => 1,
                'source_type' => 'api',
                'endpoint_url' => 'https://example.test/result',
                'http_method' => 'GET',
                'lookup_date_mode' => 'ROUND_DATE',
                'lookup_date_offset_days' => 0,
                'parser_type' => 'JSON_PATH',
                'timeout_seconds' => 10,
            ],
        ]);

        $response = $this->createTestResponse(
            app(LottoResultSourceController::class)->save($request)
        );

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'รายการหวยยี่กี่ไม่รองรับ Auto Result Source ของหวยปกติ');
    }
}
