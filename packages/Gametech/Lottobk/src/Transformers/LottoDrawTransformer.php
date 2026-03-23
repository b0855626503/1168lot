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
            $top3 = $model->result_number['top_3'] ?? '-';
            $bottom2 = $model->result_number['bottom_2'] ?? '-';
            $resultText = '3 บน ' . $top3 . ' / 2 ล่าง ' . $bottom2;
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
}

