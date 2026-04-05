<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Enums\BetType;
use League\Fractal\TransformerAbstract;

class LottoBlockedNumbersReportTransformer extends TransformerAbstract
{
    public function transform($row): array
    {
        return [
            'draw_date' => $row->draw_date ? date('d/m/Y', strtotime((string) $row->draw_date)) : '-',
            'market_name' => $this->formatMarket((string) ($row->market_name ?? '-'), (string) ($row->market_logo ?? ''), (string) ($row->market_icon ?? '')),
            'bet_type' => BetType::label((string) ($row->bet_type ?? '')),
            'number' => '<code>' . e((string) ($row->number ?? '-')) . '</code>',
            'mode' => (string) ($row->mode ?? '') === 'limit_future'
                ? '<span class="badge badge-warning">จำกัดอนาคต</span>'
                : '<span class="badge badge-danger">อั้น</span>',
            'blocked_at' => $row->blocked_at ? date('d/m/Y H:i', strtotime((string) $row->blocked_at)) : '-',
            'updated_at' => $row->updated_at ? date('d/m/Y H:i', strtotime((string) $row->updated_at)) : '-',
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
}
