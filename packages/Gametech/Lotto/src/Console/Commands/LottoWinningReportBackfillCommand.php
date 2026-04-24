<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Services\SettlementService;
use Illuminate\Console\Command;

class LottoWinningReportBackfillCommand extends Command
{
    protected $signature = 'lotto:winning-report:backfill {--round_id=} {--start_date=} {--end_date=} {--dry-run}';

    protected $description = 'Backfill winning report materialized rows for resulted draws';

    public function handle(SettlementService $settlementService): int
    {
        $drawQuery = LottoDraw::query()->where('status', 'resulted')->orderBy('id');

        $roundId = $this->option('round_id');
        if ($roundId !== null && $roundId !== '') {
            $drawQuery->where('id', (int) $roundId);
        }

        $startDate = $this->option('start_date');
        if (is_string($startDate) && $startDate !== '') {
            $drawQuery->whereDate('draw_date', '>=', $startDate);
        }

        $endDate = $this->option('end_date');
        if (is_string($endDate) && $endDate !== '') {
            $drawQuery->whereDate('draw_date', '<=', $endDate);
        }

        $draws = $drawQuery->get();

        $this->info(sprintf('Found %d draw(s) for backfill', $draws->count()));

        if ((bool) $this->option('dry-run')) {
            return self::SUCCESS;
        }

        foreach ($draws as $draw) {
            $resultNumber = is_array($draw->result_number) ? $draw->result_number : [];
            if ($resultNumber === []) {
                $this->warn(sprintf('Skip draw=%d (empty result_number)', (int) $draw->id));

                continue;
            }

            $summary = $settlementService->settleDraw($draw, $resultNumber, 'backfill');

            $this->line(sprintf(
                'draw=%d tickets=%d winning_items=%d total_win=%0.2f',
                (int) $draw->id,
                (int) ($summary['ticket_count'] ?? 0),
                (int) ($summary['winning_item_count'] ?? 0),
                (float) ($summary['total_win_amount'] ?? 0)
            ));
        }

        return self::SUCCESS;
    }
}
