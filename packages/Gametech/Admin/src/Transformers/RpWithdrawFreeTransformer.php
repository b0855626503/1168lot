<?php

namespace Gametech\Admin\Transformers;


use Gametech\Payment\Contracts\WithdrawFree;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class RpWithdrawFreeTransformer extends TransformerAbstract
{


    public function transform(WithdrawFree $model)
    {

        $status = ['0' => 'รอดำเนินการ', '1' => 'อนุมัติ', '2' => 'ไม่อนุมัติ'];

        $status_wd = ['W' => '-','A' => 'ถอนออโต้' ,'C' => 'ถอนสำเร็จ'];

        $remark = '<span class="text-long" data-toggle="tooltip" title="' . $model->remark_admin . '">' . Str::limit($model->remark_admin, 50) . '</span>';

        $model->status_withdraw = ($model->status_withdraw ?? 'W');

        return [
            'code' => (int)$model->code,
            'bank' => (is_null($model->bank) ? '' : core()->displayBank($model->bank->shortcode, $model->bank->filepic)),
            'date' => $model->date_record->format('d/m/y'),
            'time' => $model->timedept,
            'amount' => core()->textcolor(core()->currency($model->amount), 'text-danger'),
            'member_name' => (is_null($model->member) ? '' : $model->member->name),
            'user_name' => (is_null($model->member) ? '' : $model->member->user_name),

            'status' => $status[$model->status],
            'date_approve' => ($model->date_approve === '0000-00-00 00:00:00' || is_null($model->date_approve) ? '' : core()->formatDate($model->date_approve, 'd/m/y H:i:s')),
            'status_withdraw' => $status_wd[$model->status_withdraw],
            'remark' => 'Admin : ' . $model->remark_admin,
            'emp_name' => ($model->emp_approve === 0 ? '' : (is_null($model->admin) ? '' : $model->admin->name)),
            'date_create' => $model->date_create->format('d/m/y H:i'),
            'ip' => $model->ip
        ];
    }


}
