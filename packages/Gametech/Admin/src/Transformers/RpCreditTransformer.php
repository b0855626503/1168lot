<?php

namespace Gametech\Admin\Transformers;


use Gametech\Member\Contracts\MemberFreeCredit;
use League\Fractal\TransformerAbstract;

class RpCreditTransformer extends TransformerAbstract
{


    public function transform(MemberFreeCredit $model)
    {


        return [
            'code' => (int)$model->code,
            'member_name' => (is_null($model->member) ? '' : $model->member->name),
            'user_name' => (is_null($model->member) ? '' : $model->member->user_name),
            'credit_type' => ($model->credit_type == 'D' ? "<span class='badge badge-success'> เพิ่ม Credit </span>" : "<span class='badge badge-danger'> ลด Credit </span>"),
            'credit_amount' => "<span class='text-primary'> " . core()->currency($model->credit_amount) . " </span>",
            'credit_before' => "<span class='text-info'> " . core()->currency($model->credit_before) . " </span>",
            'credit_balance' => "<span class='text-danger'> " . core()->currency($model->credit_balance) . " </span>",
            'remark' => $model->remark,
            'emp_name' => ($model->emp_code == 0 ? $model->user_create : (is_null($model->admin) ? $model->user_create : $model->admin->user_name)),
            'date_create' => $model->date_create->format('d/m/y H:i:s'),
            'ip' => $model->ip
        ];
    }


}
