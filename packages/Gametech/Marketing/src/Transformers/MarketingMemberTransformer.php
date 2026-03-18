<?php

namespace Gametech\Marketing\Transformers;

use Gametech\Marketing\Contracts\MarketingMember;
use League\Fractal\TransformerAbstract;

class MarketingMemberTransformer extends TransformerAbstract
{
    public function transform(MarketingMember $model)
    {

        $total_deposit = number_format($model->total_deposit);
        $today_deposit = number_format($model->today_deposit);
        $total_withdraw = number_format($model->total_withdraw);
        $today_withdraw = number_format($model->today_withdraw);
        $first = number_format($model->firstDeposit->value ?? 0);

//        if (bouncer()->hasPermission('marketing.marketing_campaign.dashboard.member_list_deposit')) {
//            $total_deposit = number_format($model->total_deposit);
//            $today_deposit = number_format($model->today_deposit);
//        } else {
//            $total_deposit = '******';
//            $today_deposit = '******';
//        }
//
//        if (bouncer()->hasPermission('marketing.marketing_campaign.dashboard.member_list_withdraw')) {
//            $total_withdraw = number_format($model->total_withdraw);
//            $today_withdraw = number_format($model->today_withdraw);
//        } else {
//            $total_withdraw = '******';
//            $today_withdraw = '******';
//        }
//
//        if (bouncer()->hasPermission('marketing.marketing_campaign.dashboard.member_list_first')) {
//            $first = number_format(optional($model->firstDeposit)->value ?? 0);
//
//        } else {
//            $first = '******';
//
//        }

        return [
            'id' => (int) $model->code,
            'name' => $model->name,
            'username' => $model->user_name,
            'date_regis' => $model->date_regis->format('d M Y'),
            'total_deposit' => $total_deposit,
            'deposit' => $today_deposit,
            'first_deposit' => $first,
            'total_withdraw' => $total_withdraw,
            'withdraw' => $today_withdraw,
        ];
    }
}
