<?php

namespace Gametech\Admin\Transformers;

use Gametech\Payment\Contracts\BillFree;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class RpBillFreeTransformer extends TransformerAbstract
{


    public function transform(BillFree $model)
    {

//        dd($model->toJson(JSON_PRETTY_PRINT));

        return [
            'code' => (int)$model->code,
            'id' => '#BL' . Str::of($model->code)->padLeft(8, 0),
            'member_name' => (is_null($model->member) ? '' : $model->member->name),
            'user_name' => (is_null($model->member) ? '' : $model->member->user_name),
            'transfer_type' => ($model->transfer_type == 1 ? 'Wallet To ' . $model->game->name : "<span class='text-danger'>" . $model->game->name . " To Wallet</span>"),
            'game_user' => (is_null($model->game_user) ? '' : $model->game_user->user_name),
//            'game_user' => $model->game_user->user_name,
            'enable' => ($model->enable == 'Y' ? "<span class='text-success'>โยกสำเร็จ</span>" : "<span class='text-danger'>โยกไม่สำเร็จ</span>"),
            'credit' => "<span class='text-info'>" . core()->currency($model->credit) . "</span>",
            'pro_name' => (!is_null($model->promotion) ? $model->promotion->name_th : ''),
            'credit_bonus' => $model->credit_bonus,
            'credit_balance' => $model->credit_balance,
            'balance_before' => "<span class='text-success'>" . core()->currency($model->balance_before) . "</span>",
            'balance_after' => "<span class='text-danger'>" . core()->currency($model->balance_after) . "</span>",
            'credit_before' => "<span class='text-success'>" . core()->currency($model->credit_before) . "</span>",
            'credit_after' => "<span class='text-danger'>" . core()->currency($model->credit_after) . "</span>",
            'date_create' => $model->date_create->format('d/m/y H:i:s'),
            'ip' => $model->ip
        ];
    }


}
