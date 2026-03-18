<?php

namespace Gametech\Admin\Transformers;


use Gametech\Member\Contracts\Member;
use Gametech\Payment\Contracts\Bill;
use League\Fractal\TransformerAbstract;

class RpMemberProTransformer extends TransformerAbstract
{


    public function transform(Member $model)
    {

//        dd($model->toJson(JSON_PRETTY_PRINT));

        return [
            'code' => (int)$model->code,
            'firstname' => $model->firstname,
            'lastname' => $model->lastname,
            'user_name' => $model->user_name,
        ];
    }


}
