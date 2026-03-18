<?php

namespace Gametech\Admin\Transformers;


use Gametech\Payment\Contracts\PaymentWaiting;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class ConfirmwalletTransformer extends TransformerAbstract
{


    public function transform(PaymentWaiting $model)
    {


        return [
            'code' => (int)$model->code,
            'name' => $model->member->name,
            'user_name' => $model->member->user_name,
            'game' => $model->game->name,
            'pro' => ($model->pro_code == 0 ? '' : $model->promotion->name_th),
            'amount' => '<span style="color:red">' . core()->currency($model->amount) . '</span>',
            'balance' => '<span style="color:blue">' . core()->currency($model->member->balance) . '</span>',
            'ip' => '<span class="text-long" data-toggle="tooltip" title="' . $model->ip . '">' . Str::limit($model->ip, 10) . '</span>',
            'date' => $model->date_create->format('d/m/y H:i:s'),
            'confirm' => view('admin::module.confirm_wallet.datatables_confirm', ['code' => $model->code])->render(),
            'cancel' => view('admin::module.confirm_wallet.datatables_cancel', ['code' => $model->code])->render(),
            'delete' => view('admin::module.confirm_wallet.datatables_delete', ['code' => $model->code])->render()
        ];
    }


}
