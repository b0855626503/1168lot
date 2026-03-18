<?php

namespace Gametech\Admin\Transformers;

use Gametech\Payment\Contracts\Payment;
use League\Fractal\TransformerAbstract;

class PaymentTransformer extends TransformerAbstract
{


    public function transform(Payment $model)
    {


        return [
            'code' => (int)$model->code,
            'date_pay' => $model->date_pay->format('d/m/Y'),
            'date_create' => $model->date_create->format('d/m/Y H:i'),
            'name' => $model->name,
            'ip' => $model->ip,
            'user_create' => $model->user_create,
            'amount' => core()->currency($model->amount),
            'action' => view('admin::module.payment.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
