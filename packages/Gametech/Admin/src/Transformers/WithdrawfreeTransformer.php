<?php

namespace Gametech\Admin\Transformers;


use Gametech\Payment\Contracts\WithdrawFree;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class WithdrawfreeTransformer extends TransformerAbstract
{


    public function transform(WithdrawFree $model)
    {


        return [
            'code' => (int)$model->code,
            'acc_no' => (!is_null($model->bank) ? core()->displayBank($model->bank->shortcode . ' [' . (is_null($model->member) ? '' : $model->member->acc_no) . ']', $model->bank->filepic) : ''),
            'balance' => '<span style="color:blue">' . $model->balance . '</span>',
            'amount' => '<span style="color:red">' . $model->amount . '</span>',
            'date' => $model->date_record->format('d/m/y'),
            'time' => $model->timedept,
            'username' => $model->member_user,
            'name' => (is_null($model->member) ? '' : $model->member->name),
            'refill' => (!is_null($model->payment_last) ? $model->payment_last['bank'] : ''),
            'ip' => '<span class="text-long" data-toggle="tooltip" title="' . $model->ip . '">' . Str::limit($model->ip, 10) . '</span>',
            'check' => '<button type="button" class="btn ' . ($model->check_status == 'Y' ? 'btn-success' : 'btn-danger') . ' btn-xs icon-only" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->check_status) . "'" . "," . "'check_status'" . ')">' . ($model->check_status == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>',
            'waiting' => view('admin::module.withdraw_free.datatables_confirm', ['code' => $model->code, 'status' => $model->status, 'emp_code' => $model->emp_approve])->render(),
            'cancel' => view('admin::module.withdraw_free.datatables_cancel', ['code' => $model->code, 'status' => $model->status])->render(),
            'delete' => view('admin::module.withdraw_free.datatables_delete', ['code' => $model->code, 'status' => $model->status])->render()
        ];
    }


}
