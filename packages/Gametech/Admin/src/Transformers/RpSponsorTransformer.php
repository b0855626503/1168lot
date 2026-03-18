<?php

namespace Gametech\Admin\Transformers;


use Gametech\Payment\Contracts\PaymentPromotion;
use League\Fractal\TransformerAbstract;

class RpSponsorTransformer extends TransformerAbstract
{


    public function transform(PaymentPromotion $model)
    {


        return [
            'code' => (int)$model->code,
            'date_create' => $model->date_create->format('d/m/Y H:i'),
            'up_name' => (is_null($model->member) ? '' : $model->member->name),
            'up_id' => (is_null($model->member) ? '' : $model->member->user_name),
            'down_name' => (is_null($model->down) ? '' : $model->down->name),
            'down_id' => (is_null($model->down) ? '' : $model->down->user_name),
            'amount' => "<span class='text-info'> " . core()->currency($model->amount) . " </span>",
            'bonus' => "<span class='text-danger'> " . core()->currency($model->credit_bonus) . " </span>",
        ];
    }


}
