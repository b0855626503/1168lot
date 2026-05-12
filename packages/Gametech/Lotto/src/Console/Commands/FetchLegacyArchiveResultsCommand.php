<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Repositories\LegacyArchiveResultRepository;
use Gametech\Lotto\Services\LegacyArchiveSourceClient;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class FetchLegacyArchiveResultsCommand extends Command
{
    protected $signature = 'lotto:legacy-results:fetch
        {--from= : Start date YYYY-MM-DD}
        {--to= : End date YYYY-MM-DD}
        {--type= : Single market type (e.g. egx30); if omitted, loop all known types}
        {--today : Shorthand for --from=today --to=today}
        {--force : Overwrite existing success rows}
        {--limit= : Maximum rows to process (default unlimited)}
        {--sleep=0 : Milliseconds to sleep between fetch calls}';

    protected $description = 'Fetch legacy archive results and upsert into lotto_result_archive_legacy_results';

    /**
     * All market types this command iterates when --type is omitted.
     *
     * @var array<int, string>
     */
    protected array $knownTypes = [
        'egx30',
        'thai_gov',
        'hanoi',
        'hanoi_special',
        'vip_hanoi',
        'laos',
        'malay',
        'singapore',
        'nikkei',
        'dow_jones',
    ];

    public function handle(LegacyArchiveResultRepository $repository, LegacyArchiveSourceClient $client): int
    {
        $today = $this->option('today');
        $from = $this->option('from');
        $to = $this->option('to');

        if ($today) {
            $from = Carbon::today()->format('Y-m-d');
            $to = Carbon::today()->format('Y-m-d');
        } elseif (! $from || ! $to) {
            $this->error('You must provide --from and --to, or use --today.');

            return self::FAILURE;
        }

        try {
            $fromDate = Carbon::createFromFormat('Y-m-d', (string) $from);
            $toDate = Carbon::createFromFormat('Y-m-d', (string) $to);
        } catch (\Throwable $e) {
            $this->error('Invalid date format. Expected YYYY-MM-DD.');

            return self::FAILURE;
        }

        if ($fromDate->format('Y-m-d') !== (string) $from || $toDate->format('Y-m-d') !== (string) $to) {
            $this->error('Invalid date. Expected a real calendar date in YYYY-MM-DD format.');

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $limitRaw = $this->option('limit');
        $limit = $limitRaw !== null ? (int) $limitRaw : null;
        $sleepMs = max(0, (int) ($this->option('sleep') ?? 0));
        $typeFilter = $this->option('type');
        $types = $typeFilter ? [(string) $typeFilter] : $this->knownTypes;

        $totalUpserted = 0;
        $limitReached = false;

        $current = $fromDate->copy()->startOfDay();
        $end = $toDate->copy()->startOfDay();

        if ($current->gt($end)) {
            $this->error('--from date must not be after --to date.');

            return self::FAILURE;
        }

        while ($current->lte($end) && ! $limitReached) {
            $dateStr = $current->format('Y-m-d');

            foreach ($types as $type) {
                if ($limit !== null && $totalUpserted >= $limit) {
                    $limitReached = true;
                    break;
                }

                $this->line(sprintf('Processing type=%s date=%s', $type, $dateStr));

                $rows = $this->fetchForDate($type, $dateStr, $client);

                foreach ($rows as $row) {
                    if ($limit !== null && $totalUpserted >= $limit) {
                        $limitReached = true;
                        break;
                    }

                    $result = $repository->upsertWithResult($row, $force);
                    $totalUpserted++;

                    if ($result->wasWritten() && $result->model->fetch_status === 'success') {
                        Cache::increment('lotto:archive:'.$type.':version');
                    }
                }

                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }

            $current->addDay();
        }

        $this->line(sprintf('Done. Total upserted: %d', $totalUpserted));

        return self::SUCCESS;
    }

    /**
     * Fetch raw result rows for a given type and date via the source client.
     *
     * Returns an array of mapped field arrays suitable for repository->upsert().
     *
     * @return array<int, array<string, mixed>>
     */
    protected function fetchForDate(string $type, string $date, LegacyArchiveSourceClient $client): array
    {
        return $client->fetch($type, $date);
    }
}
