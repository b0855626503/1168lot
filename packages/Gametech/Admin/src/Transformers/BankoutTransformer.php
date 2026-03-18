<?php

namespace Gametech\Admin\Transformers;

use Gametech\Payment\Contracts\BankPayment;
use League\Fractal\TransformerAbstract;

class BankoutTransformer extends TransformerAbstract
{

    public function transform(BankPayment $model): array
    {

        return [
            'code' => (int)$model->code,
            'bankcode' => (!is_null($model->bank_account) ? (!is_null($model->bank_account->bank) ? core()->displayBank($model->bank_account->bank->shortcode, $model->bank_account->bank->filepic) : '') : ''),
            'acc_no' => (!is_null($model->bank_account) ? $model->bank_account->acc_no : ''),
            'bank_time' => $model->bank_time->format('d/m/y H:i:s'),
            'channel' => $model->channel,
            'detail' => $model->detail,
            'value' => '<span style="color:red">' . core()->currency($model->value) . '</span>',
            'date' => $model->date_create->format('d/m/y H:i:s'),
            'cancel' => view('admin::module.bank_out.datatables_clear', ['code' => $model->code])->render(),
            'delete' => view('admin::module.bank_out.datatables_delete', ['code' => $model->code])->render()
        ];
    }


}
