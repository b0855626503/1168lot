<?php

namespace Gametech\Admin\Transformers;


use Gametech\Payment\Models\WithdrawNew;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class WithdrawNewTransformer extends TransformerAbstract
{


    public function transform(WithdrawNew $model)
    {

//        dd($model->toJson(JSON_PRETTY_PRINT));

        $status = ['0' => 'โอนไม่สำเร็จ', '1' => 'โอนสำเร็จ', '2' => 'ไม่อนุมัติ'];


//        dd($model->payment_last);

        return [
            'code' => (int)$model->code,
            'to_name' => $model->to_name,
            'to_account' => (!is_null($model->bank) ? core()->displayBank($model->bank->shortcode, $model->bank->filepic) : ''). ' [ '.$model->to_account.' ]',
            'date_bank' => $model->date_bank->format('d/m/Y'),
            'time_bank' => $model->time_bank,
            'amount' => '<span style="color:red">' . $model->amount . '</span>',
            'account_code' => (!is_null($model->frombank) ? core()->displayBank($model->frombank->shortcode, $model->frombank->filepic) : '').' [ '.$model->from_account.' ]',
            'status' => $status[$model->status],
//            'emp' => ($model->emp_approve == 0 ? '' : (is_null($model->admin) ? '' : $model->admin->user_name)),
            'ref' => $model->ref,
            'emp' => $model->emp_name,
            'ip' => $model->ip,
            'remark' => $model->remark
        ];
    }


}
