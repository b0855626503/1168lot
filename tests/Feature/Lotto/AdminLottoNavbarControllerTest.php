<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Http\Controllers\Admin\LottoNavbarController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminLottoNavbarControllerTest extends TestCase
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

    public function test_create_rejects_duplicate_sort_order_for_active_items(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('sort_order ของ active item ห้ามซ้ำกัน');

        $request = Request::create('/admin/lotto/navbar-configs/create', 'POST', [
            'data' => [
                'code' => 'mobile_bottom_nav',
                'name' => 'Navbar',
                'is_active' => true,
                'items' => [
                    [
                        'key' => 'a',
                        'item_type' => 'normal',
                        'icon_type' => 'preset',
                        'icon' => 'home',
                        'label_i18n' => ['th' => 'A'],
                        'action_type' => 'route',
                        'action_value' => '/a',
                        'sort_order' => 1,
                        'is_active' => true,
                    ],
                    [
                        'key' => 'b',
                        'item_type' => 'normal',
                        'icon_type' => 'preset',
                        'icon' => 'ticket',
                        'label_i18n' => ['th' => 'B'],
                        'action_type' => 'route',
                        'action_value' => '/b',
                        'sort_order' => 1,
                        'is_active' => true,
                    ],
                ],
            ],
        ]);

        app(LottoNavbarController::class)->create($request);
    }

    public function test_update_rejects_published_row_direct_edit(): void
    {
        DB::table('lotto_navbars')->insert([
            'id' => 1,
            'code' => 'mobile_bottom_nav',
            'name' => 'Published',
            'is_active' => 1,
            'is_published' => 1,
            'published_version' => 1,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/admin/lotto/navbar-configs/update', 'POST', [
            'id' => 1,
            'data' => [
                'code' => 'mobile_bottom_nav',
                'name' => 'changed',
                'items' => [],
            ],
        ]);

        $response = TestResponse::fromBaseResponse(app(LottoNavbarController::class)->update($request));

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_publish_bumps_monotonic_version_per_code(): void
    {
        DB::table('lotto_navbars')->insert([
            [
                'id' => 1,
                'code' => 'mobile_bottom_nav',
                'name' => 'Draft 1',
                'is_active' => 1,
                'is_published' => 0,
                'published_version' => null,
                'published_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'code' => 'mobile_bottom_nav',
                'name' => 'Old Published',
                'is_active' => 1,
                'is_published' => 1,
                'published_version' => 3,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('lotto_navbar_items')->insert([
            'navbar_id' => 1,
            'key' => 'ticket',
            'item_type' => 'normal',
            'icon_type' => 'preset',
            'icon' => 'ticket',
            'label_json' => json_encode(['th' => 'แทงหวย']),
            'action_type' => 'route',
            'action_value' => '/lotto',
            'sort_order' => 1,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/admin/lotto/navbar-configs/publish', 'POST', ['id' => 1]);
        $response = TestResponse::fromBaseResponse(app(LottoNavbarController::class)->publish($request));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $newPublished = DB::table('lotto_navbars')
            ->where('code', 'mobile_bottom_nav')
            ->where('is_published', 1)
            ->where('is_active', 1)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($newPublished);
        $this->assertSame(4, (int) $newPublished->published_version);

        $activePublishedCount = DB::table('lotto_navbars')
            ->where('code', 'mobile_bottom_nav')
            ->where('is_published', 1)
            ->where('is_active', 1)
            ->count();

        $this->assertSame(1, $activePublishedCount);
    }

    public function test_create_rejects_emoji_icon_type_when_icon_is_not_emoji(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('icon_type=emoji ต้องเป็น emoji จริง');

        $request = Request::create('/admin/lotto/navbar-configs/create', 'POST', [
            'data' => [
                'code' => 'mobile_bottom_nav',
                'name' => 'Navbar',
                'is_active' => true,
                'items' => [
                    [
                        'key' => 'home',
                        'item_type' => 'normal',
                        'icon_type' => 'emoji',
                        'icon' => 'home',
                        'label_i18n' => ['th' => 'หน้าแรก'],
                        'action_type' => 'route',
                        'action_value' => '/',
                        'sort_order' => 1,
                        'is_active' => true,
                    ],
                ],
            ],
        ]);

        app(LottoNavbarController::class)->create($request);
    }

    public function test_loaddata_returns_published_snapshot_for_same_code(): void
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
                'name' => 'Published',
                'is_active' => 1,
                'is_published' => 1,
                'published_version' => 2,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('lotto_navbar_items')->insert([
            [
                'navbar_id' => 10,
                'key' => 'home',
                'item_type' => 'normal',
                'icon_type' => 'preset',
                'icon' => 'home',
                'label_json' => json_encode(['th' => 'หน้าหลัก']),
                'action_type' => 'route',
                'action_value' => '/',
                'sort_order' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'navbar_id' => 11,
                'key' => 'wallet',
                'item_type' => 'normal',
                'icon_type' => 'preset',
                'icon' => 'wallet',
                'label_json' => json_encode(['th' => 'กระเป๋า']),
                'action_type' => 'route',
                'action_value' => '/wallet',
                'sort_order' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $request = Request::create('/admin/lotto/navbar-configs/loaddata', 'POST', ['id' => 10]);
        $response = TestResponse::fromBaseResponse(app(LottoNavbarController::class)->loadData($request));

        $response->assertStatus(200);
        $response->assertJsonPath('data.code', 'mobile_bottom_nav');
        $response->assertJsonPath('data.published_snapshot.published_version', 2);
        $response->assertJsonPath('data.published_snapshot.items.0.key', 'wallet');
    }
}
