<?php

namespace Gametech\Lotto\Services\AutoResultV2\Browser;

use Gametech\Lotto\Jobs\ExecuteRenderedBrowserFetchJob;
use Illuminate\Support\Facades\Bus;

class BrowserFetchDispatchService
{
    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function dispatch(array $payload): array
    {
        $receipt = (string) uniqid('browser_fetch_', true);
        $request = is_array($payload['request'] ?? null) ? $payload['request'] : [];
        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];
        $job = new ExecuteRenderedBrowserFetchJob($request, $context, $receipt);

        Bus::dispatch($job);

        return [
            'job_id' => $receipt,
            'status' => 'FETCH_DEFERRED',
        ];
    }
}
