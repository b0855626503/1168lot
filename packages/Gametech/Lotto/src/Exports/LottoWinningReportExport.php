<?php

namespace Gametech\Lotto\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LottoWinningReportExport implements FromArray, WithHeadings
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(private array $rows) {}

    public function headings(): array
    {
        return [
            'round_id',
            'user_id',
            'username',
            'ticket_no',
            'bet_type',
            'number',
            'stake',
            'odds',
            'payout',
            'net_profit',
            'result_number',
            'matched_rule',
            'status',
            'settlement_batch_id',
            'settled_at',
            'credited_at',
        ];
    }

    public function array(): array
    {
        return collect($this->rows)
            ->map(static function (array $row): array {
                return [
                    $row['round_id'] ?? null,
                    $row['user_id'] ?? null,
                    $row['username'] ?? null,
                    $row['ticket_no'] ?? null,
                    $row['bet_type'] ?? null,
                    $row['number'] ?? null,
                    $row['stake'] ?? null,
                    $row['odds'] ?? null,
                    $row['payout'] ?? null,
                    $row['net_profit'] ?? null,
                    $row['result_number'] ?? null,
                    $row['matched_rule'] ?? null,
                    $row['status'] ?? null,
                    $row['settlement_batch_id'] ?? null,
                    $row['settled_at'] ?? null,
                    $row['credited_at'] ?? null,
                ];
            })
            ->values()
            ->all();
    }
}
