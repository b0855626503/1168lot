<?php

namespace Gametech\Admin\Transformers;

use Gametech\Payment\Contracts\BankPayment;
use League\Fractal\TransformerAbstract;

class RpSmPaymentTransformer extends TransformerAbstract
{

    protected $no;

    public function __construct($no = 0)
    {
        $this->no = $no;

    }

    public function transform(BankPayment $model)
    {


        return [
            'code' => (int)$model->code,
            'no' => ++$this->no,
            'date_approve' => (is_null($model->date_approve) ? '' : $model->date_approve->format('d/m/y H:i:s')),
            'amount' => core()->textcolor(core()->currency($model->value), 'text-success'),
            'username' => (is_null($model->member) ? '' : $model->member->user_name),
            'msg' => $model->msg,
            'pro_amount' => $model->pro_amount,
        ];
    }


}
