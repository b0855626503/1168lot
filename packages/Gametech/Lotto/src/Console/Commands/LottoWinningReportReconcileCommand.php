<?php

namespace Gametech\Lotto\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LottoWinningReportReconcileCommand extends Command
{
    protected $signature = 'lotto:winning-report:reconcile {--round_id=} {--settlement_batch_id=}';

    protected $description = 'Reconcile settlement batch totals against materialized lotto_winnings';

    public function handle(): int
    {
        $batchQuery = DB::table('settlement_batches')->orderBy('id');

        $batchId = $this->option('settlement_batch_id');
        if ($batchId !== null && $batchId !== '') {
            $batchQuery->where('id', (int) $batchId);
        }

        $roundId = $this->option('round_id');
        if ($roundId !== null && $roundId !== '') {
            $batchQuery->where('draw_id', (int) $roundId);
        }

        $batches = $batchQuery->get();
        if ($batches->isEmpty()) {
            $this->warn('No settlement batch found');

            return self::FAILURE;
        }

        $mismatchCount = 0;

        foreach ($batches as $batch) {
            $winningQuery = DB::table('lotto_winnings')
                ->where('settlement_batch_id', (int) $batch->id);

            $actualWinningCount = (int) $winningQuery->count();
            $actualStake = round((float) $winningQuery->sum('stake'), 2);
            $actualPayout = round((float) $winningQuery->sum(DB::raw('COALESCE(payout, 0)')), 2);

            $expectedWinningCount = (int) ($batch->total_winning_records ?? 0);
            $expectedStake = round((float) ($batch->total_stake ?? 0), 2);
            $expectedPayout = round((float) ($batch->total_payout ?? 0), 2);

            $isMismatch = $actualWinningCount !== $expectedWinningCount
                || $actualStake !== $expectedStake
                || $actualPayout !== $expectedPayout;

            if ($isMismatch) {
                $mismatchCount++;
            }

            $this->line(json_encode([
                'batch_id' => (int) $batch->id,
                'draw_id' => (int) $batch->draw_id,
                'expected' => [
                    'winning_records' => $expectedWinningCount,
                    'total_stake' => $expectedStake,
                    'total_payout' => $expectedPayout,
                ],
                'actual' => [
                    'winning_records' => $actualWinningCount,
                    'total_stake' => $actualStake,
                    'total_payout' => $actualPayout,
                ],
                'is_mismatch' => $isMismatch,
            ], JSON_UNESCAPED_UNICODE));
        }

        if ($mismatchCount > 0) {
            $this->error(sprintf('Found %d mismatch batch(es)', $mismatchCount));

            return self::FAILURE;
        }

        $this->info('Reconcile passed: no mismatch');

        return self::SUCCESS;
    }
}
