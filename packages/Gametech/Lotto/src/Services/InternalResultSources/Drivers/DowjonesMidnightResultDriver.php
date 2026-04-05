<?php

namespace Gametech\Lotto\Services\InternalResultSources\Drivers;

use Gametech\Lotto\Services\InternalResultSources\Contracts\InternalResultSourceDriver;
use Gametech\Lotto\Services\InternalResultSources\HttpResultFetcher;

class DowjonesMidnightResultDriver implements InternalResultSourceDriver
{
    public function __construct(private HttpResultFetcher $fetcher)
    {
    }

    public function sourceKey(): string
    {
        return 'dowjones-midnight';
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function fetch_(array $params): array
    {
        $date = (string) ($params['date'] ?? '');
        $query = [];
        if ($date !== '') {
            $query['date'] = $date;
        }

        $url = 'https://api.dowjones-midnight.com/result';
        $fetch = $this->fetcher->get(
            $url,
            $query,
            (int) config('lotto_auto_result.internal_result_sources.timeout_seconds', 15)
        );

        return [
            'source' => $this->sourceKey(),
            'type' => $this->sourceKey(),
            'request_params' => $query,
            'remote_url' => $url,
            'fetch' => $fetch,
        ];
    }

    public function fetch(array $params): array
    {
        $date = (string) ($params['date'] ?? '');

        $query = [];
        $url = 'https://api.dowjones-midnight.com/result';
        if ($date !== '') {
            $query['date'] = $date;
        }

        $fetch = $this->fetcher->get(
            $url,
            $query,
            (int) config('lotto_auto_result.internal_result_sources.timeout_seconds', 15)
        );

        return [
            'source' => $this->sourceKey(),
            'type' => $this->sourceKey(),
            'request_params' => $query,
            'remote_url' => $url,
            'fetch' => $fetch,
        ];
    }
}
