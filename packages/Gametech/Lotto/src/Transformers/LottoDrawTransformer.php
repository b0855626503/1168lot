<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Contracts\LottoDraw;
use League\Fractal\TransformerAbstract;

class LottoDrawTransformer extends TransformerAbstract
{
    public function transform(LottoDraw $model): array
    {
        $statusBadge = $this->getStatusBadge($model->status);
        $resultText = '-';

        if (is_array($model->result_number) && ! empty($model->result_number)) {
            $firstPrize = $model->result_number['first_prize'] ?? '-';
            $last2Digits = $model->result_number['last_2_digits'] ?? ($model->result_number['bottom_2'] ?? '-');
            $top3 = $model->result_number['top_3'] ?? '-';
            $top2 = $model->result_number['top_2'] ?? '-';
            $bottom2 = $model->result_number['bottom_2'] ?? '-';
            $resultText = 'รางวัลที่ 1 ' . $firstPrize
                . ' / เลขท้าย 2 ตัว ' . $last2Digits
                . ' / 3 บน ' . $top3
                . ' / 2 บน ' . $top2
                . ' / 2 ล่าง ' . $bottom2;
        } elseif (! empty($model->result_number)) {
            $resultText = (string) $model->result_number;
        }
        
        return [
            'id'             => (int) $model->id,
            'market_name'    => $model->market->name ?? '-',
            'draw_date'      => $model->draw_date ? $model->draw_date->format('d/m/Y') : '-',
            'open_at'        => $model->open_at ? $model->open_at->format('d/m/Y H:i') : '-',
            'close_at'       => $model->close_at ? $model->close_at->format('d/m/Y H:i') : '-',
            'status'         => $statusBadge,
            'blocked_numbers_count' => $this->renderCountLink('showDrawBlockedNumbers', (int) $model->id, (int) ($model->blocked_numbers_count ?? 0)),
            'tickets_count' => $this->renderCountLink('showDrawTicketList', (int) $model->id, (int) ($model->tickets_count ?? 0)),
            'result_number'  => $resultText,
            'action'         => view('admin::module.lotto.draws.datatables_actions', [
                'id' => $model->id,
                'status' => (string) $model->status,
            ])->render(),
        ];
    }

    private function getStatusBadge(string $status): string
    {
        $badges = [
            'draft'    => '<span class="badge badge-secondary">ร่าง</span>',
            'open'     => '<span class="badge badge-success">เปิดรับ</span>',
            'closed'   => '<span class="badge badge-warning">ปิดรับ</span>',
            'resulted' => '<span class="badge badge-info">ประกาศผล</span>',
        ];
        
        return $badges[$status] ?? '<span class="badge badge-light">' . $status . '</span>';
    }

    private function renderCountLink(string $handlerName, int $drawId, int $count): string
    {
        return '<a href="javascript:void(0);" onclick="' . $handlerName . '(' . $drawId . ')" class="font-weight-bold">' . $count . '</a>';
    }
}
