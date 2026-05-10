<?php

namespace Tests\Feature\Lotto;

use Gametech\Admin\Bouncer as AdminBouncer;
use Gametech\Lotto\Http\Controllers\Admin\LottoFrontendThemeSettingController;
use Gametech\Lotto\Services\LottoFrontendThemeSettingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminLottoFrontendThemeSettingControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->mockBouncerPermission(true);
        Schema::dropIfExists('logs');
        Schema::dropIfExists('lotto_frontend_theme_settings');
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
        Schema::create('lotto_frontend_theme_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('singleton_key', 32)->default('default');
            $table->string('preset_key', 32);
            $table->text('tokens');
            $table->text('custom_tokens')->nullable();
            $table->boolean('is_customized')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('logs');
        Schema::dropIfExists('lotto_frontend_theme_settings');
        parent::tearDown();
    }

    public function test_it_creates_singleton_with_default_preset(): void
    {
        $payload = app(LottoFrontendThemeSettingService::class)->formatForAdminResponse();

        $this->assertSame('default', $payload['preset_key']);
        $this->assertArrayHasKey('surface-subtle', $payload['tokens']);
        $this->assertSame('default', $payload['presets'][0]['key'] ?? null);
    }

    public function test_update_rejects_invalid_color_string(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('รองรับเฉพาะ hex, rgb(), rgba()');

        $request = Request::create('/admin/lotto/frontend-theme/update', 'POST', [
            'data' => [
                'preset_key' => 'midnight',
                'custom_tokens' => [
                    'surface-card' => 'var(--evil)',
                ],
            ],
        ]);

        app(LottoFrontendThemeSettingController::class)->update($request);
    }

    public function test_update_increments_version_and_clears_cache(): void
    {
        Cache::put(LottoFrontendThemeSettingService::CACHE_KEY, ['cached' => true], 60);

        $request = Request::create('/admin/lotto/frontend-theme/update', 'POST', [
            'data' => [
                'preset_key' => 'forest',
                'custom_tokens' => [
                    'brand-primary' => '#15803d',
                ],
            ],
        ]);

        $response = TestResponse::fromBaseResponse(app(LottoFrontendThemeSettingController::class)->update($request));
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.version', 2);
        $this->assertNull(Cache::get(LottoFrontendThemeSettingService::CACHE_KEY));
    }

    public function test_update_returns_403_when_user_has_no_update_permission(): void
    {
        $this->mockBouncerPermission(false);

        $request = Request::create('/admin/lotto/frontend-theme/update', 'POST', [
            'data' => [
                'preset_key' => 'midnight',
                'custom_tokens' => [],
            ],
        ]);

        $response = TestResponse::fromBaseResponse(app(LottoFrontendThemeSettingController::class)->update($request));
        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }

    private function mockBouncerPermission(bool $allowed): void
    {
        app()->instance(AdminBouncer::class, new class($allowed)
        {
            public function __construct(private bool $allowed) {}

            public function hasPermission(string $key): bool
            {
                return $this->allowed;
            }
        });
    }
}
