<?php

namespace Gametech\Admin\Transformers;


use Gametech\Member\Contracts\Member;
use Illuminate\Support\Facades\Cache;
use League\Fractal\TransformerAbstract;

class RpMemberOnlineTransformer extends TransformerAbstract
{


    public function transform(Member $model)
    {
//        $last = $model;
//        dd($last->toJson(JSON_PRETTY_PRINT));
//            dd($model->last_payment);
        return [
            'code' => (int)$model->code,
            'name' => $model->name,
            'user_name' => $model->user_name,
            'date_regis' => $model->date_regis->format('d/m/y'),
            'last_login' => $model->lastlogin->format('d/m/y H:i') . '  ( ' . $model->lastlogin->diffForHumans() . ' ) ',
            'last_status' => (Cache::has('is_online' . $model->code) ? core()->textcolor('Online') : core()->textcolor('Offline', 'text-secondary')),
            'refill_cnt' => $model->bank_payments_count,
            'refill_total' => ($model->bank_payments_count > 0 ? $model->bank_payments_value_sum : 0),
            'refill_last' => ($model->bank_payments_count > 0 ? $model->last_payment->date_topup->format('d/m/y H:i') . '  ( ' . $model->last_payment->date_topup->diffForHumans() . ' ) ' : ''),
        ];
    }


}
