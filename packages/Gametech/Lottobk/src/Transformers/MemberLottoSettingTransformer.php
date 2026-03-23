<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Contracts\MemberLottoSetting;
use League\Fractal\TransformerAbstract;

class MemberLottoSettingTransformer extends TransformerAbstract
{
    public function transform(MemberLottoSetting $model): array
    {
        return [
            'id'               => (int) $model->id,
            'member_code'      => $model->member->code ?? '-',
            'member_name'      => $model->member->name ?? '-',
            'rate_plan_name'   => $model->ratePlan->name ?? '-',
            'action'           => view('admin::module.lotto.member_rate_plans.datatables_actions', [
                'id' => $model->id,
            ])->render(),
        ];
    }
}

