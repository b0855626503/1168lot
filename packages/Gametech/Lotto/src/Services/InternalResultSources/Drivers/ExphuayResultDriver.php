<?php

namespace Gametech\Lotto\Services\InternalResultSources\Drivers;

use Gametech\Lotto\Services\InternalResultSources\Contracts\InternalResultSourceDriver;
use Gametech\Lotto\Services\InternalResultSources\HttpResultFetcher;

class ExphuayResultDriver implements InternalResultSourceDriver
{
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
            [
                'Referer' => 'https://exphuay.com/backward/' . rawurlencode($type),
                'x-sveltekit-invalidated' => '01',
            ]
        );

        return [
            'source' => $this->sourceKey(),
            'type' => $type,
            'request_params' => $query,
            'remote_url' => $url,
            'fetch' => $fetch,
        ];
    }
}

