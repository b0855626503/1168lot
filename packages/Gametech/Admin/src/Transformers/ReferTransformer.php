<?php

namespace Gametech\Admin\Transformers;


use Gametech\Core\Contracts\Refer;
use League\Fractal\TransformerAbstract;

class ReferTransformer extends TransformerAbstract
{


    public function transform(Refer $model)
    {


        return [
            'code' => (int)$model->code,
            'name' => $model->name,
            'enable' => '<button type="button" class="btn ' . ($model->enable == 'Y' ? 'btn-success' : 'btn-danger') . ' btn-xs icon-only" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->enable) . "'" . "," . "'enable'" . ')">' . ($model->enable == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>',
            'action' => view('admin::module.refer.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
