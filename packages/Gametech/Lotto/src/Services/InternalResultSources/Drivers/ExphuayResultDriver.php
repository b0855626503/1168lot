<?php

namespace Gametech\Lotto\Services\InternalResultSources\Drivers;

use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\RenderedBrowserFetchDriver;
use Gametech\Lotto\Services\InternalResultSources\Contracts\InternalResultSourceDriver;
use Gametech\Lotto\Services\InternalResultSources\HttpResultFetcher;

class ExphuayResultDriver implements InternalResultSourceDriver
{
    private const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36';

    public function __construct(private HttpResultFetcher $fetcher)
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
        $fetch = $this->fetcher->get(
            $url,
            $query,
            (int) config('lotto_auto_result.internal_result_sources.timeout_seconds', 15),
            $headers
        );
        $fetch = $this->tryBrowserFallback($fetch, $url, $query, $headers);

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
    private function tryBrowserFallback(array $fetch, string $url, array $query, array $headers): array
    {
        if (! $this->shouldUseBrowserFallback($fetch)) {
            return $fetch;
        }

        $timeout = max(10, (int) config('lotto_auto_result.internal_result_sources.exphuay.browser_fallback_timeout_seconds', 60));
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
                    'timeout_ms' => max(10000, (int) config('lotto_auto_result.internal_result_sources.exphuay.browser_timeout_ms', 60000)),
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
}
