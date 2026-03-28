<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoResultSource;
use Gametech\Lotto\Services\AutoResult\AutoResultPipelineService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class LottoFetchAutoResultsCommand extends Command
{
    protected $signature = 'lotto:fetch-auto-results
        {--draw-id= : Process only one draw id}
        {--market-id= : Process only one market id}
        {--run-id= : Override execution run id}
        {--expected-draw-date= : Expected draw date for strict context validation (Y-m-d)}
        {--limit=100 : Maximum draws to process per run}
        {--dry-run : Execute pipeline without applying settlement}
        {--manual-retry : Mark this run as manual retry context}';

    protected $description = 'Fetch and apply lotto auto-results for eligible closed draws';

    public function handle(AutoResultPipelineService $pipeline): int
    {
        $now = now((string) config('lotto_auto_result.timezone', (string) config('app.timezone', 'Asia/Bangkok')));
        $runId = (string) ($this->option('run-id') ?: sprintf('cmd_%s', $now->format('YmdHisv')));

        $dryRun = (bool) $this->option('dry-run');
        $manualRetry = (bool) $this->option('manual-retry');
        $limit = max(1, (int) $this->option('limit'));
        $drawId = $this->option('draw-id');
        $marketId = $this->option('market-id');
        $expectedDrawDate = $this->option('expected-draw-date');
        $forceSingleDrawRetry = $manualRetry && $drawId !== null && $drawId !== '';
        $singleDrawStatuses = ($forceSingleDrawRetry && $dryRun)
            ? ['closed', 'resulted']
            : ['closed'];

        if ($forceSingleDrawRetry) {
            $query = LottoDraw::query()
                ->where('id', (int) $drawId)
                ->whereIn('status', $singleDrawStatuses)
                ->limit(1);
        } else {
            $query = LottoDraw::query()
                ->where('status', 'closed')
                ->whereNotNull('result_at')
                ->where('result_at', '<=', $now)
                ->where(function ($q): void {
                    $q->whereNull('result_fetch_status')
                        ->orWhereNotIn('result_fetch_status', ['APPLIED', 'CONFLICT', 'EXHAUSTED']);
                })
                ->orderBy('result_at')
                ->orderBy('id')
                ->limit($limit);

            if ($drawId !== null && $drawId !== '') {
                $query->where('id', (int) $drawId);
            }
        }

        if ($marketId !== null && $marketId !== '') {
            $query->where('market_id', (int) $marketId);
        }

        $draws = $query->get();

        $summary = [
            'run_id' => $runId,
            'selected' => $draws->count(),
            'processed' => 0,
            'skipped_no_source_config' => 0,
            'skipped_not_due' => 0,
            'skipped_backoff' => 0,
            'marked_exhausted' => 0,
            'statuses' => [],
        ];

        foreach ($draws as $draw) {
            if (! $forceSingleDrawRetry) {
                if (! $this->hasConfiguredSourceForMarket((int) $draw->market_id)) {
                    $summary['skipped_no_source_config']++;
                    continue;
                }

                $resultAt = $draw->result_at ? Carbon::parse((string) $draw->result_at) : null;
                if ($resultAt && $now->lt($resultAt)) {
                    $summary['skipped_not_due']++;
                    continue;
                }

                if ($this->isWindowExpired($draw, $now)) {
                    $pipeline->markExhausted($draw);
                    $summary['marked_exhausted']++;
                    continue;
                }

                if ($this->mustSkipByRetryPolicy($pipeline, $draw, $now)) {
                    $summary['skipped_backoff']++;
                    continue;
                }
            }

            $result = $pipeline->processDraw(
                $draw,
                $dryRun,
                $manualRetry,
                $runId,
                is_string($expectedDrawDate) && trim($expectedDrawDate) !== '' ? trim($expectedDrawDate) : null
            );
            $status = (string) ($result['status'] ?? 'VALIDATION_ERROR');

            if (! isset($summary['statuses'][$status])) {
                $summary['statuses'][$status] = 0;
            }
            $summary['statuses'][$status]++;
            $summary['processed']++;

            $draw->refresh();
            $maxAttempts = max(1, (int) config('lotto_auto_result.retry.max_attempts', 27));
            if ((string) $draw->result_fetch_status === 'NOT_READY' && (int) ($draw->result_fetch_attempts ?? 0) >= $maxAttempts) {
                $pipeline->markExhausted($draw);
                $summary['marked_exhausted']++;
            }
        }

        ksort($summary['statuses']);

        $this->info(sprintf(
            'Auto result run=%s selected=%d processed=%d skipped_no_source_config=%d skipped_not_due=%d skipped_backoff=%d marked_exhausted=%d',
            $summary['run_id'],
            $summary['selected'],
            $summary['processed'],
            $summary['skipped_no_source_config'],
            $summary['skipped_not_due'],
            $summary['skipped_backoff'],
            $summary['marked_exhausted']
        ));

        foreach ($summary['statuses'] as $status => $count) {
            $this->line(sprintf('- %s: %d', $status, $count));
        }

        return self::SUCCESS;
    }

    private function mustSkipByRetryPolicy(AutoResultPipelineService $pipeline, LottoDraw $draw, Carbon $now): bool
    {
        if ((string) $draw->result_fetch_status !== 'NOT_READY') {
            return false;
        }

        $maxAttempts = max(1, (int) config('lotto_auto_result.retry.max_attempts', 27));
        if ((int) ($draw->result_fetch_attempts ?? 0) >= $maxAttempts) {
            return false;
        }

        return ! $pipeline->shouldRetryNow($draw, $now);
    }

    private function isWindowExpired(LottoDraw $draw, Carbon $now): bool
    {
        if (! $draw->result_at) {
            return false;
        }

        $maxWindowMinutes = max(1, (int) config('lotto_auto_result.fetch.max_window_minutes', 1440));
        $deadline = Carbon::parse((string) $draw->result_at)->addMinutes($maxWindowMinutes);

        return $now->gt($deadline);
    }
    private function hasConfiguredSourceForMarket(int $marketId): bool
    {
        if ($marketId <= 0) {
            return false;
        }

        return LottoResultSource::query()
            ->where('market_id', $marketId)
            ->exists();
    }

}
