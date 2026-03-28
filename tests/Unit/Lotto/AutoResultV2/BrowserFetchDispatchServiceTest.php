<?php

namespace Tests\Unit\Lotto\AutoResultV2;

use Gametech\Lotto\Services\AutoResultV2\Browser\BrowserFetchDispatchService;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class BrowserFetchDispatchServiceTest extends TestCase
{
    public function test_receipt_key_is_deterministic_for_identical_payload(): void
    {
        $service = new BrowserFetchDispatchService();
        $payload = $this->samplePayload([
            'context' => ['run_id' => 'run_1', 'source_id' => 9, 'draw_id' => 174],
        ]);
        $payloadSame = $this->samplePayload([
            'context' => ['run_id' => 'run_2', 'source_id' => 9, 'draw_id' => 174],
        ]);

        $keyA = $service->buildReceiptKey($payload);
        $keyB = $service->buildReceiptKey($payloadSame);

        $this->assertSame($keyA, $keyB);
    }

    public function test_receipt_key_changes_when_stable_context_changes(): void
    {
        $service = new BrowserFetchDispatchService();
        $payloadA = $this->samplePayload([
            'context' => ['source_id' => 9, 'draw_id' => 174],
        ]);
        $payloadB = $this->samplePayload([
            'context' => ['source_id' => 10, 'draw_id' => 174],
        ]);

        $this->assertNotSame(
            $service->buildReceiptKey($payloadA),
            $service->buildReceiptKey($payloadB)
        );
    }

    public function test_dispatch_is_blocked_when_lock_exists(): void
    {
        Bus::fake();
        $service = new BrowserFetchDispatchService();
        $payload = $this->samplePayload();
        $receipt = $service->buildReceiptKey($payload);

        $first = $service->dispatch($payload);
        $second = $service->dispatch($payload);

        $this->assertSame('FETCH_DEFERRED', $first['status']);
        $this->assertTrue((bool) ($first['dispatched'] ?? false));
        $this->assertSame('FETCH_DEFERRED', $second['status']);
        $this->assertFalse((bool) ($second['dispatched'] ?? true));
        $this->assertSame('LOCKED', $second['reason'] ?? null);
        $this->assertSame($receipt, $second['receipt_key'] ?? null);

        $service->releaseLock($receipt);
    }

    /**
     * @param array<string,mixed> $override
     * @return array<string,mixed>
     */
    private function samplePayload(array $override = []): array
    {
        $base = [
            'request' => [
                'url' => 'https://www.mlnhngoc.net',
                'method' => 'GET',
                'headers' => [],
                'query' => [],
                'body' => null,
            ],
            'timeout_seconds' => 10,
            'meta' => [
                'browser_worker' => [
                    'wait_until' => 'networkidle',
                    'timeout_ms' => 15000,
                    'capture_url_patterns' => ['/mlnhngoc'],
                ],
            ],
            'context' => [
                'source_id' => 9,
                'draw_id' => 174,
                'endpoint_url' => 'https://www.mlnhngoc.net',
                'strategy' => 'RENDERED_BROWSER',
                'parser_type' => 'JSON_PATH',
                'expected_draw_date' => '2026-03-28',
            ],
        ];

        return array_replace_recursive($base, $override);
    }
}

