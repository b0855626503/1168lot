<?php

namespace Gametech\Admin\Transformers;



use Gametech\Game\Contracts\GameUser;
use Gametech\Game\Contracts\GameUserFree;
use League\Fractal\TransformerAbstract;

class GameUserFreeTransformer extends TransformerAbstract
{


    public function transform(GameUserFree $model)
    {


        return [
            'code' => (int)$model->code,
            'user_name' => $model->user_name,
            'turnpro' => $model->turnpro,
            'amount_balance' => $model->amount_balance,
            'withdraw_limit_rate' => $model->withdraw_limit_rate,
            'withdraw_limit_amount' => $model->withdraw_limit_amount,
            'action' => view('admin::module.game_user_free.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
