<?php

namespace Gametech\Lotto\Jobs;

use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\RenderedBrowserFetchDriver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExecuteRenderedBrowserFetchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 1;

    public function __construct(
        public array $request,
        public array $context = [],
        public string $receipt = ''
    ) {
    }

    public function handle(RenderedBrowserFetchDriver $driver): void
    {
        $result = $driver->performRuntimeFetch($this->request, $this->context);

        if (trim($this->receipt) !== '') {
            Cache::put(
                'lotto:auto-result:browser-fetch:' . trim($this->receipt),
                array_merge($result, ['receipt' => trim($this->receipt)]),
                now()->addMinutes(30)
            );
        }

        Log::info('LOTTO_AUTO_RESULT_BROWSER_FETCH_DONE', [
            'receipt' => $this->receipt,
            'status' => $result['status'] ?? null,
            'driver' => $result['driver'] ?? null,
        ]);
    }
}
