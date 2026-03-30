<?php

namespace Gametech\Lotto\Services\InternalResultSources\Drivers;

use Gametech\Lotto\Services\InternalResultSources\Contracts\InternalResultSourceDriver;
use Gametech\Lotto\Services\InternalResultSources\HttpResultFetcher;

class DowjonesExtraResultDriver implements InternalResultSourceDriver
{
    public function __construct(private HttpResultFetcher $fetcher)
    {
    }

    public function sourceKey(): string
    {
        return 'dowjones-extra';
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function fetch(array $params): array
    {
        $date = (string) ($params['date'] ?? '');
        $query = [];
        if ($date !== '') {
            $query['date'] = $date;
        }

        $url = 'https://api.dowjonesextra.com/result';
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

