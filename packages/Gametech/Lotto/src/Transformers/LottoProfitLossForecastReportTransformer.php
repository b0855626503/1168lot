<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Enums\BetType;
use League\Fractal\TransformerAbstract;

class LottoProfitLossForecastReportTransformer extends TransformerAbstract
{
    public function transform($row): array
    {
        $totalBet = (float) ($row->total_bet_amount ?? 0);
        $riskAmount = (float) ($row->max_risk_amount ?? 0);
        $drawStatus = (string) ($row->draw_status ?? '');

        return [
            'draw_date' => $row->draw_date ? date('d/m/Y', strtotime((string) $row->draw_date)) : '-',
            'market_name' => $this->formatMarket((string) ($row->market_name ?? '-'), (string) ($row->market_logo ?? ''), (string) ($row->market_icon ?? '')),
            'bet_type' => BetType::label((string) ($row->bet_type ?? '')),
            'total_bet_amount' => number_format($totalBet, 2),
            'max_risk_amount' => number_format($riskAmount, 2),
            'forecast_net' => number_format($totalBet - $riskAmount, 2),
            'draw_status' => $this->statusBadge($drawStatus),
        ];
    }

    private function formatMarket(string $marketName, string $logo, string $icon): string
    {
        $safeName = e(trim($marketName) !== '' ? $marketName : '-');
        $image = trim($logo) !== '' ? $logo : $icon;

        if ($image === '') {
            return $safeName;
        }

        return '<span class="d-inline-flex align-items-center">'
            . '<img src="' . e($image) . '" alt="" style="width:18px;height:18px;object-fit:contain;margin-right:6px;" />'
            . '<span>' . $safeName . '</span>'
            . '</span>';
    }

    private function statusBadge(string $status): string
    {
        return match ($status) {
            'draft' => '<span class="badge badge-secondary">ร่าง</span>',
            'open' => '<span class="badge badge-success">เปิดรับ</span>',
            'closed' => '<span class="badge badge-warning">ปิดรับ</span>',
            'resulted' => '<span class="badge badge-info">ประกาศผลแล้ว</span>',
            default => '<span class="badge badge-light">' . e($status !== '' ? $status : '-') . '</span>',
        };
    }
}
