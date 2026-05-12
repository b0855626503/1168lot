<?php

namespace Gametech\Lotto\Jobs;

use Gametech\Lotto\Services\ArchiveWriterService;
use Gametech\Lotto\Services\ExternalResultFetcherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FillMissingResultArchiveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [60, 180, 600];

    public function __construct(
        public string $marketCode,
        public string $drawDate,
        public string $runId,
    ) {
        $this->onQueue('lotto');
    }

    public function handle(
        ExternalResultFetcherService $fetcher,
        ArchiveWriterService $writer,
    ): void {
        if (! $this->isFetchEnabled()) {
            Log::info('FillMissingResultArchiveJob: fetch disabled on this instance, skipping', [
                'market_code' => $this->marketCode,
                'draw_date' => $this->drawDate,
            ]);

            return;
        }

        $rows = $fetcher->fetchMissing($this->marketCode, $this->drawDate);

        if (! $rows) {
            Log::info('FillMissingResultArchiveJob: no data available', [
                'market_code' => $this->marketCode,
                'draw_date' => $this->drawDate,
            ]);

            return;
        }

        $result = $writer->writeArchive($rows, 'external_fetch', null, $this->runId);

        Log::info('FillMissingResultArchiveJob: complete', [
            'market_code' => $this->marketCode,
            'draw_date' => $this->drawDate,
            'created' => $result['created'],
        ]);
    }

    protected function isFetchEnabled(): bool
    {
        return (bool) (env('LOTTO_ARCHIVE_FETCH_ENABLED', false));
    }
}
