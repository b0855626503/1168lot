<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Contracts\LotteryGroup;
use League\Fractal\TransformerAbstract;

class LotteryGroupTransformer extends TransformerAbstract
{
    public function transform(LotteryGroup $model): array
    {
        return [
            'selector' => '<input type="checkbox" class="js-lotto-row-selector-groups" value="' . (int) $model->id . '">',
            'id'         => (int) $model->id,
            'name'       => $model->name,
            'code'       => '<code>' . $model->code . '</code>',
            'sort'       => (int) $model->sort,
            'is_enabled' => '<button type="button" class="btn ' . ($model->is_enabled ? 'btn-success' : 'btn-danger') . ' btn-xs"'
                . ' onclick="editdata(' . $model->id . ',' . ($model->is_enabled ? '0' : '1') . ',\'is_enabled\')">'
                . ($model->is_enabled ? '<i class="fa fa-check"></i> เปิด' : '<i class="fa fa-times"></i> ปิด')
                . '</button>',
            'action' => view('admin::module.lotto.groups.datatables_actions', [
                'id' => $model->id,
            ])->render(),
        ];
    }
}

