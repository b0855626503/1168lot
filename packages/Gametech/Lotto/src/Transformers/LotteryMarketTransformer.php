<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Contracts\LotteryMarket;
use League\Fractal\TransformerAbstract;

class LotteryMarketTransformer extends TransformerAbstract
{
    public function transform(LotteryMarket $model): array
    {
        return [
            'selector' => '<input type="checkbox" class="js-lotto-row-selector-markets" value="' . (int) $model->id . '">',
            'id'         => (int) $model->id,
            'name'       => $model->name,
            'group_name' => optional($model->group)->name ?? '-',
            'code'       => '<code>' . $model->code . '</code>',
            'is_enabled' => '<button type="button" class="btn ' . ($model->is_enabled ? 'btn-success' : 'btn-danger') . ' btn-xs"'
                . ' onclick="editdata(' . $model->id . ',' . ($model->is_enabled ? '0' : '1') . ',\'is_enabled\')">'
                . ($model->is_enabled ? '<i class="fa fa-check"></i> เปิด' : '<i class="fa fa-times"></i> ปิด')
                . '</button>',
            'action' => view('admin::module.lotto.markets.datatables_actions', [
                'id' => $model->id,
            ])->render(),
        ];
    }
}

