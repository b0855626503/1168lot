<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoResultArchive;
use Gametech\Lotto\Services\ArchiveChecksumService;
use Gametech\Lotto\Services\ArchiveNormalizerService;
use Gametech\Lotto\Services\ArchiveWriterService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ReconcileResultArchiveCommand extends Command
{
    protected $signature = 'lotto:reconcile-result-archive
        {--market= : Market code (required)}
        {--from= : Start date YYYY-MM-DD (required)}
        {--to= : End date YYYY-MM-DD (required)}
        {--fix : Auto-correct mismatches (requires --yes)}
        {--yes : Confirm --fix operation}
        {--source-priority=internal : internal|external}';

    protected $description = 'Reconcile archive results against source of truth';

    public function handle(
        ArchiveChecksumService $checksum,
        ArchiveNormalizerService $normalizer,
        ArchiveWriterService $writer,
    ): int {
        $marketCode = $this->option('market');
        $fromDate = $this->option('from');
        $toDate = $this->option('to');
        $fix = (bool) $this->option('fix');
        $yes = (bool) $this->option('yes');

        if (! $marketCode || ! $fromDate || ! $toDate) {
            $this->error('--market, --from, and --to are required.');

            return self::FAILURE;
        }

        $from = Carbon::parse($fromDate);
        $to = Carbon::parse($toDate);

        if ($from->diffInDays($to) > 366) {
            $this->error('Date range must not exceed 366 days.');

            return self::FAILURE;
        }

        if ($fix && ! $yes) {
            $this->error('--fix requires --yes confirmation.');

            return self::FAILURE;
        }

        $sourcePriority = $this->option('source-priority');
        if (! in_array($sourcePriority, ['internal', 'external'], true)) {
            $this->error('--source-priority must be "internal" or "external".');

            return self::FAILURE;
        }

        $mode = $fix ? 'FIX' : 'DRY RUN';
        $this->info("Reconcile: market={$marketCode}, from={$fromDate}, to={$toDate}, mode={$mode}");

        $runId = (string) Str::uuid();
        $archives = LottoResultArchive::where('market_code', $marketCode)
            ->whereDate('draw_date', '>=', $fromDate)
            ->whereDate('draw_date', '<=', $toDate)
            ->orderBy('draw_date')
            ->orderBy('draw_key')
            ->get();

        $drawIds = $archives->pluck('source_draw_id')->unique()->filter()->values()->all();
        $draws = LottoDraw::with(['market', 'betSettings'])
            ->whereIn('id', $drawIds)
            ->get()
            ->keyBy('id');

        $matched = 0;
        $mismatched = 0;
        $fixed = 0;
        $skipped = 0;

        foreach ($archives as $archive) {
            if ($archive->source_type === 'external_fetch' && $sourcePriority === 'internal') {
                $this->line("  SKIP: {$archive->draw_date}/{$archive->draw_key} (external_fetch, internal priority)");
                $skipped++;

                continue;
            }

            if (! $archive->source_draw_id) {
                $matched++;

                continue;
            }

            $draw = $draws->get($archive->source_draw_id);

            if (! $draw || $draw->status !== 'resulted') {
                $this->warn("  WARN: {$archive->draw_date}/{$archive->draw_key} — source draw missing/not resulted");
                $skipped++;

                continue;
            }

            $rows = $normalizer->normalizeDraw($draw);
            $currentRow = collect($rows)->firstWhere('draw_key', $archive->draw_key);

            if (! $currentRow) {
                $this->warn("  WARN: {$archive->draw_date}/{$archive->draw_key} — draw_key no longer produced by normalizer");
                $skipped++;

                continue;
            }

            $currentHash = $currentRow['result_hash'];

            if ($archive->result_hash === $currentHash) {
                $matched++;

                continue;
            }

            $mismatched++;
            $this->line("  MISMATCH: {$archive->draw_date}/{$archive->draw_key} — archive={$archive->result_hash}, source={$currentHash}");

            if ($fix) {
                $result = $writer->writeArchive([$currentRow], 'internal_mirror', $archive->source_draw_id, $runId);
                if ($result['corrected'] > 0) {
                    $fixed++;
                    $this->info('    FIXED');
                }
            }
        }

        $this->info("Run ID: {$runId}");
        $this->table(['Metric', 'Count'], [
            ['Matched', $matched],
            ['Mismatched', $mismatched],
            ['Fixed', $fixed],
            ['Skipped', $skipped],
        ]);

        return self::SUCCESS;
    }
}
