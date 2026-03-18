<?php

namespace Gametech\Admin\Transformers;

use Gametech\Game\Contracts\GameUser;
use League\Fractal\TransformerAbstract;

class RpBillTurnTransformer extends TransformerAbstract
{


    public function transform(GameUser $model)
    {

//        dd($model->toJson(JSON_PRETTY_PRINT));

        return [
            'code' => (int)$model->code,
            'member_name' => (is_null($model->membernew) ? '' : $model->membernew->name),
            'user_name' => (is_null($model->membernew) ? '' : $model->membernew->user_name),
            'game' => $model->game->name,
            'pro_name' => ($model->pro_code == 0 ? '' : $model->promotion->name_th),
            'amount' => core()->textcolor($model->amount, 'text-primary'),
            'bonus' => $model->bonus,
            'total' => ($model->amount + $model->bonus),
            'turn' => core()->textcolor($model->amount_balance, 'text-danger'),
            'min' => core()->textcolor($model->withdraw_limit_amount, 'text-danger'),
            'limit' => core()->textcolor($model->withdraw_limit, 'text-danger'),

            'date_create' => (is_null($model->billcode) ? '' : $model->billcode->date_create->format('d/m/y H:i')),
        ];
    }


}
