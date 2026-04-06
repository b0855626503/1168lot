<?php

namespace Gametech\Lotto\Services\InternalResultSources\Drivers;

use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\RenderedBrowserFetchDriver;
use Gametech\Lotto\Services\InternalResultSources\Contracts\InternalResultSourceDriver;
use Gametech\Lotto\Services\InternalResultSources\ExphuayPythonWorkerClient;
use Gametech\Lotto\Services\InternalResultSources\HttpResultFetcher;

class ExphuayResultDriver implements InternalResultSourceDriver
{
    private const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36';

    public function __construct(
        private HttpResultFetcher $fetcher,
        private ExphuayPythonWorkerClient $pythonWorkerClient
    )
    {
    }

    public function sourceKey(): string
    {
        return 'exphuay';
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function fetch(array $params): array
    {
        $startedAt = microtime(true);
        $totalBudgetSeconds = max(1, (int) config('lotto_auto_result.internal_result_sources.exphuay.request_budget_seconds', 30));
        $type = (string) ($params['type'] ?? '');
        $date = (string) ($params['date'] ?? '');
        $page = max(1, (int) ($params['page'] ?? 1));

        $url = sprintf('https://exphuay.com/backward/%s/__data.json', rawurlencode($type));
        $query = [
            'page' => $page,
            'x-sveltekit-invalidated' => '01',
        ];
        if ($date !== '') {
            $query['date'] = $date;
        }

        $headers = $this->buildHeaders($type);
        $primaryTimeout = min(
            max(1, (int) config('lotto_auto_result.internal_result_sources.timeout_seconds', 15)),
            $totalBudgetSeconds
        );
        $fetch = $this->fetcher->get(
            $url,
            $query,
            $primaryTimeout,
            $headers
        );
        $fetch = $this->tryPythonWorkerFallback($fetch, $url, $query, $headers, $startedAt, $totalBudgetSeconds);
        $fetch = $this->tryBrowserFallback($fetch, $url, $query, $headers, $startedAt, $totalBudgetSeconds);

        return [
            'source' => $this->sourceKey(),
            'type' => $type,
            'request_params' => $query,
            'remote_url' => $url,
            'fetch' => $fetch,
        ];
    }

    /**
     * @return array<string,string>
     */
    private function buildHeaders(string $type): array
    {
        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'th-TH,th;q=0.9,en;q=0.8',
            'Referer' => 'https://exphuay.com/backward/' . rawurlencode($type),
            'User-Agent' => (string) env('LOTTO_EXPHUAY_USER_AGENT', self::DEFAULT_USER_AGENT),
            'x-sveltekit-invalidated' => '01',
        ];

        $cookie = trim((string) env('LOTTO_EXPHUAY_COOKIE', ''));
        if ($cookie !== '') {
            $headers['Cookie'] = $cookie;
        }

        return $headers;
    }

    /**
     * @param array<string,mixed> $fetch
     * @param array<string,mixed> $query
     * @param array<string,mixed> $headers
     * @return array<string,mixed>
     */
    private function tryPythonWorkerFallback(
        array $fetch,
        string $url,
        array $query,
        array $headers,
        float $startedAt,
        int $totalBudgetSeconds
    ): array
    {
        if (! $this->shouldUsePythonWorkerFallback($fetch)) {
            return $fetch;
        }

        $remainingSeconds = $this->remainingTimeoutSeconds($startedAt, $totalBudgetSeconds);
        if ($remainingSeconds <= 0) {
            $fetch['meta'] = array_merge(
                is_array($fetch['meta'] ?? null) ? $fetch['meta'] : [],
                ['budget_exhausted_before_python_worker' => true]
            );

            return $fetch;
        }

        $configuredTimeout = max(1, (int) config('lotto_auto_result.internal_result_sources.exphuay.python_worker_timeout_seconds', 20));
        $timeout = min($configuredTimeout, $remainingSeconds);
        $workerFetch = $this->pythonWorkerClient->fetch($url, $query, $headers, $timeout);

        if ((bool) ($workerFetch['ok'] ?? false)) {
            return $workerFetch;
        }

        $fetch['meta'] = array_merge(
            is_array($fetch['meta'] ?? null) ? $fetch['meta'] : [],
            [
                'python_worker_error_code' => $workerFetch['error_code'] ?? null,
                'python_worker_error_message' => $workerFetch['error_message'] ?? null,
            ]
        );

        return $fetch;
    }

