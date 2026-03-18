<?php

namespace Gametech\Admin\Transformers;


use Gametech\Game\Contracts\Game;
use Gametech\Game\Contracts\FreeGame;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Fractal\TransformerAbstract;

class FreeGameTransformer extends TransformerAbstract
{


    public function transform(FreeGame $model)
    {


        return [
            'code' => (int)$model->code,
            'member_user' => $model->member_user,
            'expired_date' => $model->expired_date,
            'free_game_name' => $model->free_game_name,
            'bet_amount' => $model->bet_amount,
            'game_count' => $model->game_count,
            'product_id' => $model->product_id,
            'game_name' => $model->game_name,
            'emp_code' => $model->emp_code,
            'emp_user' => $model->emp_user,
            'date_create' => $model->date_create->format('Y-m-d H:i:s'),
            'status' => ($model->status='Y'?'สำเร็จ':'ไม่สำเร็จ'),
            'ip' => $model->ip,
            'action' => view('admin::module.freegame.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
