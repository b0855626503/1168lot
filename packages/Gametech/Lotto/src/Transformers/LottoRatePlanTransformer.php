<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Contracts\LottoRatePlan;
use League\Fractal\TransformerAbstract;

class LottoRatePlanTransformer extends TransformerAbstract
{
    public function transform(LottoRatePlan $model): array
    {
        return [
            'id' => (int) $model->id,
            'name' => $model->name,
            'group_name' => optional($model->group)->name ?? '-',
            'is_enabled' => '<button type="button" class="btn ' . ($model->is_enabled ? 'btn-success' : 'btn-danger') . ' btn-xs"'
                . ' onclick="editdata(' . $model->id . ',' . ($model->is_enabled ? '0' : '1') . ',\'is_enabled\')">'
                . ($model->is_enabled ? '<i class="fa fa-check"></i> เปิด' : '<i class="fa fa-times"></i> ปิด')
                . '</button>',
            'action' => view('admin::module.lotto.rate_plans.datatables_actions', [
                'id' => $model->id,
            ])->render(),
        ];
    }
}

