<?php

namespace Gametech\Admin\Transformers;

use Gametech\Core\Contracts\DailyStat;
use League\Fractal\TransformerAbstract;

class RpSumStatTransformer extends TransformerAbstract
{


    public function transform(DailyStat $model)
    {


        $total = ($model->deposit_sum - $model->withdraw_sum);

        return [
            'date' => $model->date->format('d/m/Y'),
            'member_all' => core()->currency($model->member_all, 0),
            'member_new' => '<a href="javascript:void(0)" onclick="ShowModel(' . $model->code . ',' . "'" . 'new' . "'" . ')">' . core()->currency($model->member_new, 0) . '</a>',
            'member_new_refill' => '<a href="javascript:void(0)" onclick="ShowModel(' . $model->code . ',' . "'" . 'newrefill' . "'" . ')">' . core()->currency($model->member_new_refill, 0) . '</a>',
            'member_all_refill' => core()->currency($model->member_all_refill, 0),
            'deposit_count' => core()->currency($model->deposit_count, 0),
            'deposit_sum' => core()->currency($model->deposit_sum),
            'withdraw_count' => core()->currency($model->withdraw_count, 0),
            'withdraw_sum' => core()->currency($model->withdraw_sum),
            'bonus_sum' => core()->currency($model->bonus_sum),
            'setwallet_d_sum' => core()->currency($model->setwallet_d_sum),
            'setwallet_w_sum' => core()->currency($model->setwallet_w_sum),
            'total' => core()->currency($total),
            'updated_at' => $model->updated_at->format('d/m/Y H:i:s'),

        ];
    }


}
