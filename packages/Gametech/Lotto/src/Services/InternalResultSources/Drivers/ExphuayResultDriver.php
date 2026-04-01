<?php

namespace Gametech\Lotto\Services\InternalResultSources\Drivers;

use Gametech\Lotto\Services\InternalResultSources\Contracts\InternalResultSourceDriver;
use Gametech\Lotto\Services\InternalResultSources\HttpResultFetcher;

class ExphuayResultDriver implements InternalResultSourceDriver
{
    private const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0 Safari/537.36';

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

        $fetch = $this->fetcher->get(
            $url,
            $query,
            (int) config('lotto_auto_result.internal_result_sources.timeout_seconds', 15),
            $this->buildHeaders($type)
        );

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
}
