<?php

namespace Gametech\Admin\Transformers;


use Gametech\Game\Contracts\GameType;
use League\Fractal\TransformerAbstract;

class GameTypeTransformer extends TransformerAbstract
{


    public function transform(GameType $model)
    {


        return [
            'code' => (int)$model->code,
            'id' => $model->id,
            'status_open' => '<button type="button" class="btn ' . ($model->status_open == 'Y' ? 'btn-success' : 'btn-danger') . ' btn-xs icon-only" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->status_open) . "'" . "," . "'status_open'" . ')">' . ($model->status_open == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>',
             'action' => view('admin::module.game_type.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
