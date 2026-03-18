<?php

namespace Gametech\Admin\Transformers;


use Gametech\Payment\Contracts\Bill;
use League\Fractal\TransformerAbstract;

class RpTopProTransformer extends TransformerAbstract
{


    public function transform(Bill $model)
    {

//        dd($model->toJson(JSON_PRETTY_PRINT));

        return [
            'code' => (int)$model->code,
            'member_name' => (is_null($model->member) ? '' : $model->member->name),
            'user_name' => (is_null($model->member) ? '' : $model->member->user_name),
            'pro_name' => (is_null($model->promotion) ? '' : $model->promotion->name_th),
            'amount' => $model->amount,
            'bonus' => $model->credit_bonus,
            'date_create' => $model->date_create->format('d/m/y H:i')
        ];
    }


}
