<?php

namespace Gametech\Admin\Transformers;


use Gametech\Payment\Contracts\WithdrawSeamlessFree;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class WithdrawSeamlessFreeTransformer extends TransformerAbstract
{


    public function transform(WithdrawSeamlessFree $model)
    {

//        dd($model->toJson(JSON_PRETTY_PRINT));

        $status = ['0' => 'รอดำเนินการ', '1' => 'อนุมัติ', '2' => 'ไม่อนุมัติ'];


//        dd($model->payment_last);

        return [
            'code' => (int)$model->code,
            'acc_no' => (!is_null($model->bank) ? core()->displayBank($model->bank->shortcode . ' [' . (is_null($model->member) ? '' : $model->member->acc_no) . ']', $model->bank->filepic) : ''),
            'balance' => '<span style="color:blue">' . $model->balance . '</span>',
            'amount_balance' => '<span style="color:black">' . $model->amount_balance . '</span>',
            'amount_limit' => '<span style="color:black">' . $model->amount_limit . '</span>',
            'amount_limit_rate' => '<span style="color:black">' . $model->amount_limit_rate . '</span>',
            'amount' => '<span style="color:red">' . $model->amount . '</span>',
            'before' => '<span style="color:gray">' . $model->oldcredit . '</span>',
            'after' => '<span style="color:gray">' . $model->aftercredit . '</span>',
            'date' => $model->date_record->format('d/m/y'),
            'time' => $model->timedept,
            'username' => $model->member_user,
            'name' => (is_null($model->member) ? '' : $model->member->name),
            'ip' => '<span class="text-long" data-toggle="tooltip" title="' . $model->ip . '">' . Str::limit($model->ip, 10) . '</span>',
            'bonus' => (!is_null($model->bills->first()) ? (is_null($model->bills->first()->promotion) ? '' : $model->bills->first()->promotion['name_th']) . ' [' . $model->bills->first()->date_create->format('d/m/Y') . ']' : ''),
            'refill' => (!is_null($model->payment_last) ? $model->payment_last['bank'] : ''),
            'status' => $status[$model->status],
            'date_approve' => ($model->date_approve === '0000-00-00 00:00:00' || is_null($model->date_approve) ? '' : $model->date_approve->format('d/m/y H:i:s')),
            'emp_approve' => ($model->emp_approve == 0 ? '' : (is_null($model->admin) ? '' : $model->admin->user_name)),
            'waiting' => view('admin::module.withdraw_seamless_free.datatables_confirm', ['code' => $model->code, 'status' => $model->status , 'emp_code' => $model->emp_approve])->render(),
            'cancel' => view('admin::module.withdraw_seamless_free.datatables_cancel', ['code' => $model->code, 'status' => $model->status , 'emp_code' => $model->emp_approve])->render(),
            'delete' => view('admin::module.withdraw_seamless_free.datatables_delete', ['code' => $model->code, 'status' => $model->status , 'emp_code' => $model->emp_approve])->render()
        ];
    }


}
