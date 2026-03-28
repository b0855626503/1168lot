<?php

namespace Tests\Unit\Lotto\AutoResultV2;

use Gametech\Lotto\Jobs\ExecuteRenderedBrowserFetchJob;
use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\RenderedBrowserFetchDriver;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ExecuteRenderedBrowserFetchJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('cache.default', 'array');
    }

    public function test_job_writes_cached_payload_with_artifact_refs(): void
    {
        $receipt = 'test_receipt_execute_job';
        $driver = new class extends RenderedBrowserFetchDriver {
            public function performRuntimeFetch(array $request, array $context = []): array
            {
                return [
                    'status' => 'success',
                    'response_body' => '{"ok":true}',
                    'response_content_type' => 'application/json',
                    'http_status' => 200,
                    'selected_endpoint' => 'https://example.com/api/result',
                    'error_code' => null,
                    'meta' => [
                        'duration_ms' => 12,
                        'captured_count' => 1,
                        'selected_driver' => 'BROWSER_RUNTIME',
                    ],
                ];
            }
        };

        $job = new ExecuteRenderedBrowserFetchJob(
            ['url' => 'https://example.com'],
            ['source_id' => 99, 'draw_id' => 123],
            $receipt
        );
        $job->handle($driver);

        $cached = Cache::get('lotto:auto-result:browser-fetch:' . $receipt);
        $this->assertIsArray($cached);
        $this->assertSame('success', $cached['status']);
        $this->assertArrayHasKey('meta', $cached);
        $this->assertArrayHasKey('artifact_refs', $cached['meta']);
        $this->assertIsArray($cached['meta']['artifact_refs']);
    }
}

