<?php

namespace Gametech\Admin\Transformers;


use Gametech\API\Contracts\GameList;
use Gametech\Game\Contracts\GameType;
use League\Fractal\TransformerAbstract;

class GameListTransformer extends TransformerAbstract
{


    public function transform(GameList $model)
    {


        return [
            'product' => $model->product,
            'name' => $model->name,
            'enable' => '<button type="button" class="btn ' . ($model->enable == true ? 'btn-success' : 'btn-danger') . ' btn-xs icon-only" onclick="editdata(' . "'" . $model->id . "'" . "," . "'" . core()->flip2($model->enable) . "'" . "," . "'enable'" . ')">' . ($model->enable == true ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>',
//             'action' => view('admin::module.game_type.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
