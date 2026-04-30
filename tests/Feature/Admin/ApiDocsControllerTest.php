<?php

namespace Tests\Feature\Admin;

use Gametech\Admin\Http\Controllers\ApiDocsController;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiDocsControllerTest extends TestCase
{
    public function test_frontend_api_v1_view_uses_frontend_v1_index_entrypoint(): void
    {
        $request = Request::create('/admin/docs/api/frontend-v1', 'GET', [
            '_config' => [
                'view' => 'admin::module.docs.frontend_api_v1',
            ],
        ]);
        $this->app->instance('request', $request);

        $response = app(ApiDocsController::class)->frontendApiV1();
        $rendered = $response->render();

        $this->assertStringContainsString('Frontend API V1 - Start Here', $rendered);
        $this->assertStringContainsString('/docs/api/frontend-v1/01-quick-start', $rendered);
        $this->assertStringContainsString('/docs/api/frontend-v1/07-route-reference', $rendered);
    }

    public function test_frontend_api_v1_raw_returns_frontend_v1_index_entrypoint(): void
    {
        $request = Request::create('/admin/docs/api/frontend-v1/raw', 'GET', [
            '_config' => [
                'view' => 'admin::module.docs.frontend_api_v1',
            ],
        ]);
        $this->app->instance('request', $request);

        $response = app(ApiDocsController::class)->frontendApiV1Raw();
        $content = $response->getContent();

        $this->assertStringContainsString('# Frontend API V1 - Start Here', $content);
        $this->assertStringContainsString('/docs/api/frontend-v1/01-quick-start', $content);
        $this->assertStringContainsString('/docs/api/frontend-v1/07-route-reference', $content);
    }

    public function test_frontend_api_v1_chapter_view_returns_markdown_content(): void
    {
        $request = Request::create('/admin/docs/api/frontend-v1/01-quick-start', 'GET', [
            '_config' => [
                'view' => 'admin::module.docs.frontend_api_v1',
            ],
        ]);
        $this->app->instance('request', $request);

        $response = app(ApiDocsController::class)->frontendApiV1Chapter('01-quick-start');
        $rendered = $response->render();

        $this->assertStringContainsString('Frontend API V1 - 01-quick-start', $rendered);
        $this->assertStringContainsString('POST /api/v1/auth/login', $rendered);
    }

    public function test_frontend_api_v1_chapter_raw_returns_markdown_content(): void
    {
        $request = Request::create('/admin/docs/api/frontend-v1/01-quick-start/raw', 'GET', [
            '_config' => [
                'view' => 'admin::module.docs.frontend_api_v1',
            ],
        ]);
        $this->app->instance('request', $request);

        $response = app(ApiDocsController::class)->frontendApiV1ChapterRaw('01-quick-start');
        $content = $response->getContent();

        $this->assertStringContainsString('# Frontend API V1 - Quick Start', $content);
        $this->assertStringContainsString('POST /api/v1/auth/login', $content);
    }
}
