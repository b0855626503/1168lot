<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Services\AutoResult\AutoResultPipelineService;
use Gametech\Lotto\Services\Relay\LotteryRelayRuntime;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LottoCloneAutoPullThaiResultsCommand extends Command
{
    protected $signature = 'lotto:clone-auto-pull-thai-results
        {--dry-run : Execute without applying result}';

    protected $description = 'Pull Thai lottery results on clone sites when primary has no relay to offer';

    private const LOCK_PREFIX = 'lotto:clone-auto-pull';

    private const LOCK_TTL_SECONDS = 300;

    public function handle(
        LotteryRelayRuntime $relayRuntime,
        AutoResultPipelineService $pipeline
    ): int {
        if (! $relayRuntime->isClone()) {
            $this->line('Skipped: relay mode is not clone.');

            return self::SUCCESS;
        }

        if (! (bool) config('lotto_auto_result.clone_auto_pull.enabled', false)) {
            $this->line('Skipped: clone auto pull is disabled.');

            return self::SUCCESS;
        }

        $groupIds = (array) config('lotto_auto_result.clone_auto_pull.group_ids', []);
        $groupIds = array_values(array_filter(array_map('intval', $groupIds), static fn (int $id): bool => $id > 0));

        if ($groupIds === []) {
            $this->line('Skipped: no group IDs configured.');

            return self::SUCCESS;
        }

        $delayMinutes = max(0, (int) config('lotto_auto_result.clone_auto_pull.delay_minutes', 1));
        $dryRun = (bool) $this->option('dry-run');
        $now = Carbon::now((string) config('lotto_auto_result.timezone', (string) config('app.timezone', 'Asia/Bangkok')));
        $cutoff = $now->copy()->subMinutes($delayMinutes);

        $draws = LottoDraw::query()
            ->join('lotto_markets', 'lotto_markets.id', '=', 'lotto_draws.market_id')
            ->whereIn('lotto_markets.group_id', $groupIds)
            ->where('lotto_draws.status', 'closed')
            ->whereNotNull('lotto_draws.result_at')
            ->where('lotto_draws.result_at', '<=', $cutoff)
            ->whereNull('lotto_draws.result_number')
            ->select('lotto_draws.*')
            ->orderBy('lotto_draws.result_at')
            ->orderBy('lotto_draws.id')
            ->limit(50)
            ->get();

        if ($draws->isEmpty()) {
            $this->line('No eligible draws found.');

            return self::SUCCESS;
        }

        $processed = 0;
        $skipped = 0;

        foreach ($draws as $draw) {
            $lockKey = self::LOCK_PREFIX.':'.$draw->id;

            $lock = Cache::lock($lockKey, self::LOCK_TTL_SECONDS);

            if (! $lock->get()) {
                $skipped++;

                continue;
            }

            try {
                $fresh = $draw->fresh();
                if (
                    ! $fresh
                    || $fresh->status === 'resulted'
                    || $fresh->result_number !== null
                ) {
                    $skipped++;

                    continue;
                }

                $result = $pipeline->processDraw($fresh, $dryRun);
                $status = (string) ($result['status'] ?? 'UNKNOWN');

                Log::channel('daily')->info('lotto:clone-auto-pull-thai-results', [
                    'draw_id' => $draw->id,
                    'status' => $status,
                    'dry_run' => $dryRun,
                ]);

                $this->line("Draw {$draw->id}: {$status}");
                $processed++;
            } catch (\Throwable $e) {
                Log::channel('daily')->warning('lotto:clone-auto-pull-thai-results exception', [
                    'draw_id' => $draw->id,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                $lock->release();
            }
        }

        $this->line("Done. processed={$processed} skipped={$skipped}");

        return self::SUCCESS;
    }
}
