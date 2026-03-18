<?php

namespace Gametech\Admin\Transformers;

use Gametech\Payment\Contracts\BonusSpin;
use League\Fractal\TransformerAbstract;

class RpSpinTransformer extends TransformerAbstract
{


    public function transform(BonusSpin $model)
    {
        $types = ['WALLET' => 'Wallet (balance)', 'CREDIT' => 'CREDIT (balance_free)', 'DIAMOND' => 'Diamond', 'REAL' => 'รางวัลที่จับต้องได้'];


        return [
            'code' => (int)$model->code,
            'member_name' => (is_null($model->member) ? '' : $model->member->name),
            'user_name' => (is_null($model->member) ? '' : $model->member->user_name),
            'bonus' => $model->bonus_name,
            'reward_type' => $types[$model->reward_type],
            'amount' => $model->amount,
            'diamond' => number_format($model->diamond_balance),
            'date_create' => $model->date_create->format('d/m/y H:i'),
            'ip' => $model->ip
        ];
    }


}
