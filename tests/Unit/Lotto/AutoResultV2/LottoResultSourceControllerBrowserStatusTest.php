<?php

namespace Tests\Unit\Lotto\AutoResultV2;

use Gametech\Lotto\Http\Controllers\Admin\LottoResultSourceController;
use Gametech\Lotto\Services\AutoResultV2\Browser\BrowserFetchDispatchService;
use Illuminate\Http\Request;
use Tests\TestCase;

class LottoResultSourceControllerBrowserStatusTest extends TestCase
{
    public function test_browser_test_status_includes_runtime_debug_fields(): void
    {
        $receipt = 'status_test_receipt';
        $dispatch = new BrowserFetchDispatchService();
        $dispatch->putCachedPayload($receipt, [
            'status' => 'success',
            'response_body' => '{"ok":true}',
            'selected_endpoint' => 'https://example.com/result',
            'meta' => [
                'selected_driver' => 'BROWSER_RUNTIME',
                'payload_origin' => 'network_capture',
                'phase_timing' => ['navigation_ms' => 100],
                'selected_capture' => ['url' => 'https://example.com/result'],
                'artifact_refs' => ['path' => '/tmp'],
            ],
        ]);

        $controller = new LottoResultSourceController();
        $response = $controller->browserTestStatus(Request::create('/lotto/auto-result-sources/browser-test-status', 'GET', [
            'receipt_key' => $receipt,
        ]));

        $payload = $response->getData(true);
        $this->assertTrue((bool) ($payload['success'] ?? false));
        $this->assertSame('SUCCESS', $payload['data']['status'] ?? null);
        $this->assertSame('BROWSER_RUNTIME', $payload['data']['selected_driver'] ?? null);
        $this->assertSame('network_capture', $payload['data']['payload_origin'] ?? null);
        $this->assertIsArray($payload['data']['phase_timing'] ?? null);
        $this->assertIsArray($payload['data']['selected_capture'] ?? null);
        $this->assertIsArray($payload['data']['artifact_refs'] ?? null);
    }
}

