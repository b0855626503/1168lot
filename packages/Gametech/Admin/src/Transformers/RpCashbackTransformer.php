<?php

namespace Gametech\Admin\Transformers;


use Gametech\Member\Contracts\Member;
use League\Fractal\TransformerAbstract;

class RpCashbackTransformer extends TransformerAbstract
{
    protected $bonus;

    public function __construct($bonus)
    {
        $this->bonus = $bonus;
    }

    public function transform(Member $model)
    {

//        $promotion = DB::table('promotions')->where('id','pro_cashback')->first();
        $bonus = $this->bonus;
//        dd($bonus);


//        dd($model->toJson(JSON_PRETTY_PRINT));

        $balance = ($model->deposit_amount - $model->withdraw_amount - $model->balance);
        if ($balance < 0 || $model->bonus_amount > 0) {
            $balance = 0;
        }
        $cashback = ($balance * $bonus) / 100;

        return [
            'code' => (int)$model->code,
            'member_name' => $model->member_name,
            'user_name' => $model->user_name,

            'deposit_amount' => core()->textcolor(core()->currency($model->deposit_amount), 'text-primary'),
            'bonus_amount' => core()->textcolor(core()->currency($model->bonus_amount), 'text-default'),
            'withdraw_amount' => core()->textcolor(core()->currency($model->withdraw_amount), 'text-danger'),
            'balance_amount' => core()->textcolor(core()->currency($balance), 'text-info'),
            'cashback' => core()->textcolor(core()->currency($cashback), 'text-success'),
            'balance' => $model->balance,
            'date_approve' => (is_null($model->date_approve) ? '' : $model->date_approve->format('d/m/y H:i')),
            'status' => is_null($model->member_cashback) ? ($balance == 0 ? '-' : '<span class="badge badge-warning">Fail</span>') : '<span class="badge badge-danger">Success</span>',
            'action' => view('admin::module.rp_cashback.datatables_actions', ['code' => $model->code, 'status' => (is_null($model->member_cashback) ? 0 : 1), 'balance' => $balance, 'bonus' => $bonus, 'member_code' => $model->member_code, 'upline_code' => $model->upline_code, 'date_cashback' => $model->date_cashback])->render()

        ];
    }


}
