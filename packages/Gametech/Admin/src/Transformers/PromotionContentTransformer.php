<?php

namespace Gametech\Admin\Transformers;


use Gametech\Promotion\Contracts\PromotionContent;
use League\Fractal\TransformerAbstract;

class PromotionContentTransformer extends TransformerAbstract
{


    public function transform(PromotionContent $model)
    {


        return [
            'code' => (int)$model->code,
            'name' => $model->name_th,
            'sort' => $model->sort,
            'enable' => core()->checkDisplay($model->enable),
            'pic' => core()->showImg($model->filepic, 'procontent_img', '50px', '50px'),
            'action' => view('admin::module.pro_content.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
