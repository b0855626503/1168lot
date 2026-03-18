<?php

namespace Gametech\Admin\Transformers;


use Gametech\Member\Contracts\Member;
use League\Fractal\TransformerAbstract;


class RpFirstTimeTransformer extends TransformerAbstract
{



    public function transform(Member $model)
    {

//        dd($model);

        return [
            'code' => (int)$model->code,
            'name' => $model->name,
            'date_regis' => $model->date_create->format('d/m/Y H:i:s'),
            'user_name' => $model->user_name,
//            'payment' => $model->payment_first_value_sum,
            'payment' => ($model->payment_first ? $model->payment_first->value:''),
            'payment_date' => ($model->payment_first? $model->payment_first->bank_time->format('d/m/Y H:i:s'):'-'),
       ];
    }


}
