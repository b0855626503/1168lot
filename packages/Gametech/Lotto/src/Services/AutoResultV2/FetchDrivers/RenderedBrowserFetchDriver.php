<?php

namespace Gametech\Lotto\Services\AutoResultV2\FetchDrivers;

use Gametech\Lotto\Services\AutoResultV2\Browser\BrowserFetchDispatchService;
use Gametech\Lotto\Services\AutoResultV2\Browser\BrowserRuntimePolicyService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

class RenderedBrowserFetchDriver
{
    private const MAX_CAPTURE_PATTERNS = 12;
    private const MAX_CAPTURED_RESPONSES = 8;

    public function __construct(
        private ?BrowserFetchDispatchService $dispatchService = null,
        private ?BrowserRuntimePolicyService $policyService = null
    ) {
        $this->dispatchService = $this->dispatchService ?: new BrowserFetchDispatchService();
        $this->policyService = $this->policyService ?: new BrowserRuntimePolicyService();
    }

    /**
     * @param array<string,mixed> $fetchConfig
     * @return array<string,mixed>
     */
    public function fetch(array $fetchConfig): array
    {
        $enabled = (bool) config('lotto_auto_result.browser_runtime.enabled', false);
        $request = is_array($fetchConfig['request'] ?? null) ? $fetchConfig['request'] : [];
        $context = is_array($fetchConfig['context'] ?? null) ? $fetchConfig['context'] : [];
        $meta = is_array($fetchConfig['meta'] ?? null) ? $fetchConfig['meta'] : [];
        $runtimeMeta = is_array($meta['runtime'] ?? null) ? $meta['runtime'] : [];
        $capability = $this->policyService->normalizeCapability($runtimeMeta['fetch_capability'] ?? null);
        $context['fetch_capability'] = $capability;
        $context['allow_dom_fallback'] = (bool) ($runtimeMeta['allow_dom_fallback'] ?? false);

        $receipt = $this->dispatchService->buildReceiptKey([
            'request' => $request,
            'timeout_seconds' => $fetchConfig['timeout_seconds'] ?? 10,
            'context' => $context,
            'meta' => $meta,
        ]);

        $sourceId = (int) ($context['source_id'] ?? 0);
        if (! $this->policyService->canUseBrowserRuntime($capability, $enabled, $sourceId)) {
            return [
                'ok' => false,
                'status' => 'FETCH_FAILED',
                'http_status' => null,
                'response_body' => null,
                'response_content_type' => null,
                'duration_ms' => 0,
                'error_message' => 'browser runtime not enabled for this source capability/rollout',
                'error_code' => 'BROWSER_RUNTIME_UNAVAILABLE',
                'receipt_key' => $receipt,
                'selected_driver' => 'HTTP_FALLBACK',
                'capability' => $capability,
            ];
        }

        $cached = $this->dispatchService->getCachedPayload($receipt);
        if (is_array($cached)) {
            return $this->buildFetchResultFromCache($cached, $receipt);
        }

        $job = $this->dispatchService->dispatch([
            'request' => $request,
            'timeout_seconds' => $fetchConfig['timeout_seconds'] ?? 10,
            'context' => $context,
            'meta' => $meta,
        ]);

        return [
            'ok' => false,
            'status' => (string) ($job['status'] ?? 'FETCH_DEFERRED'),
            'http_status' => null,
            'response_body' => null,
            'response_content_type' => null,
            'duration_ms' => 0,
            'error_message' => ($job['reason'] ?? null) === 'LOCKED'
                ? 'rendered browser fetch already in progress'
                : 'rendered browser fetch dispatched asynchronously',
            'error_code' => 'FETCH_DEFERRED',
            'job_id' => $job['job_id'] ?? null,
            'receipt_key' => $receipt,
            'selected_driver' => 'BROWSER_RUNTIME',
            'capability' => $capability,
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
        $query = is_array($request['query'] ?? null) ? $request['query'] : [];
        $body = $request['body'] ?? null;
        $timeout = max(1, (int) ($context['timeout_seconds'] ?? 15));
        $browserMeta = $this->normalizeBrowserMeta(is_array($context['meta'] ?? null) ? $context['meta'] : []);
        $runtimeCapability = $this->policyService->normalizeCapability($context['fetch_capability'] ?? null);
        $allowDomFallback = (bool) ($context['allow_dom_fallback'] ?? false);

        if ($url === '') {
            return [
                'status' => 'failed',
                'error_code' => 'INVALID_REQUEST',
                'error_message' => 'url is required',
                'response_body' => null,
                'response_content_type' => null,
                'selected_endpoint' => null,
                'meta' => [
                    'duration_ms' => 0,
                    'captured_count' => 0,
                ],
            ];
        }

        $start = microtime(true);
        $runtimePayload = $this->executeNodeRuntime([
            'schema_version' => (int) config('lotto_auto_result.browser_runtime.schema_version', 1),
            'request' => [
                'url' => $url,
                'method' => $method,
                'headers' => $headers,
                'query' => $query,
                'body' => $body,
            ],
            'context' => [
                'source_id' => (int) ($context['source_id'] ?? 0),
                'draw_id' => (int) ($context['draw_id'] ?? 0),
                'expected_draw_date' => (string) ($context['expected_draw_date'] ?? ''),
            ],
            'browser' => $browserMeta,
            'allow_dom_fallback' => $allowDomFallback,
            'selection_mode' => (string) (($browserMeta['capture']['selection_mode'] ?? 'best')),
        ], max($timeout, (int) config('lotto_auto_result.browser_runtime.timeouts.overall_seconds', 60)));

        if ((bool) ($runtimePayload['ok'] ?? false)) {
            return [
                'status' => 'success',
                'response_body' => $runtimePayload['response_body'] ?? null,
                'response_content_type' => $runtimePayload['response_content_type'] ?? null,
                'http_status' => $runtimePayload['http_status'] ?? 200,
                'selected_endpoint' => $runtimePayload['selected_endpoint'] ?? null,
                'error_code' => null,
                'meta' => [
                    'duration_ms' => (int) ($runtimePayload['duration_ms'] ?? (int) round((microtime(true) - $start) * 1000)),
                    'content_type' => $runtimePayload['response_content_type'] ?? null,
                    'captured_count' => (int) ($runtimePayload['captured_count'] ?? 0),
                    'selection_priority' => (string) ($runtimePayload['selection_priority'] ?? 'best'),
                    'payload_origin' => (string) ($runtimePayload['payload_origin'] ?? 'network_capture'),
                    'selected_driver' => 'BROWSER_RUNTIME',
                    'selected_capture' => $runtimePayload['selected_capture'] ?? null,
                    'phase_timing' => $runtimePayload['phase_timing'] ?? [],
                    'network_summary' => $runtimePayload['network_summary'] ?? [],
                ],
                'network_summary' => $runtimePayload['network_summary'] ?? [],
            ];
        }

        $errorCode = strtoupper((string) ($runtimePayload['error_code'] ?? 'BROWSER_EXECUTOR_IO_ERROR'));
        if ($this->policyService->shouldFallbackToHttp($runtimeCapability, $errorCode)) {
            $fallback = $this->performHttpFallbackFetch($url, $method, $headers, $query, $body, $timeout, $allowDomFallback, $start);
            $fallback['meta'] = is_array($fallback['meta'] ?? null) ? $fallback['meta'] : [];
            $fallback['meta']['fallback_from'] = $errorCode;
            $fallback['meta']['selected_driver'] = 'HTTP_FALLBACK';
            $fallback['meta']['payload_origin'] = $fallback['meta']['payload_origin'] ?? 'http_fallback';

            return $fallback;
        }

        return [
            'status' => 'failed',
            'error_code' => $errorCode,
            'error_message' => (string) ($runtimePayload['error_message'] ?? 'browser runtime failed'),
            'response_body' => null,
            'response_content_type' => null,
            'http_status' => null,
            'selected_endpoint' => null,
            'meta' => [
                'duration_ms' => (int) ($runtimePayload['duration_ms'] ?? (int) round((microtime(true) - $start) * 1000)),
                'content_type' => null,
                'captured_count' => 0,
                'selected_driver' => 'BROWSER_RUNTIME',
                'payload_origin' => 'none',
                'phase_timing' => $runtimePayload['phase_timing'] ?? [],
                'network_summary' => $runtimePayload['network_summary'] ?? [],
            ],
            'network_summary' => $runtimePayload['network_summary'] ?? [],
        ];
    }

    /**
     * @param array<string,mixed> $cached
     * @return array<string,mixed>
     */
    private function buildFetchResultFromCache(array $cached, string $receipt): array
    {
        $status = strtolower((string) ($cached['status'] ?? 'failed'));
        $meta = is_array($cached['meta'] ?? null) ? $cached['meta'] : [];

        if ($status === 'success') {
            return [
                'ok' => true,
                'status' => 'SUCCESS',
                'http_status' => $cached['http_status'] ?? 200,
                'response_body' => $cached['response_body'] ?? null,
                'response_content_type' => $cached['response_content_type'] ?? 'text/html',
                'duration_ms' => (int) ($meta['duration_ms'] ?? 0),
                'error_message' => null,
                'error_code' => null,
                'receipt_key' => $receipt,
                'selected_endpoint' => $cached['selected_endpoint'] ?? null,
                'selected_driver' => (string) ($meta['selected_driver'] ?? 'BROWSER_RUNTIME'),
            ];
        }

        if ($status === 'app_shell_only') {
            return [
                'ok' => false,
                'status' => 'APP_SHELL_ONLY',
                'http_status' => $cached['http_status'] ?? null,
                'response_body' => $cached['response_body'] ?? null,
                'response_content_type' => $cached['response_content_type'] ?? null,
                'duration_ms' => (int) ($meta['duration_ms'] ?? 0),
                'error_message' => $cached['error_message'] ?? 'app shell only',
                'error_code' => (string) ($cached['error_code'] ?? 'APP_SHELL_ONLY'),
                'receipt_key' => $receipt,
                'selected_endpoint' => $cached['selected_endpoint'] ?? null,
                'selected_driver' => (string) ($meta['selected_driver'] ?? 'BROWSER_RUNTIME'),
            ];
        }

        return [
            'ok' => false,
            'status' => 'FETCH_FAILED',
            'http_status' => $cached['http_status'] ?? null,
            'response_body' => $cached['response_body'] ?? null,
            'response_content_type' => $cached['response_content_type'] ?? null,
            'duration_ms' => (int) ($meta['duration_ms'] ?? 0),
            'error_message' => $cached['error_message'] ?? 'browser worker failed',
            'error_code' => (string) ($cached['error_code'] ?? 'FETCH_FAILED'),
            'receipt_key' => $receipt,
            'selected_endpoint' => $cached['selected_endpoint'] ?? null,
            'selected_driver' => (string) ($meta['selected_driver'] ?? 'BROWSER_RUNTIME'),
        ];
    }

    /**
     * @param array<int,string> $patterns
     * @return array<int,string>
     */
    private function sanitizeCapturePatterns(array $patterns): array
    {
        $valid = [];
        foreach ($patterns as $rawPattern) {
            $pattern = trim((string) $rawPattern);
            if ($pattern === '') {
                continue;
            }
            if (! $this->isSafePattern($pattern)) {
                continue;
            }
            $valid[] = $pattern;
            if (count($valid) >= self::MAX_CAPTURE_PATTERNS) {
                break;
            }
        }

        return $valid;
    }

    private function isSafePattern(string $pattern): bool
    {
        if (mb_strlen($pattern) > 200) {
            return false;
        }

        // Basic guard against expensive nested quantifiers.
        if (preg_match('/\((?:[^()]*[+*][^()]*)\)[+*?]/', $pattern) === 1) {
            return false;
        }

        return true;
    }

    /**
     * @param array<int,string> $patterns
     * @return array<int,string>
     */
    private function buildCaptureCandidates(string $mainUrl, array $patterns): array
    {
        $candidates = [];
        $parts = parse_url($mainUrl);
        $origin = null;
        if (is_array($parts) && isset($parts['scheme'], $parts['host'])) {
            $origin = $parts['scheme'] . '://' . $parts['host'];
        }

        foreach ($patterns as $pattern) {
            if (filter_var($pattern, FILTER_VALIDATE_URL)) {
                $candidates[] = $pattern;
                continue;
            }

            if ($origin !== null && str_starts_with($pattern, '/')) {
                $candidates[] = $origin . $pattern;
            }
        }

        return array_values(array_unique(array_filter($candidates, static fn ($url): bool => is_string($url) && trim($url) !== '')));
    }

    private function looksLikeLottoPayload(array $payload): bool
    {
        if (isset($payload['lotData']) || isset($payload['items']) || isset($payload['item'])) {
            return true;
        }

        return isset($payload['resultDate']) || isset($payload['label']);
    }

    private function isAppShellHtml(string $html): bool
    {
        $trimmed = trim($html);
        if ($trimmed === '') {
            return true;
        }

        $hasAppContainer = preg_match('/id=["\'](?:app|root)["\']/i', $trimmed) === 1;
        $hasNumericResult = preg_match('/\b\d{4,6}\b/u', $trimmed) === 1;

        return $hasAppContainer && ! $hasNumericResult;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function executeNodeRuntime(array $payload, int $timeoutSeconds): array
    {
        $nodeBinary = (string) config('lotto_auto_result.browser_runtime.node_binary', 'node');
        $workerScript = (string) config('lotto_auto_result.browser_runtime.worker_script', base_path('scripts/lotto/browser_runtime_worker.js'));
        if (! is_file($workerScript)) {
            return [
                'ok' => false,
                'error_code' => 'BROWSER_RUNTIME_UNAVAILABLE',
                'error_message' => 'browser runtime worker script not found',
            ];
        }

        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            return [
                'ok' => false,
                'error_code' => 'BROWSER_EXECUTOR_IO_ERROR',
                'error_message' => 'cannot encode runtime payload',
            ];
        }

        try {
            $process = new Process([$nodeBinary, $workerScript], base_path(), null, $encoded, $timeoutSeconds);
            $process->run();

            $stderr = trim((string) $process->getErrorOutput());
            $stdout = trim((string) $process->getOutput());

            if (! $process->isSuccessful()) {
                return [
                    'ok' => false,
                    'error_code' => $process->getExitCode() === 127 ? 'BROWSER_RUNTIME_UNAVAILABLE' : 'BROWSER_LAUNCH_FAILED',
                    'error_message' => $stderr !== '' ? mb_substr($stderr, 0, 2000) : 'node worker failed',
                ];
            }

            $decoded = json_decode($stdout, true);
            if (! is_array($decoded)) {
                return [
                    'ok' => false,
                    'error_code' => 'BROWSER_EXECUTOR_IO_ERROR',
                    'error_message' => 'invalid node worker output',
                ];
            }

            return $decoded;
        } catch (\Throwable $e) {
            $message = strtolower($e->getMessage());
            if (str_contains($message, 'timed out')) {
                return [
                    'ok' => false,
                    'error_code' => 'BROWSER_EXECUTOR_TIMEOUT',
                    'error_message' => $e->getMessage(),
                ];
            }

            return [
                'ok' => false,
                'error_code' => 'BROWSER_EXECUTOR_IO_ERROR',
                'error_message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function performHttpFallbackFetch(string $url, string $method, array $headers, array $query, mixed $body, int $timeout, bool $allowDomFallback, float $start): array
    {
        $capturePatterns = $this->sanitizeCapturePatterns([$url]);
        $candidateEndpoints = $this->buildCaptureCandidates($url, $capturePatterns);
        $captured = 0;

        foreach ($candidateEndpoints as $endpointUrl) {
            $captureResult = $this->fetchHttp($endpointUrl, 'GET', array_merge(['accept' => 'application/json'], $headers), [], null, $timeout);
            $captured++;
            if (! (bool) ($captureResult['ok'] ?? false)) {
                continue;
            }
            $decoded = json_decode((string) ($captureResult['response_body'] ?? ''), true);
            if (! is_array($decoded) || ! $this->looksLikeLottoPayload($decoded)) {
                continue;
            }

            return [
                'status' => 'success',
                'response_body' => (string) ($captureResult['response_body'] ?? ''),
                'response_content_type' => (string) ($captureResult['response_content_type'] ?? 'application/json'),
                'http_status' => $captureResult['http_status'] ?? 200,
                'selected_endpoint' => $endpointUrl,
                'error_code' => null,
                'meta' => [
                    'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                    'content_type' => (string) ($captureResult['response_content_type'] ?? 'application/json'),
                    'captured_count' => $captured,
                    'selection_priority' => 'captured_endpoint_json',
                    'payload_origin' => 'network_capture',
                ],
            ];
        }

        if (! $allowDomFallback) {
            return [
                'status' => 'failed',
                'error_code' => 'NO_NETWORK_MATCH',
                'error_message' => 'no valid network payload and dom fallback disabled',
                'response_body' => null,
                'response_content_type' => null,
                'http_status' => null,
                'selected_endpoint' => null,
                'meta' => [
                    'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                    'content_type' => null,
                    'captured_count' => $captured,
                ],
            ];
        }

        $rendered = $this->fetchHttp($url, $method, $headers, $query, $body, $timeout);
        if (! (bool) ($rendered['ok'] ?? false)) {
            return [
                'status' => 'failed',
                'response_body' => $rendered['response_body'] ?? null,
                'response_content_type' => $rendered['response_content_type'] ?? null,
                'http_status' => $rendered['http_status'] ?? null,
                'selected_endpoint' => null,
                'error_code' => (string) ($rendered['error_code'] ?? 'FETCH_FAILED'),
                'error_message' => $rendered['error_message'] ?? 'fetch failed',
                'meta' => [
                    'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                    'content_type' => $rendered['response_content_type'] ?? null,
                    'captured_count' => $captured,
                    'payload_origin' => 'dom_fallback',
                ],
            ];
        }

        $html = (string) ($rendered['response_body'] ?? '');
        if ($this->isAppShellHtml($html)) {
            return [
                'status' => 'failed',
                'response_body' => $html !== '' ? $html : null,
                'response_content_type' => (string) ($rendered['response_content_type'] ?? 'text/html'),
                'http_status' => $rendered['http_status'] ?? null,
                'selected_endpoint' => null,
                'error_code' => 'APP_SHELL_ONLY',
                'error_message' => 'rendered page looks like app shell without usable result payload',
                'meta' => [
                    'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                    'content_type' => (string) ($rendered['response_content_type'] ?? 'text/html'),
                    'captured_count' => $captured,
                    'payload_origin' => 'dom_fallback',
                ],
            ];
        }

        return [
            'status' => 'success',
            'response_body' => $html,
            'response_content_type' => (string) ($rendered['response_content_type'] ?? 'text/html'),
            'http_status' => $rendered['http_status'] ?? 200,
            'selected_endpoint' => null,
            'error_code' => null,
            'meta' => [
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'content_type' => (string) ($rendered['response_content_type'] ?? 'text/html'),
                'captured_count' => $captured,
                'payload_origin' => 'dom_fallback',
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function fetchHttp(string $url, string $method, array $headers, array $query, mixed $body, int $timeout): array
    {
        $start = microtime(true);
        try {
            $response = Http::timeout($timeout)
                ->withHeaders($headers)
                ->send($method, $url, $this->buildHttpOptions($method, $query, $body));

            return [
                'ok' => $response->successful(),
                'http_status' => $response->status(),
                'response_body' => (string) $response->body(),
                'response_content_type' => (string) $response->header('Content-Type'),
                'error_code' => $response->successful() ? null : ('HTTP_STATUS_' . (string) $response->status()),
                'error_message' => $response->successful() ? null : 'HTTP status not successful',
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            ];
        } catch (ConnectionException $e) {
            $message = (string) $e->getMessage();
            $isTimeout = str_contains(strtolower($message), 'timed out') || str_contains($message, 'cURL error 28');
            return [
                'ok' => false,
                'http_status' => null,
                'response_body' => null,
                'response_content_type' => null,
                'error_code' => $isTimeout ? 'BROWSER_TIMEOUT' : 'NETWORK_ERROR',
                'error_message' => $message,
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'http_status' => null,
                'response_body' => null,
                'response_content_type' => null,
                'error_code' => 'FETCH_EXCEPTION',
                'error_message' => $e->getMessage(),
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            ];
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function buildHttpOptions(string $method, array $query, mixed $body): array
    {
        $options = [];
        if ($query !== []) {
            $options['query'] = $query;
        }

        if (in_array(strtoupper($method), ['GET', 'HEAD'], true)) {
            return $options;
        }

        if (is_array($body) && $body !== []) {
            $options['json'] = $body;
        } elseif ($body !== null && $body !== '') {
            $options['body'] = (string) $body;
        }

        return $options;
    }

    /**
     * @return array<string,mixed>
     */
    private function normalizeBrowserMeta(array $meta): array
    {
        $browser = is_array($meta['browser_worker'] ?? null) ? $meta['browser_worker'] : [];
        $capture = is_array($browser['capture'] ?? null) ? $browser['capture'] : [];
        $capturePatterns = is_array($capture['url_patterns'] ?? null)
            ? $capture['url_patterns']
            : (is_array($browser['capture_url_patterns'] ?? null) ? $browser['capture_url_patterns'] : []);
        $blockTypes = is_array($browser['block_resource_types'] ?? null) ? $browser['block_resource_types'] : [];

        return [
            'wait_for_selector' => trim((string) ($browser['wait_for_selector'] ?? '')),
            'wait_until' => $this->normalizeWaitUntil((string) ($browser['wait_until'] ?? 'networkidle')),
            'timeout_ms' => max(1000, min(120000, (int) ($browser['timeout_ms'] ?? 15000))),
            'capture' => [
                'url_patterns' => $capturePatterns,
                'max_captured_responses' => max(1, (int) ($capture['max_captured_responses'] ?? 3)),
                'selection_mode' => $this->normalizeSelectionMode((string) ($capture['selection_mode'] ?? 'best')),
            ],
            'block_resource_types' => array_values(array_filter(array_map(static fn ($v): string => trim((string) $v), $blockTypes), static fn ($v): bool => $v !== '')),
        ];
    }

    private function normalizeWaitUntil(string $waitUntil): string
    {
        $value = strtolower(trim($waitUntil));
        $allowed = ['load', 'domcontentloaded', 'networkidle', 'commit'];
        if (! in_array($value, $allowed, true)) {
            return 'networkidle';
        }

        return $value;
    }

    private function normalizeSelectionMode(string $mode): string
    {
        $value = strtolower(trim($mode));
        if (! in_array($value, ['first', 'best', 'all'], true)) {
            return 'best';
        }

        return $value;
    }
}
