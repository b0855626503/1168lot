<?php

namespace Gametech\Lotto\Services\AutoResultV2\FetchDrivers;

use Gametech\Lotto\Services\AutoResultV2\Browser\BrowserFetchDispatchService;
use Illuminate\Support\Facades\Http;

class RenderedBrowserFetchDriver
{
    public function __construct(
        private ?BrowserFetchDispatchService $dispatchService = null
    ) {
        $this->dispatchService = $this->dispatchService ?: new BrowserFetchDispatchService();
    }

    /**
     * @param array<string,mixed> $fetchConfig
     * @return array<string,mixed>
     */
    public function fetch(array $fetchConfig): array
    {
        $enabled = (bool) config('lotto_auto_result.v2.browser_runtime_enabled', false);
        if (! $enabled) {
            return [
                'ok' => false,
                'status' => 'APP_SHELL_ONLY',
                'http_status' => null,
                'response_body' => null,
                'response_content_type' => null,
                'duration_ms' => 0,
                'error_message' => 'browser runtime not enabled in this worker',
            ];
        }

        $job = $this->dispatchService->dispatch($fetchConfig);

        return [
            'ok' => false,
            'status' => (string) ($job['status'] ?? 'FETCH_DEFERRED'),
            'http_status' => null,
            'response_body' => null,
            'response_content_type' => null,
            'duration_ms' => 0,
            'error_message' => 'rendered browser fetch dispatched asynchronously',
            'job_id' => $job['job_id'] ?? null,
        ];
    }

    /**
     * Runtime-only fetch for dedicated worker. This method must never be called in main request path.
     *
     * @param array<string,mixed> $request
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function performRuntimeFetch(array $request, array $context = []): array
    {
        $url = (string) ($request['url'] ?? '');
        $method = strtoupper((string) ($request['method'] ?? 'GET'));
        $headers = is_array($request['headers'] ?? null) ? $request['headers'] : [];
        $timeout = max(1, (int) ($context['timeout_seconds'] ?? 15));

        if ($url === '') {
            return [
                'status' => 'FETCH_FAILED',
                'error_message' => 'url is required',
                'response_body' => null,
                'response_content_type' => null,
            ];
        }

        $start = microtime(true);
        try {
            $response = Http::timeout($timeout)
                ->withHeaders($headers)
                ->send($method, $url, []);

            return [
                'status' => $response->successful() ? 'SUCCESS' : 'FETCH_FAILED',
                'response_body' => (string) $response->body(),
                'response_content_type' => (string) $response->header('Content-Type'),
                'http_status' => $response->status(),
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'driver' => 'rendered_browser_runtime_stub',
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'FETCH_FAILED',
                'error_message' => $e->getMessage(),
                'response_body' => null,
                'response_content_type' => null,
                'http_status' => null,
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'driver' => 'rendered_browser_runtime_stub',
            ];
        }
    }
}
