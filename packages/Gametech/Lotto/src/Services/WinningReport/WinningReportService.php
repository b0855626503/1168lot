<?php

namespace Gametech\Lotto\Services\WinningReport;

use Gametech\Lotto\Services\WinningReport\Queries\WinningReportBetsQuery;
use Gametech\Lotto\Services\WinningReport\Queries\WinningReportSummaryQuery;
use Gametech\Lotto\Services\WinningReport\Queries\WinningReportUsersQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WinningReportService
{
    public function __construct(
        private ?WinningReportSummaryQuery $summaryQuery = null,
        private ?WinningReportUsersQuery $usersQuery = null,
        private ?WinningReportBetsQuery $betsQuery = null
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters): array
    {
        $drawId = isset($filters['draw_id']) ? (int) $filters['draw_id'] : null;
        if ($drawId !== null && $drawId > 0) {
            $this->assertDrawExists($drawId);
        }

        $payload = $this->summaryQuery()->run($filters);

        return [
            'summary' => [
                'total_stake' => $payload['total_stake'],
                'total_payout' => $payload['total_payout'],
                'net_profit_loss' => $payload['net_profit_loss'],
                'winner_count' => $payload['winner_count'],
                'winning_ticket_count' => $payload['winning_ticket_count'],
                'settlement_status' => $payload['settlement_status'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function users(int $drawId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $this->assertDrawReadyForReport($drawId);

        return $this->usersQuery()->run($drawId, $filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function bets(int $drawId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $this->assertDrawReadyForReport($drawId);

        return $this->betsQuery()->run($drawId, $filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function exportRows(int $drawId, array $filters): array
    {
        $this->assertDrawReadyForReport($drawId);

        $query = DB::table('lotto_winnings')->where('draw_id', $drawId);

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['bet_type'])) {
            $query->where('bet_type', (string) $filters['bet_type']);
        }

        if (! empty($filters['number'])) {
            $query->where('number', (string) $filters['number']);
        }

        $rows = $query
            ->orderBy('id')
            ->get()
            ->map(function ($row): array {
                $isPending = (string) $row->status === 'pending';

                return [
                    'round_id' => (int) $row->draw_id,
                    'user_id' => (int) $row->user_id,
                    'username' => (string) ($row->username ?? ''),
                    'ticket_no' => (string) ($row->ticket_no ?? ''),
                    'bet_type' => (string) $row->bet_type,
                    'number' => (string) $row->number,
                    'stake' => round((float) $row->stake, 2),
                    'odds' => round((float) $row->odds, 4),
                    'payout' => $isPending ? null : round((float) ($row->payout ?? 0), 2),
                    'net_profit' => $isPending ? null : round((float) ($row->net_profit ?? 0), 2),
                    'result_number' => (string) ($row->result_number ?? ''),
                    'matched_rule' => (string) ($row->matched_rule ?? ''),
                    'status' => (string) $row->status,
                    'settlement_batch_id' => (int) $row->settlement_batch_id,
                    'settled_at' => $row->settled_at,
                    'credited_at' => $row->credited_at,
                ];
            })
            ->values()
            ->all();

        return [
            'rows' => $rows,
            'count' => count($rows),
            'total_stake' => round((float) collect($rows)->sum('stake'), 2),
            'total_payout' => round((float) collect($rows)->whereNotNull('payout')->sum('payout'), 2),
        ];
    }

    public function assertDrawReadyForReport(int $drawId): void
    {
        $this->assertDrawExists($drawId);

        $batch = DB::table('settlement_batches')
            ->where('draw_id', $drawId)
            ->orderByDesc('id')
            ->first();

        if (! $batch) {
            throw new RuntimeException('SETTLEMENT_NOT_FINALIZED');
        }

        if ((string) $batch->status === 'pending') {
            throw new RuntimeException('SETTLEMENT_PENDING');
        }

        if ((string) $batch->status !== 'settled') {
            throw new RuntimeException('SETTLEMENT_NOT_FINALIZED');
        }
    }

    private function assertDrawExists(int $drawId): void
    {
        $exists = DB::table('lotto_draws')
            ->where('id', $drawId)
            ->exists();

        if (! $exists) {
            throw new RuntimeException('ROUND_NOT_FOUND');
        }
    }

    private function summaryQuery(): WinningReportSummaryQuery
    {
        if ($this->summaryQuery instanceof WinningReportSummaryQuery) {
            return $this->summaryQuery;
        }

        $this->summaryQuery = app(WinningReportSummaryQuery::class);

        return $this->summaryQuery;
    }

    private function usersQuery(): WinningReportUsersQuery
    {
        if ($this->usersQuery instanceof WinningReportUsersQuery) {
            return $this->usersQuery;
        }

        $this->usersQuery = app(WinningReportUsersQuery::class);

        return $this->usersQuery;
    }

    private function betsQuery(): WinningReportBetsQuery
    {
        if ($this->betsQuery instanceof WinningReportBetsQuery) {
            return $this->betsQuery;
        }

        $this->betsQuery = app(WinningReportBetsQuery::class);

        return $this->betsQuery;
    }
}
