<?php

namespace Gametech\Admin\Transformers;


use Gametech\API\Contracts\PGSoft;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Fractal\TransformerAbstract;

class PGSlotTransformer extends TransformerAbstract
{


    public function transform(PGSoft $model)
    {


        return [
            'id' => $model->_id,
            'game_id' => $model->game_id,
            'parent_bet_id' => $model->parent_bet_id,
            'bet_id' => $model->bet_id,
            'player_name' => $model->player_name,
            'transfer_amount' => $model->transfer_amount,
            'gameCode' => $model->gameCode,
            'method' => $model->method,
            'create_time' => core()->formatDate($model->create_time,'Y-m-d H:i:s'),
        ];
    }


}
