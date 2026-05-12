<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Jobs\FillMissingResultArchiveJob;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Services\ArchiveWriterService;
use Gametech\Lotto\Services\ExternalResultFetcherService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FillMissingResultsCommand extends Command
{
    protected $signature = 'lotto:fill-missing-results
        {--market= : Filter by market code (required)}
        {--from= : Start date YYYY-MM-DD (required)}
        {--to= : End date YYYY-MM-DD (required)}
        {--dry-run : Report only, do not write}
        {--sync : Process synchronously instead of dispatching queue jobs}
        {--force : Bypass primary-instance guard (use only on primary)}';

    protected $description = 'Fill missing archive results from external sources';

    public function handle(
        ExternalResultFetcherService $fetcher,
        ArchiveWriterService $writer,
    ): int {
        if (! $this->option('force') && ! $this->isFetchEnabled()) {
            $this->warn('External fetch is disabled on this instance. Set LOTTO_ARCHIVE_FETCH_ENABLED=true or use --force.');

            return self::FAILURE;
        }

        $marketCode = $this->option('market');
        $fromDate = $this->option('from');
        $toDate = $this->option('to');
        $dryRun = (bool) $this->option('dry-run');
        $sync = (bool) $this->option('sync');

        if (! $marketCode || ! $fromDate || ! $toDate) {
            $this->error('--market, --from, and --to are required.');

            return self::FAILURE;
        }

        $market = LotteryMarket::where('code', $marketCode)->first();
        if (! $market) {
            $this->error("Market not found: {$marketCode}");

            return self::FAILURE;
        }

        if (($market->result_mode ?? null) === LotteryMarket::RESULT_MODE_YEEKEE) {
            $this->error("Market {$marketCode} is yeekee — excluded from archive.");

            return self::FAILURE;
        }

        $runId = (string) Str::uuid();
        $mode = $sync ? 'SYNC' : 'QUEUE';
        $this->info("Fill missing results: market={$marketCode}, from={$fromDate}, to={$toDate}, mode={$mode}".($dryRun ? ' (DRY RUN)' : ''));

        $cursor = Carbon::parse($fromDate);
        $end = Carbon::parse($toDate);
        $attempted = 0;
        $dispatched = 0;
        $filled = 0;
        $failed = 0;

        while ($cursor->lte($end)) {
            $date = $cursor->format('Y-m-d');

            $hasArchive = \DB::table('lotto_result_archives')
                ->where('market_code', $marketCode)
                ->where('draw_date', $date)
                ->exists();

            if ($hasArchive) {
                $cursor->addDay();

                continue;
            }

            $attempted++;

            if ($dryRun) {
                $this->line("  [DRY RUN] Would fetch: {$date}");
                $cursor->addDay();

                continue;
            }

            if ($sync) {
                $rows = $fetcher->fetchMissing($marketCode, $date);

                if (! $rows) {
                    $this->warn("  No data for {$date}");
                    $failed++;
                    $cursor->addDay();

                    continue;
                }

                $result = $writer->writeArchive($rows, 'external_fetch', null, $runId);
                $this->line("  {$date}: created={$result['created']}, skipped={$result['skipped']}");
                $filled += $result['created'];
            } else {
                FillMissingResultArchiveJob::dispatch($marketCode, $date, $runId);
                $dispatched++;
            }

            $cursor->addDay();
        }

        $this->info("Done. Run ID: {$runId}");
        $this->table(['Metric', 'Count'], array_filter([
            ['Attempted', $attempted],
            $sync ? ['Filled', $filled] : ['Dispatched', $dispatched],
            ['Failed', $failed],
        ]));

        return self::SUCCESS;
    }

    protected function isFetchEnabled(): bool
    {
        return (bool) (env('LOTTO_ARCHIVE_FETCH_ENABLED', false));
    }
}
