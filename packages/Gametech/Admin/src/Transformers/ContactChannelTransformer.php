<?php

namespace Gametech\Admin\Transformers;

use Gametech\Core\Contracts\ContactChannel;
use Gametech\Core\Models\ContactChannelProxy;
use League\Fractal\TransformerAbstract;

class ContactChannelTransformer extends TransformerAbstract
{
    public function transform(ContactChannel $model)
    {

        return [
            'code' => (int) $model->code,
            'type' => $model->type,
            'label' => $model->label,
            'link' => $model->link,
            'sort' => $model->sort,
            'enable' => '<button type="button" class="btn '.($model->enable == 'Y' ? 'btn-success' : 'btn-danger').' btn-xs icon-only" onclick="editdata('.$model->code.','."'".core()->flip($model->enable)."'".','."'enable'".')">'.($model->enable == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>').'</button>',
            'action' => view('admin::module.slide.datatables_actions', ['code' => $model->code])->render(),
        ];
    }
}
