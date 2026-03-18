<?php

namespace Gametech\Admin\Transformers;

use Gametech\Payment\Contracts\WithdrawSeamless;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class RpWithdrawSeamlessTransformer extends TransformerAbstract
{


    public function transform(WithdrawSeamless $model)
    {

        $status = ['0' => 'รอดำเนินการ', '1' => 'อนุมัติ', '2' => 'ไม่อนุมัติ'];

        $status_wd = ['W' => 'ไม่ได้เลือก Payment Gateway','A' => 'ระหว่างดำเนินการ' ,'C' => 'โอนให้แล้ว','R' => 'คืนยอดให้ลุกค้าแล้ว'];

        $remark = '<span class="text-long" data-toggle="tooltip" title="' . $model->remark_admin . '">' . Str::limit($model->remark_admin, 50) . '</span>';

        $model->status_withdraw = ($model->status_withdraw ?? 'W');

        return [
            'code' => (int)$model->code,
            'bank' => core()->displayBank($model->bank->shortcode, $model->bank->filepic),
            'account_code' => ($model->bank_tran ?  core()->displayBank($model->bank_tran->bank->shortcode, $model->bank_tran->bank->filepic).' '.$model->bank_tran->acc_no : ''),
            'date' => $model->date_record->format('d/m/y').' '.$model->timedept,
            'time' => $model->timedept,
            'txid' => $model->txid,
            'balance' => core()->textcolor(core()->currency($model->balance), 'text-danger'),
            'amount' => core()->textcolor(core()->currency($model->amount), 'text-danger'),
            'member_name' => (is_null($model->member) ? '' : $model->member->name),
            'user_name' => (is_null($model->member) ? '' : $model->member->user_name),
            'game_user' => (is_null($model->user) ? '' : $model->user->user_name),
            'status' => $status[$model->status],
            'date_approve' => $model->date_bank->format('d/m/y').' '.$model->time_bank,
            'status_withdraw' => $status_wd[$model->status_withdraw],
            'remark' => 'Admin : ' . $model->remark_admin,
            'emp_name' => ($model->emp_approve === 0 ? '' : (is_null($model->admin) ? '' : $model->admin->name)),
            'date_create' => $model->date_create->format('d/m/y H:i:s'),
            'date_update' => $model->date_update->format('d/m/y H:i:s'),
            'ip' => 'User : ' . $model->ip . '<br>Admin : ' . $model->ip_admin,
        ];
    }


}
