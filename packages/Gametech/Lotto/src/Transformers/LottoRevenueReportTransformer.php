<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Contracts\LottoDraw;
use League\Fractal\TransformerAbstract;

class LottoRevenueReportTransformer extends TransformerAbstract
{
    public function transform(LottoDraw $model): array
    {
        $totalBet = (float) ($model->total_bet_amount ?? 0);
        $totalWin = (float) ($model->total_win_amount ?? 0);

        return [
            'market_name' => $model->market->name ?? '-',
            'draw_date' => $model->draw_date ? $model->draw_date->format('d/m/Y') : '-',
            'status' => $this->statusBadge((string) $model->status),
            'ticket_count' => (int) ($model->ticket_count ?? 0),
            'winning_ticket_count' => (int) ($model->winning_ticket_count ?? 0),
            'total_bet_amount' => number_format($totalBet, 2),
            'total_win_amount' => number_format($totalWin, 2),
            'net_revenue' => number_format($totalBet - $totalWin, 2),
        ];
    }

    private function statusBadge(string $status): string
    {
        return match ($status) {
            'draft' => '<span class="badge badge-secondary">ร่าง</span>',
            'open' => '<span class="badge badge-success">เปิดรับ</span>',
            'closed' => '<span class="badge badge-warning">ปิดรับ</span>',
            'resulted' => '<span class="badge badge-info">ประกาศผลแล้ว</span>',
            default => '<span class="badge badge-light">' . $status . '</span>',
        };
    }
}

