<?php

namespace Gametech\Admin\Transformers;


use Gametech\Promotion\Contracts\Promotion;
use League\Fractal\TransformerAbstract;

class PromotionTransformer extends TransformerAbstract
{


    public function transform(Promotion $model)
    {

        $type = [
            '' => '-',
            'PRICE' => 'จ่ายเป็น บาท',
            'PERCENT' => 'จ่ายเป็น %',
            'TIME' => 'ช่วงเวลา จ่ายเป็น บาท',
            'TIMEPC' => 'ช่วงเวลา จ่ายเป็น %',
            'AMOUNT' => 'ช่วงราคาตรงกัน จ่ายเป็น บาท',
            'AMOUNTPC' => 'ช่วงราคาตรงกัน จ่ายเป็น %',
            'BETWEEN' => 'ช่วงระหว่างราคา จ่ายเป็น บาท',
            'BETWEENPC' => 'ช่วงระหว่างราคา จ่ายเป็น %'
        ];

        return [
            'code' => (int)$model->code,

            'name' => $model->name_th,
            'type' => $type[$model->length_type],
            'id' => $model->id,
            'sort' => $model->sort,
            'auto' => core()->checkDisplay($model->use_auto),
            'wallet' => core()->checkDisplay($model->use_wallet),
            'active' => core()->checkDisplay($model->active),
            'enable' => core()->checkDisplay($model->enable),
            'pic' => core()->showImg($model->filepic, 'promotion_img', '50px', '50px'),
            'action' => view('admin::module.promotion.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
