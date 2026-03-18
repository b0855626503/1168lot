<?php

namespace Gametech\Admin\Transformers;



use Gametech\Core\Contracts\Notice;
use League\Fractal\TransformerAbstract;

class NoticeTransformer extends TransformerAbstract
{


    public function transform(Notice $model)
    {


        return [
            'code' => (int)$model->code,
            'route' => $model->route,
            'message' => $model->message,
            'enable' => '<button type="button" class="btn ' . ($model->enable == 'Y' ? 'btn-success' : 'btn-danger') . ' btn-xs icon-only" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->enable) . "'" . "," . "'enable'" . ')">' . ($model->enable == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>',
            'action' => view('admin::module.notice.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
