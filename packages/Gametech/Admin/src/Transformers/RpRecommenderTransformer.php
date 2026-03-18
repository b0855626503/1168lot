<?php

namespace Gametech\Admin\Transformers;


use Gametech\Member\Contracts\Member;
use Gametech\Payment\Contracts\PaymentPromotion;
use League\Fractal\TransformerAbstract;

class RpRecommenderTransformer extends TransformerAbstract
{


    public function transform(Member $model)
    {
//        dd($model);

        return [
            'code' => (int)$model->code,
            'date_regis' => $model->date_create->format('d/m/Y H:i'),
//            'name' => (is_null($model->member) ? '' : $model->member->name),
            'name' => $model->name,
            'user_name' => $model->user_name,
            'amount' => $model->payment_value_sum,
            'payout' => $model->payout_amount_sum,
//            'amount' => "<span class='text-info'> " . core()->currency($model->payment_value_sum) . " </span>",
//            'bonus' => "<span class='text-danger'> " . core()->currency($model->credit_bonus) . " </span>",
        ];
    }


}
