<?php

namespace Tests\Feature\FrontendApi;

use Gametech\FrontendApi\Http\Controllers\Api\V1\LottoController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LottoMarketContentControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('lotto_market_contents');
        Schema::dropIfExists('lotto_markets');

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('group_id')->nullable();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('name_kh')->nullable();
            $table->string('name_laos')->nullable();
            $table->string('logo')->nullable();
            $table->string('icon')->nullable();
            $table->string('code')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->string('result_mode')->default('normal');
        });

        Schema::create('lotto_market_contents', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->string('locale', 10);
            $table->string('title')->nullable();
            $table->text('summary')->nullable();
            $table->longText('rules_content')->nullable();
            $table->longText('schedule_content')->nullable();
            $table->longText('prize_content')->nullable();
            $table->longText('formula_content')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_market_contents');
        Schema::dropIfExists('lotto_markets');

        parent::tearDown();
    }

    public function test_market_content_returns_404_when_market_not_found(): void
    {
        $request = Request::create('/api/v1/lotto/markets/999/content', 'GET');

        $response = TestResponse::fromBaseResponse(app(LottoController::class)->marketContent($request, 999));

        $response->assertStatus(404);
    }

    public function test_market_content_returns_hit_for_normalized_locale(): void
    {
        DB::table('lotto_markets')->insert([
            'id' => 10,
            'name' => 'Market A',
            'code' => 'market_a',
            'is_enabled' => 1,
            'result_mode' => 'normal',
        ]);

        DB::table('lotto_market_contents')->insert([
            'market_id' => 10,
            'locale' => 'km',
            'title' => 'ខ្មែរ',
            'summary' => 'คำอธิบาย',
            'is_enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/lotto/markets/10/content', 'GET', ['language' => 'khmer']);
        $response = TestResponse::fromBaseResponse(app(LottoController::class)->marketContent($request, 10));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.locale', 'km');
        $response->assertJsonPath('data.fallback_locale', null);
        $response->assertJsonPath('data.content.title', 'ខ្មែរ');
    }

    public function test_market_content_falls_back_to_th_locale(): void
    {
        DB::table('lotto_markets')->insert([
            'id' => 11,
            'name' => 'Market B',
            'code' => 'market_b',
            'is_enabled' => 1,
            'result_mode' => 'normal',
        ]);

        DB::table('lotto_market_contents')->insert([
            'market_id' => 11,
            'locale' => 'th',
            'title' => 'ไทย',
            'is_enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/lotto/markets/11/content', 'GET', ['language' => 'en']);
        $response = TestResponse::fromBaseResponse(app(LottoController::class)->marketContent($request, 11));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.locale', 'en');
        $response->assertJsonPath('data.fallback_locale', 'th');
        $response->assertJsonPath('data.content.title', 'ไทย');
    }

    public function test_market_content_returns_empty_object_when_no_content_exists(): void
    {
        DB::table('lotto_markets')->insert([
            'id' => 12,
            'name' => 'Market C',
            'code' => 'market_c',
            'is_enabled' => 1,
            'result_mode' => 'normal',
        ]);

        $request = Request::create('/api/v1/lotto/markets/12/content', 'GET', ['language' => 'laos']);
        $response = TestResponse::fromBaseResponse(app(LottoController::class)->marketContent($request, 12));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.locale', 'lo');
        $response->assertJsonPath('data.fallback_locale', null);
        $response->assertJsonPath('data.content.title', null);
        $response->assertJsonPath('data.content.rules_content', null);
    }
}
