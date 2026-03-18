<?php

namespace Gametech\Admin\Transformers;


use Gametech\Core\Contracts\BatchUser;
use Illuminate\Support\Facades\DB;
use League\Fractal\TransformerAbstract;

class BatchUserTransformer extends TransformerAbstract
{


    public function transform(BatchUser $model)
    {

//        $link = '';
//        if (!empty($model['link_ios'])) {
//            $link .= '<a href="' . $model['link_ios'] . '" target="_blank" class="color-sky"><i class="fa fa-apple"></i>IOS</a>&nbsp;&nbsp;';
//        }
//        if (!empty($model['link_android'])) {
//            $link .= '<a href="' . $model['link_android'] . '" target="_blank" class="color-sky"><i class="fa fa-android"></i>Android</a>&nbsp;&nbsp;';
//        }
//        if (!empty($model['link_web'])) {
//            $link .= '<a href="' . $model['link_web'] . '" target="_blank" class="color-sky"><i class="fa fa-link"></i>Web</a> ';
//        }

        $model->game_id = ($model->game_id == 'jokerNew' ? 'joker' : $model->game_id);
        $remain = DB::table("users_" . $model->game_id)->where('enable', 'Y')->where('use_account', 'N')->where('batch_code', $model->code)->count();

        return [
            'code' => (int)$model->code,
            'game' => (isset($model->game) ? $model->game->name : ''),
            'type' => ($model->freecredit == 'Y' ? core()->textcolor('<i class="fa fa-check"></i>') : core()->textcolor('<i class="fa fa-times"></i>', 'text-danger')),
            'prefix' => $model->prefix,
            'start' => number_format($model->batch_start),
            'stop' => number_format($model->batch_stop),
            'remain' => core()->textcolor(number_format($remain), 'text-danger')
        ];
    }


}
