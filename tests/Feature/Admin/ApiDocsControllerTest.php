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

        $this->assertStringContainsString('# Frontend API V1 - Start Here', $rendered);
        $this->assertStringContainsString('./01-quick-start.md', $rendered);
        $this->assertStringContainsString('./07-route-reference.md', $rendered);
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
        $this->assertStringContainsString('./01-quick-start.md', $content);
        $this->assertStringContainsString('./07-route-reference.md', $content);
    }
}