    /**
     * @param array<string,mixed> $fetch
     * @param array<string,mixed> $query
     * @param array<string,mixed> $headers
     * @return array<string,mixed>
     */
    private function tryBrowserFallback(
        array $fetch,
        string $url,
        array $query,
        array $headers,
        float $startedAt,
        int $totalBudgetSeconds
    ): array
    {
        if (! $this->shouldUseBrowserFallback($fetch)) {
            return $fetch;
        }

        $remainingSeconds = $this->remainingTimeoutSeconds($startedAt, $totalBudgetSeconds);
        if ($remainingSeconds <= 0) {
            $fetch['meta'] = array_merge(
                is_array($fetch['meta'] ?? null) ? $fetch['meta'] : [],
                ['budget_exhausted_before_browser_fallback' => true]
            );

            return $fetch;
        }

        $configuredTimeout = max(1, (int) config('lotto_auto_result.internal_result_sources.exphuay.browser_fallback_timeout_seconds', 60));
        $timeout = min($configuredTimeout, $remainingSeconds);
        $runtime = (new RenderedBrowserFetchDriver())->performRuntimeFetch([
            'url' => $url,
            'method' => 'GET',
            'headers' => $headers,
            'query' => $query,
        ], [
            'timeout_seconds' => $timeout,
            'fetch_capability' => 'require_browser_runtime',
            'allow_dom_fallback' => false,
            'meta' => [
                'browser_worker' => [
                    'wait_until' => (string) config('lotto_auto_result.internal_result_sources.exphuay.browser_wait_until', 'domcontentloaded'),
                    'timeout_ms' => min(
                        max(1000, $timeout * 1000),
                        max(1000, (int) config('lotto_auto_result.internal_result_sources.exphuay.browser_timeout_ms', 60000))
                    ),
                    'capture_url_patterns' => [$url],
                ],
            ],
        ]);

        if (strtolower((string) ($runtime['status'] ?? '')) !== 'success') {
            $fetch['error_code'] = (string) ($runtime['error_code'] ?? ($fetch['error_code'] ?? 'FETCH_FAILED'));
            $fetch['error_message'] = (string) ($runtime['error_message'] ?? ($fetch['error_message'] ?? 'browser fallback failed'));

            return $fetch;
        }

        return [
            'ok' => true,
            'http_status' => (int) ($runtime['http_status'] ?? 200),
            'response_body' => (string) ($runtime['response_body'] ?? ''),
            'duration_ms' => (int) ($runtime['meta']['duration_ms'] ?? 0),
            'error_code' => null,
            'error_message' => null,
            'response_content_type' => (string) ($runtime['response_content_type'] ?? 'application/json'),
        ];
    }

    /**
     * @param array<string,mixed> $fetch
     */
    private function shouldUseBrowserFallback(array $fetch): bool
    {
        $fallbackEnabled = (bool) config('lotto_auto_result.internal_result_sources.exphuay.browser_fallback_enabled', true);
        if (! $fallbackEnabled) {
            return false;
        }

        return $this->isCloudflareChallenge($fetch);
    }

    /**
     * @param array<string,mixed> $fetch
     */
    private function shouldUsePythonWorkerFallback(array $fetch): bool
    {
        $fallbackEnabled = (bool) config('lotto_auto_result.internal_result_sources.exphuay.python_worker_enabled', false);
        if (! $fallbackEnabled) {
            return false;
        }

        return $this->isCloudflareChallenge($fetch);
    }

    /**
     * @param array<string,mixed> $fetch
     */
    private function isCloudflareChallenge(array $fetch): bool
    {
        if ((bool) ($fetch['ok'] ?? false)) {
            return false;
        }

        $status = (int) ($fetch['http_status'] ?? 0);
        $body = strtolower((string) ($fetch['response_body'] ?? ''));

        if ($status === 403) {
            return true;
        }

        return str_contains($body, 'just a moment')
            || str_contains($body, 'cf-mitigated')
            || str_contains($body, 'cdn-cgi/challenge-platform');
    }

    private function remainingTimeoutSeconds(float $startedAt, int $totalBudgetSeconds): int
    {
        $elapsedSeconds = (int) floor(microtime(true) - $startedAt);

        return max(0, $totalBudgetSeconds - $elapsedSeconds);
    }
}
