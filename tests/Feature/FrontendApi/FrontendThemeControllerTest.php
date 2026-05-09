<?php

namespace Tests\Feature\FrontendApi;

use Gametech\FrontendApi\Http\Controllers\Api\V1\FrontendThemeController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class FrontendThemeControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Schema::dropIfExists('lotto_frontend_theme_settings');
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
        Schema::dropIfExists('lotto_frontend_theme_settings');
        parent::tearDown();
    }

    public function test_public_theme_response_has_stable_shape_with_complete_tokens(): void
    {
        DB::table('lotto_frontend_theme_settings')->insert([
            'singleton_key' => 'default',
            'preset_key' => 'midnight',
            'tokens' => json_encode([
                'surface-subtle' => '#1e293b',
                'surface-card' => '#0f172a',
                'surface-page' => '#020617',
                'surface-navbar' => 'rgba(15,23,42,0.92)',
                'surface-highlight' => '#f59e0b',
                'brand-primary' => '#fbbf24',
                'brand-primary-hover' => '#fcd34d',
                'text-strong' => '#f8fafc',
                'text-default' => '#cbd5e1',
                'text-muted' => '#94a3b8',
                'border-default' => 'rgba(241,245,249,0.12)',
                'status-error' => '#f87171',
                'status-success' => '#34d399',
                'status-warning' => '#fbbf24',
            ]),
            'custom_tokens' => json_encode([]),
            'is_customized' => 0,
            'version' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = TestResponse::fromBaseResponse(app(FrontendThemeController::class)->show());

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.preset_key', 'midnight');
        $response->assertJsonPath('data.version', 7);
        $response->assertJsonPath('data.tokens.surface-subtle', '#1e293b');
        $response->assertJsonPath('data.tokens.status-warning', '#fbbf24');
    }
}
