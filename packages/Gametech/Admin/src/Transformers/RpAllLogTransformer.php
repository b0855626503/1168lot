<?php

namespace Gametech\Admin\Transformers;


use Gametech\Member\Contracts\MemberCreditLog;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class RpAllLogTransformer extends TransformerAbstract
{

    protected $no;

    public function __construct($no = 1)
    {
        $this->no = $no;

    }

    public function transform(MemberCreditLog $model)
    {

//dd($model->toJson(JSON_PRETTY_PRINT));
        $game = (is_null($model->game_user) ? (is_null($model->game) ? '' : $model->game->name) : $model->game_user->game->name);
        $bank = (is_null($model->bank) ? '' : $model->bank->shortcode);
        $pro = (is_null($model->promotion) ? '' : $model->promotion->name_th);
        $type = core()->TypeDisplay($model->kind, $model->credit_type, $model->remark, $bank, $game, $pro, $model->refer_table, $model->refer_code);

        $amount = ($model->kind === 'TRANSFER' ? ($model->credit_type === 'D' ? $model->amount : $model->credit) : $model->amount);
        $bonus = ($model->kind === 'TRANSFER' ? ($model->credit_type === 'D' ? $model->bonus : $model->credit_bonus) : $model->bonus);
        $total = ($model->kind === 'TRANSFER' ? ($model->credit_type === 'D' ? $model->total : $model->credit_total) : $model->total);
        $remark = $model->remark;
        return [
            'code' => ++$this->no,
            'date' => $model->date_create->format('d/m/Y H:i:s'),
            'user_name' => ( is_null($model->member) ? 'ไม่พบข้อมูล' : $model->member->user_name),
            'name' => (is_null($model->member) ? 'ไม่พบข้อมูล' : $model->member->name),
            'method' => $type,
            'game_user' => (is_null($model->game_user) ? '' : $model->game_user->user_name),
            'amount' => $amount,
            'enable' => ($model->enable == 'Y' ? '<span class="text-danger"><i class="fa fa-check"></i></span>' : '<span class="text-warning"><i class="fa fa-times"></i></span>'),
            'remark' => $remark,
            'pro_name' => $pro,
            'bonus' => $bonus,
            'total' => $total,
            'wallet_before' => $model->balance_before,
            'wallet_after' => $model->balance_after,
            'credit_before' => $model->credit_before,
            'credit_after' => $model->credit_after,
            'amount_balance' => $model->amount_balance,
            'withdraw_limit_amount' => $model->withdraw_limit_amount,
            'withdraw_limit' => $model->withdraw_limit,
//            'ip' => '<span class="text-long" data-toggle="tooltip" title="' . $model->ip . '">' . Str::limit($model->ip, 10) . '</span>',
            'ip' => $model->ip,
            'user_create' => ($model->emp_code > 0 ? (!is_null($model->admin) ? $model->admin->name . ' ' . $model->admin->surname : strip_tags($model->user_create)) : strip_tags($model->user_create)),
        ];
    }


}
