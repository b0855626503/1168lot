<?php

namespace Tests\Feature\Admin;

use Gametech\Admin\Http\Controllers\ApiDocsController;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiDocsControllerTest extends TestCase
{
    public function test_frontend_api_v1_view_uses_full_document_bundle(): void
    {
        $request = Request::create('/admin/docs/api/frontend-v1', 'GET', [
            '_config' => [
                'view' => 'admin::module.docs.frontend_api_v1',
            ],
        ]);
        $this->app->instance('request', $request);

        $response = app(ApiDocsController::class)->frontendApiV1();
        $rendered = $response->render();

        $this->assertStringContainsString('# คู่มือ Frontend API V1 (Gametech)', $rendered);
        $this->assertStringContainsString('## 3) Route Catalog (ครบทุกเส้น)', $rendered);
        $this->assertStringContainsString('/api/v1/wallet/transactions', $rendered);
        $this->assertStringContainsString('## Yeekee API', $rendered);
        $this->assertStringContainsString('/api/v1/lotto/yeekee/rounds/{roundId}/result-proof', $rendered);
    }

    public function test_frontend_api_v1_raw_returns_full_document_bundle(): void
    {
        $request = Request::create('/admin/docs/api/frontend-v1/raw', 'GET', [
            '_config' => [
                'view' => 'admin::module.docs.frontend_api_v1',
            ],
        ]);
        $this->app->instance('request', $request);

        $response = app(ApiDocsController::class)->frontendApiV1Raw();
        $content = $response->getContent();

        $this->assertStringContainsString('# คู่มือ Frontend API V1 (Gametech)', $content);
        $this->assertStringContainsString('## 3) Route Catalog (ครบทุกเส้น)', $content);
        $this->assertStringContainsString('/api/v1/wallet/transactions', $content);
        $this->assertStringContainsString('## Yeekee API', $content);
        $this->assertStringContainsString('/api/v1/lotto/yeekee/rounds/{roundId}/result-proof', $content);
    }
}
