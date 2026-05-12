<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Services\ArchiveNormalizerService;
use Gametech\Lotto\Services\ArchiveWriterService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MirrorExistingResultedDrawsCommand extends Command
{
    protected $signature = 'lotto:mirror-result-archives
        {--market= : Filter by market code}
        {--from= : Start date (YYYY-MM-DD)}
        {--to= : End date (YYYY-MM-DD)}
        {--chunk=100 : Chunk size for batch processing}
        {--mode=missing-only : missing-only|sync}';

    protected $description = 'Mirror already-resulted draws into lotto_result_archives';

    public function handle(
        ArchiveNormalizerService $normalizer,
        ArchiveWriterService $writer,
    ): int {
        $runId = (string) Str::uuid();
        $mode = $this->option('mode');
        $chunkSize = (int) $this->option('chunk');

        $query = LottoDraw::with(['market', 'betSettings'])
            ->where('status', 'resulted')
            ->whereNotNull('result_number');

        if ($market = $this->option('market')) {
            $query->whereHas('market', fn ($q) => $q->where('code', $market));
        }

        if ($from = $this->option('from')) {
            $query->where('draw_date', '>=', $from);
        }

        if ($to = $this->option('to')) {
            $query->where('draw_date', '<=', $to);
        }

        $totalDraws = $query->count();

        if ($totalDraws === 0) {
            $this->info('No resulted draws found.');

            return self::SUCCESS;
        }

        $this->info("Mirroring {$totalDraws} resulted draws to archive (mode={$mode}, run={$runId})");

        $bar = $this->output->createProgressBar($totalDraws);

        $totalCreated = 0;
        $totalSkipped = 0;
        $totalCorrected = 0;
        $totalLogs = 0;
        $skippedUnknownBetType = 0;

        $query->chunk($chunkSize, function ($draws) use (
            $normalizer, $writer, $runId, $mode, $bar,
            &$totalCreated, &$totalSkipped, &$totalCorrected, &$totalLogs, &$skippedUnknownBetType,
        ): void {
            foreach ($draws as $draw) {
                $rows = $normalizer->normalizeDraw($draw);

                if (empty($rows)) {
                    if ($draw->result_number !== null && count($draw->betSettings) > 0) {
                        $skippedUnknownBetType++;
                    }
                    $bar->advance();

                    continue;
                }

                if ($mode === 'missing-only') {
                    $rows = $this->filterOnlyMissing($rows, $draw->id);
                }

                if (empty($rows)) {
                    $bar->advance();

                    continue;
                }

                $result = $writer->writeArchive($rows, 'internal_mirror', $draw->id, $runId);

                $totalCreated += $result['created'];
                $totalSkipped += $result['skipped'];
                $totalCorrected += $result['corrected'];
                $totalLogs += $result['logs'];

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Draws processed', $totalDraws],
                ['Created', $totalCreated],
                ['Skipped (already exists)', $totalSkipped],
                ['Corrected (hash mismatch)', $totalCorrected],
                ['Log entries', $totalLogs],
                ['Skipped (unknown bet_type)', $skippedUnknownBetType],
            ]
        );

        $this->info("Run ID: {$runId}");

        return self::SUCCESS;
    }

    protected function filterOnlyMissing(array $rows, int $drawId): array
    {
        $missing = [];

        foreach ($rows as $row) {
            $drawKey = $row['draw_key'];
            $exists = \DB::table('lotto_result_archives')
                ->where('source_draw_id', $drawId)
                ->where('draw_key', $drawKey)
                ->exists();

            if (! $exists) {
                $missing[] = $row;
            }
        }

        return $missing;
    }
}
