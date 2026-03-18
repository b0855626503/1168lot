<?php

namespace Gametech\Admin\Transformers;

use Gametech\Game\Contracts\Game;
use League\Fractal\TransformerAbstract;

class RpSumGameTransformer extends TransformerAbstract
{


    public function transform(Game $model)
    {

//        dd($model->toJson(JSON_PRETTY_PRINT));

        $in = (is_null($model->in) ? 0 : $model->in);
        $out = (is_null($model->out) ? 0 : $model->out);
        $bonus = (is_null($model->bonus) ? 0 : $model->bonus);
        $member_in = (is_null($model->member_in) ? 0 : $model->member_in);
        $member_out = (is_null($model->member_out) ? 0 : $model->member_out);

        $member_in_cnt = (is_null($model->member_in_cnt) ? 0 : $model->member_in_cnt);
        $member_out_cnt = (is_null($model->member_out_cnt) ? 0 : $model->member_out_cnt);

        $total = $in - $out;
//        if($total < 0){
//            $total = core()->textcolor(core()->currency($total),'text-danger');
//        }else{
//            $total = core()->textcolor(core()->currency($total));
//        }
        return [
            'name' => $model->name,
            'in' => core()->currency($in),
            'out' => core()->currency($out),
            'total' => core()->currency($total),
            'bonus' => core()->currency($bonus),
            'member_in' => core()->currency($member_in),
            'member_in_cnt' => core()->currency($member_in_cnt),
            'member_out' => core()->currency($member_out),
            'member_out_cnt' => core()->currency($member_out_cnt),
        ];
    }


}
