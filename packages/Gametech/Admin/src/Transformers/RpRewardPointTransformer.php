<?php

namespace Gametech\Admin\Transformers;

use Gametech\Member\Contracts\MemberRewardLog;
use League\Fractal\TransformerAbstract;

class RpRewardPointTransformer extends TransformerAbstract
{


    public function transform(MemberRewardLog $model)
    {


        return [
            'code' => (int)$model->code,
            'member_name' => (is_null($model->member) ? '' : $model->member->name),
            'user_name' => (is_null($model->member) ? '' : $model->member->user_name),
            'reward_name' => $model->reward->name,
            'point' => number_format($model->point),
            'remark' => $model->remark,
            'approve' => ($model->approve == 0 ? 'รออนุมัติ' : 'อนุมัติแล้ว'),
            'point_before' => "<span class='text-info'> " . number_format($model->point_before) . " </span>",
            'point_balance' => "<span class='text-danger'> " . number_format($model->point_balance) . " </span>",
            'emp_code' => ($model->emp_code == 0 ? '' : $model->emp->user_name),
            'date_create' => $model->date_create->format('d/m/y H:i'),
            'date_approve' => (is_null($model->date_approve) ? '' : $model->date_approve->format('d/m/y H:i')),
            'ip_admin' => $model->ip_admin
        ];
    }


}
