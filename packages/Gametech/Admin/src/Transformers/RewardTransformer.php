<?php

namespace Gametech\Admin\Transformers;


use Gametech\Core\Contracts\Reward;
use Illuminate\Support\Facades\Storage;
use League\Fractal\TransformerAbstract;

class RewardTransformer extends TransformerAbstract
{


    public function transform(Reward $model)
    {

        return [
            'code' => (int)$model->code,
            'name' => $model->name,
            'qty' => $model->qty,
            'remain' => $model->qty,
            'points' => $model->points,
            'active' => '<button type="button" class="btn ' . ($model->active == 'Y' ? 'btn-success' : 'btn-danger') . ' btn-xs icon-only icon-only" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->active) . "'" . "," . "'active'" . ')">' . ($model->enable == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>',
            'filepic' => ($model->filepic ? '<img src="' . Storage::url('reward_img/' . $model->filepic) . '" class="rounded" style="width:50px;height:50px;">' : ''),
            'action' => view('admin::module.reward.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
