<?php

namespace Gametech\Admin\Transformers;

use Gametech\Core\Contracts\Refer;

use League\Fractal\TransformerAbstract;

class RpMemberRefTransformer extends TransformerAbstract
{


    public function transform(Refer $model)
    {

//        dd($model->toJson(JSON_PRETTY_PRINT));

        $total = (is_null($model->total) ? 0 : $model->total);

        return [
            'name' => $model->name,
            'total' => core()->currency($total,0),
            'more' => '<a href="javascript:void(0)" onclick="ShowModel(' . $model->code . ')">View</a>',

        ];
    }


}
