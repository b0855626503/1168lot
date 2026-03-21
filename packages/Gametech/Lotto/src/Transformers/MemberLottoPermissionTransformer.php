<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Contracts\MemberLottoMarketPolicy;
use League\Fractal\TransformerAbstract;

class MemberLottoPermissionTransformer extends TransformerAbstract
{
    public function transform(MemberLottoMarketPolicy $model): array
    {
        $memberName = trim((string) (($model->member->user_name ?? '') ?: ($model->member->name ?? '')));
        $nextStatus = $model->is_allowed ? 0 : 1;
        $toggleClick = "editdata(" . (int) $model->id . "," . $nextStatus . ",'is_allowed')";

        return [
            'id' => (int) $model->id,
            'member_code' => (int) $model->member_id,
            'member_name' => $memberName !== '' ? $memberName : '-',
            'group_name' => $model->group->name ?? '-',
            'market_name' => $model->market->name ?? '-',
            'is_allowed' => '<button type="button" class="btn ' . ($model->is_allowed ? 'btn-success' : 'btn-danger') . ' btn-xs"'
                . ' onclick="' . $toggleClick . '">'
                . ($model->is_allowed ? '<i class="fa fa-check"></i> อนุญาต' : '<i class="fa fa-times"></i> ปิด')
                . '</button>',
            'source' => '<span class="badge badge-info">' . e((string) $model->source) . '</span>',
            'policy_version' => (int) $model->policy_version,
            'action' => view('admin::module.lotto.member_permissions.datatables_actions', [
                'id' => $model->id,
            ])->render(),
        ];
    }
}

