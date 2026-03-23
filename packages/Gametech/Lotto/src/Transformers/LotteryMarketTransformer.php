<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Contracts\LotteryMarket;
use League\Fractal\TransformerAbstract;

class LotteryMarketTransformer extends TransformerAbstract
{
    public function transform(LotteryMarket $model): array
    {
        return [
            'id'         => (int) $model->id,
            'name'       => $model->name,
            'group_name' => optional($model->group)->name ?? '-',
            'code'       => '<code>' . $model->code . '</code>',
            'draw_mode'  => $this->drawModeLabel((string) ($model->draw_mode ?? 'manual')),
            'result_url' => $model->result_url
                ? '<a href="' . e($model->result_url) . '" target="_blank">ลิงก์ผล</a>'
                : '-',
            'is_enabled' => '<button type="button" class="btn ' . ($model->is_enabled ? 'btn-success' : 'btn-danger') . ' btn-xs"'
                . ' onclick="editdata(' . $model->id . ',' . ($model->is_enabled ? '0' : '1') . ',\'is_enabled\')">'
                . ($model->is_enabled ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>')
                . '</button>',
            'action' => view('admin::module.lotto.markets.datatables_actions', [
                'id' => $model->id,
            ])->render(),
        ];
    }

    private function drawModeLabel(string $mode): string
    {
        if ($mode === 'daily') {
            return 'Auto ทุกวัน';
        }

        if ($mode === 'weekdays') {
            return 'Auto จันทร์-ศุกร์';
        }

        return 'Manual';
    }
}
