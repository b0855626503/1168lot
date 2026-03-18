<?php

namespace Gametech\Admin\Transformers;

use Gametech\Payment\Contracts\Bill;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class RpBillTransformer extends TransformerAbstract
{

    public function transform(Bill $model)
    {

//        dd($model->toJson(JSON_PRETTY_PRINT));

        if ($model->transfer_type == 1) {
            $amount = $model->credit;
            $amount_request = $amount;
            $amount_limit = 0;
        } else {
            $amount = $model->amount;
            if ($model->amount_request > 0) {
                $amount_request = $model->amount_request;
            } else {
                $amount_request = $model->amount;
            }
            if ($model->amount_limit > 0) {
                $amount_limit = $model->amount_limit;
            } else {
                $amount_limit = 0;
            }
        }

        return [
            'code' => (int)$model->code,
            'id' => '#BL' . Str::of($model->code)->padLeft(8, 0),
            'member_name' => (is_null($model->member) ? '' : $model->member->name),
            'user_name' => (is_null($model->member) ? '' : $model->member->user_name),
            'transfer_type' => ($model->transfer_type == 1 ? 'Wallet To ' . $model->game->name : "<span class='text-danger'>" . $model->game->name . " To Wallet</span>"),
            'game_user' => (is_null($model->game_user) ? '' : $model->game_user->user_name),
//            'game_user' => $model->game_user->user_name,
            'enable' => ($model->enable == 'Y' ? "<span class='text-success'>โยกสำเร็จ</span>" : "<span class='text-danger'>โยกไม่สำเร็จ</span>"),
            'amount_request' => "<span class='text-primary'>" . core()->currency($amount_request) . "</span>",
            'amount_limit' => "<span class='text-orange'>" . core()->currency($amount_limit) . "</span>",
            'credit' => "<span class='text-info'>" . core()->currency($amount) . "</span>",
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
