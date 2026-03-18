<?php

namespace Gametech\Admin\Transformers;

use Gametech\Payment\Contracts\Payment;
use League\Fractal\TransformerAbstract;

class RpSumPaymentTransformer extends TransformerAbstract
{


    public function transform(Payment $model)
    {


        return [
            'code' => (int)$model->code,
            'date_pay' => $model->date_pay->format('d/m/Y'),
            'name' => $model->name,
            'amount' => core()->currency($model->amount),
            'action' => view('admin::module.payment.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
