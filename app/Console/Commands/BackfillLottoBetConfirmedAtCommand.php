<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BackfillLottoBetConfirmedAtCommand extends Command
{
    protected $signature = 'lotto:backfill-bet-confirmed-at
        {--chunk=1000 : Chunk size for ticket scan}
        {--dry-run : Preview only, do not write ticket updates}
        {--report= : Optional relative path in storage/app for reconcile json output}';

    protected $description = 'Backfill lotto_tickets.bet_confirmed_at from successful LOTTO_BET wallet transactions';

    public function handle(): int
    {
        if (!Schema::hasTable('lotto_tickets') || !Schema::hasColumn('lotto_tickets', 'bet_confirmed_at')) {
            $this->error('ไม่พบคอลัมน์ lotto_tickets.bet_confirmed_at');
            return self::FAILURE;
        }

        if (!Schema::hasTable('wallet_transactions')) {
            $this->error('ไม่พบตาราง wallet_transactions');
            return self::FAILURE;
        }

        $chunk = max(100, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $totalScanned = 0;
        $totalUpdated = 0;
        $reconcileRows = [];

        DB::table('lotto_tickets')
            ->select(['id'])
            ->whereNull('bet_confirmed_at')
            ->orderBy('id')
            ->chunkById($chunk, function (Collection $tickets) use (&$totalScanned, &$totalUpdated, &$reconcileRows, $dryRun): void {
                foreach ($tickets as $ticket) {
                    $ticketId = (int) $ticket->id;
                    $totalScanned++;

                    $matched = DB::table('wallet_transactions')
                        ->where('ref_type', 'LOTTO_BET')
                        ->where('direction', 'DEBIT')
                        ->where('status', 'SUCCESS')
                        ->where('ref_id', $ticketId)
                        ->orderBy('created_at')
                        ->orderBy('id')
                        ->get(['id', 'created_at']);

                    $matchedCount = $matched->count();
                    $chosen = $matched->first();
                    $chosenId = $chosen ? (int) $chosen->id : null;
                    $chosenAt = $chosen ? (string) $chosen->created_at : null;

                    if ($matchedCount === 0) {
                        $reconcileRows[] = [
                            'ticket_id' => $ticketId,
                            'matched_wallet_count' => 0,
                            'chosen_wallet_transaction_id' => null,
                            'issue_type' => 'missing_success_pair',
                        ];
                        continue;
                    }

                    if ($matchedCount > 1) {
                        $reconcileRows[] = [
                            'ticket_id' => $ticketId,
                            'matched_wallet_count' => $matchedCount,
                            'chosen_wallet_transaction_id' => $chosenId,
                            'issue_type' => 'duplicate_success_pair',
                        ];
                    }

                    if ($dryRun || !$chosenAt) {
                        continue;
                    }

                    DB::table('lotto_tickets')
                        ->where('id', $ticketId)
                        ->whereNull('bet_confirmed_at')
                        ->update([
                            'bet_confirmed_at' => $chosenAt,
                            'updated_at' => now(),
                        ]);

                    $totalUpdated++;
                }
            }, 'id');

        $reportPath = (string) ($this->option('report') ?: ('reconcile/lotto_bet_confirmed_at_' . now()->format('Ymd_His') . '.json'));

        if (!empty($reconcileRows)) {
            Storage::disk('local')->put($reportPath, json_encode($reconcileRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $this->info(sprintf('scan=%d updated=%d issues=%d dry_run=%s', $totalScanned, $totalUpdated, count($reconcileRows), $dryRun ? 'Y' : 'N'));

        if (!empty($reconcileRows)) {
            $this->line('reconcile_report=storage/app/' . $reportPath);
            $this->table(
                ['ticket_id', 'matched_wallet_count', 'chosen_wallet_transaction_id', 'issue_type'],
                collect($reconcileRows)->take(20)->all()
            );
        }

        return self::SUCCESS;
    }
}
