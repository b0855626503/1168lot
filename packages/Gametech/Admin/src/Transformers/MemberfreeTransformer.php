<?php

namespace Gametech\Admin\Transformers;


use Gametech\Member\Contracts\Member;
use League\Fractal\TransformerAbstract;

class MemberfreeTransformer extends TransformerAbstract
{


    public function transform(Member $model)
    {


//dd($model->banks_account->toJson(JSON_PRETTY_PRINT));
        if ($model->freecredit == 'Y') {
            $credit = '<span style="color:blue">' . $model->balance_free . '</span>';
            $action = view('admin::module.member_free.datatables_actions', ['code' => $model->code])->render();
        } else {
            $credit = '<button class="btn btn-xs icon-only btn-danger" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->freecredit) . "'" . "," . "'freecredit'" . ')"><i class="fa fa-dollar-sign"></i></button>';
            $action = '';
        }


        return [
            'code' => (int)$model->code,
            'date' => $model->date_regis->format('d/m/Y'),
            'firstname' => $model->firstname,
            'lastname' => $model->lastname,
            'user_name' => $model->user_name,
            'pass' => $model->user_pass,
            'balance' => $credit,
            'action' => $action
        ];
    }


}
