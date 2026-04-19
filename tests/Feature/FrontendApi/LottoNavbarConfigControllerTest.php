<?php

namespace Tests\Feature\FrontendApi;

use Gametech\FrontendApi\Http\Controllers\Api\V1\LottoNavbarConfigController;
use Gametech\Lotto\Http\Controllers\Admin\LottoNavbarController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LottoNavbarConfigControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        Schema::dropIfExists('logs');
        Schema::dropIfExists('lotto_navbar_items');
        Schema::dropIfExists('lotto_navbars');

        Schema::create('logs', function (Blueprint $table): void {
            $table->bigIncrements('code');
            $table->unsignedBigInteger('emp_code')->default(0);
            $table->string('mode', 16)->nullable();
            $table->string('menu', 191)->nullable();
            $table->string('record', 191)->nullable();
            $table->text('item_before')->nullable();
            $table->text('item')->nullable();
            $table->string('ip', 64)->nullable();
            $table->string('user_create', 191)->nullable();
            $table->dateTime('date_update')->nullable();
            $table->dateTime('date_create')->nullable();
        });

        Schema::create('lotto_navbars', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code', 64);
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('published_version')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_navbar_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('navbar_id');
            $table->string('key', 64);
            $table->string('item_type', 32)->default('normal');
            $table->string('icon_type', 32)->default('preset');
            $table->string('icon')->nullable();
            $table->text('label_json')->nullable();
            $table->string('action_type', 32)->default('route');
            $table->string('action_value', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('logs');
        Schema::dropIfExists('lotto_navbar_items');
        Schema::dropIfExists('lotto_navbars');

        parent::tearDown();
    }

    public function test_default_code_is_mobile_bottom_nav_and_response_has_required_fields(): void
    {
        DB::table('lotto_navbars')->insert([
            'id' => 1,
            'code' => 'mobile_bottom_nav',
            'name' => 'Bottom Nav',
            'is_active' => 1,
            'is_published' => 1,
            'published_version' => 1,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_navbar_items')->insert([
            [
                'navbar_id' => 1,
                'key' => 'home',
                'item_type' => 'normal',
                'icon_type' => 'preset',
                'icon' => 'home',
                'label_json' => json_encode(['th' => 'หน้าแรก', 'en' => 'Home']),
                'action_type' => 'route',
                'action_value' => '/',
                'sort_order' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'navbar_id' => 1,
                'key' => 'ticket',
                'item_type' => 'center_cta',
                'icon_type' => 'emoji',
                'icon' => '🎯',
                'label_json' => json_encode(['th' => 'แทงหวย', 'en' => 'Bet']),
                'action_type' => 'route',
                'action_value' => '/lotto',
                'sort_order' => 2,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $request = Request::create('/api/v1/lotto/navbar-config', 'GET');
        $request->attributes->set('frontend_language', 'en');

        $response = TestResponse::fromBaseResponse(app(LottoNavbarConfigController::class)->show($request));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.navbar.code', 'mobile_bottom_nav');
        $response->assertJsonPath('data.navbar.published_version', 1);
        $response->assertJsonPath('data.navbar.items.0.key', 'home');
        $response->assertJsonPath('data.navbar.items.0.item_type', 'normal');
        $response->assertJsonPath('data.navbar.items.0.icon_type', 'preset');
        $response->assertJsonPath('data.navbar.items.0.icon', 'home');
        $response->assertJsonPath('data.navbar.items.0.label', 'Home');
        $response->assertJsonPath('data.navbar.items.0.label_i18n.th', 'หน้าแรก');
        $response->assertJsonPath('data.navbar.items.0.action_type', 'route');
        $response->assertJsonPath('data.navbar.items.0.action_value', '/');
        $response->assertJsonPath('data.navbar.items.0.sort_order', 1);
        $response->assertJsonPath('data.navbar.items.1.sort_order', 2);
    }

    public function test_locale_fallback_is_requested_then_th_then_en_then_key(): void
    {
        DB::table('lotto_navbars')->insert([
            'id' => 1,
            'code' => 'mobile_bottom_nav',
            'is_active' => 1,
            'is_published' => 1,
            'published_version' => 1,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_navbar_items')->insert([
            [
                'navbar_id' => 1,
                'key' => 'k1',
                'label_json' => json_encode(['th' => 'ไทย', 'en' => 'EN']),
                'sort_order' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'navbar_id' => 1,
                'key' => 'k2',
                'label_json' => json_encode(['en' => 'Only EN']),
                'sort_order' => 2,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'navbar_id' => 1,
                'key' => 'k3',
                'label_json' => json_encode([]),
                'sort_order' => 3,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $request = Request::create('/api/v1/lotto/navbar-config', 'GET');
        $request->attributes->set('frontend_language', 'la');

        $response = TestResponse::fromBaseResponse(app(LottoNavbarConfigController::class)->show($request));

        $response->assertStatus(200);
        $response->assertJsonPath('data.navbar.items.0.label', 'ไทย');
        $response->assertJsonPath('data.navbar.items.1.label', 'Only EN');
        $response->assertJsonPath('data.navbar.items.2.label', 'k3');
    }

    public function test_default_code_returns_404_when_unpublished_or_missing(): void
    {
        DB::table('lotto_navbars')->insert([
            'id' => 1,
            'code' => 'mobile_bottom_nav',
            'is_active' => 0,
            'is_published' => 1,
            'published_version' => 1,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/lotto/navbar-config', 'GET');
        $request->attributes->set('frontend_language', 'th');

        $response = TestResponse::fromBaseResponse(app(LottoNavbarConfigController::class)->show($request));

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'ไม่พบ navbar config ที่เผยแพร่แล้ว');
    }

    public function test_draft_update_does_not_impact_public_until_publish_and_version_increments_monotonic(): void
    {
        DB::table('lotto_navbars')->insert([
            [
                'id' => 10,
                'code' => 'mobile_bottom_nav',
                'name' => 'Draft',
                'is_active' => 1,
                'is_published' => 0,
                'published_version' => null,
                'published_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'code' => 'mobile_bottom_nav',
                'name' => 'Published V1',
                'is_active' => 1,
                'is_published' => 1,
                'published_version' => 1,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('lotto_navbar_items')->insert([
            [
                'id' => 1,
                'navbar_id' => 10,
                'key' => 'ticket',
                'label_json' => json_encode(['th' => 'Draft V1']),
                'sort_order' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'navbar_id' => 11,
                'key' => 'ticket',
                'label_json' => json_encode(['th' => 'Live V1']),
                'sort_order' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('lotto_navbar_items')
            ->where('id', 1)
            ->update([
                'label_json' => json_encode(['th' => 'Draft Changed']),
                'updated_at' => now(),
            ]);

        $requestBefore = Request::create('/api/v1/lotto/navbar-config', 'GET');
        $requestBefore->attributes->set('frontend_language', 'th');
        $responseBefore = TestResponse::fromBaseResponse(app(LottoNavbarConfigController::class)->show($requestBefore));

        $responseBefore->assertStatus(200);
        $responseBefore->assertJsonPath('data.navbar.published_version', 1);
        $responseBefore->assertJsonPath('data.navbar.items.0.label', 'Live V1');

        $publishRequest = Request::create('/admin/lotto/navbar-configs/publish', 'POST', ['id' => 10]);
        $publishResponse = TestResponse::fromBaseResponse(app(LottoNavbarController::class)->publish($publishRequest));
        $publishResponse->assertStatus(200);

        $requestAfter = Request::create('/api/v1/lotto/navbar-config', 'GET');
        $requestAfter->attributes->set('frontend_language', 'th');
        $responseAfter = TestResponse::fromBaseResponse(app(LottoNavbarConfigController::class)->show($requestAfter));

        $responseAfter->assertStatus(200);
        $responseAfter->assertJsonPath('data.navbar.published_version', 2);
        $responseAfter->assertJsonPath('data.navbar.items.0.label', 'Draft Changed');
    }
}
