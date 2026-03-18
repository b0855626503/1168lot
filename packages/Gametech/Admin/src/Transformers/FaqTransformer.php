<?php

namespace Gametech\Admin\Transformers;


use Gametech\Core\Contracts\Faq;
use League\Fractal\TransformerAbstract;

class FaqTransformer extends TransformerAbstract
{


    public function transform(Faq $model)
    {


        return [
            'code' => (int)$model->code,
            'name' => $model->question,
            'sort' => $model->sort,
            'enable' => '<button type="button" class="btn ' . ($model->enable == 'Y' ? 'btn-success' : 'btn-danger') . ' btn-xs icon-only" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->enable) . "'" . "," . "'enable'" . ')">' . ($model->enable == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>',
            'action' => view('admin::module.faq.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
