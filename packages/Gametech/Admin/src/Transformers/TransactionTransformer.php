<?php

namespace Gametech\Admin\Transformers;

use Gametech\Payment\Contracts\BankPayment;
use League\Fractal\TransformerAbstract;

class TransactionTransformer extends TransformerAbstract
{

    protected $no;

    public function __construct($no = 0)
    {
        $this->no = $no;

    }
    public function transform(BankPayment $model): array
    {

        if($model->value >= 0){
            $value = '<span style="color:blue">' . core()->currency($model->value) . '</span>';
        } else {
            $value = '<span style="color:red">' . core()->currency($model->value) . '</span>';
        }

        return [
            'code' => ++$this->no,
            'bankcode' => (!is_null($model->bank_account) ? (!is_null($model->bank_account->bank) ? core()->displayBank($model->bank_account->bank->shortcode, $model->bank_account->bank->filepic) : '') : ''),
            'acc_no' => (!is_null($model->bank_account) ? $model->bank_account->acc_no : ''),
            'bank_time' => $model->bank_time->format('d/m/y H:i:s'),
            'channel' => $model->channel,
            'detail' => $model->detail,
            'value' => $value,
            'date' => $model->date_create->format('d/m/y H:i:s'),
            'cancel' => view('admin::module.bank_out.datatables_clear', ['code' => $model->code])->render(),
            'delete' => view('admin::module.bank_out.datatables_delete', ['code' => $model->code])->render()
        ];
    }


}
