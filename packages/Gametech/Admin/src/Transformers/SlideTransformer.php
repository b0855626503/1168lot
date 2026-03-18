<?php

namespace Gametech\Admin\Transformers;


use Gametech\Core\Contracts\Slide;
use Illuminate\Support\Facades\Storage;
use League\Fractal\TransformerAbstract;

class SlideTransformer extends TransformerAbstract
{


    public function transform(Slide $model)
    {




        return [
            'code' => (int)$model->code,
            'sort' => $model->sort,
            'enable' => '<button type="button" class="btn ' . ($model->enable == 'Y' ? 'btn-success' : 'btn-danger') . ' btn-xs icon-only" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->enable) . "'" . "," . "'enable'" . ')">' . ($model->enable == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>',
            'filepic' => '<img src="' . Storage::url('slide_img/' . $model->filepic) . '" class="rounded" style="width:50px;height:50px;">',
            'action' => view('admin::module.slide.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
