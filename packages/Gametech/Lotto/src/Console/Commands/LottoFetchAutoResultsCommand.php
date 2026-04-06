<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoResultSource;
use Gametech\Lotto\Services\AutoResult\AutoResultPipelineService;
use Gametech\Lotto\Services\AutoResultHardeningService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

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

    public function handle(AutoResultPipelineService $pipeline, AutoResultHardeningService $hardening): int
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
            'skipped_rate_limit' => 0,
            'marked_exhausted' => 0,
            'unhandled_draw_exceptions' => 0,
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
            }

            try {
                $result = $pipeline->processDraw(
                    $draw,
                    $dryRun,
                    $manualRetry,
                    $runId,
                    is_string($expectedDrawDate) && trim($expectedDrawDate) !== '' ? trim($expectedDrawDate) : null
                );
                $status = (string) ($result['status'] ?? 'VALIDATION_ERROR');
                if (($result['skipped_backoff'] ?? false) === true) {
                    $summary['skipped_backoff']++;
                }
                if (($result['skipped_rate_limit'] ?? false) === true) {
                    $summary['skipped_rate_limit']++;
                }
            } catch (\Throwable $e) {
                $summary['unhandled_draw_exceptions']++;

                try {
                    $draw->refresh();
                } catch (\Throwable $refreshException) {
                    // Keep best-effort DB trace even if refresh fails.
                }

                $hardening->recordUnhandledException($draw, [
                    'run_id' => $runId,
                    'source_id' => $draw->result_source_id,
                    'attempt_no' => (int) ($draw->result_fetch_attempts ?? 1),
                    'pipeline_stage' => 'process_draw_exception',
                    'error_stage' => 'COMMAND',
                    'exception_class' => get_class($e),
                    'error_message' => $e->getMessage(),
                ]);

                Log::error('LOTTO_AUTO_RESULT_DRAW_EXCEPTION', [
                    'run_id' => $runId,
                    'draw_id' => (int) $draw->id,
                    'market_id' => (int) $draw->market_id,
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);

                $status = 'UNHANDLED_EXCEPTION';
            }

            if (! isset($summary['statuses'][$status])) {
                $summary['statuses'][$status] = 0;
            }
            $summary['statuses'][$status]++;
            $summary['processed']++;

            $draw->refresh();
        }

        ksort($summary['statuses']);

        $this->info(sprintf(
            'Auto result run=%s selected=%d processed=%d skipped_no_source_config=%d skipped_not_due=%d skipped_backoff=%d skipped_rate_limit=%d marked_exhausted=%d unhandled_draw_exceptions=%d',
            $summary['run_id'],
            $summary['selected'],
            $summary['processed'],
            $summary['skipped_no_source_config'],
            $summary['skipped_not_due'],
            $summary['skipped_backoff'],
            $summary['skipped_rate_limit'],
            $summary['marked_exhausted'],
            $summary['unhandled_draw_exceptions']
        ));

        foreach ($summary['statuses'] as $status => $count) {
            $this->line(sprintf('- %s: %d', $status, $count));
        }

        return self::SUCCESS;
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
