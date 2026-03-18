<?php

namespace Gametech\Admin\Transformers;


use Gametech\Member\Contracts\Member;
use League\Fractal\TransformerAbstract;
use Mozammil\Censor\Replacers\StarReplacer;

class MemberConfirmTransformer extends TransformerAbstract
{
    protected $config;

    protected $permiss;

    public function __construct($config, $permiss)
    {
        $this->config = $config;
        $this->permiss = $permiss;
    }


    public function transform(Member $model)
    {

        $config = $this->config;
        $permiss = $this->permiss;

        if ($permiss) {
            $tel = $model->tel;
        } else {
            $tel = StarReplacer::replace($model->tel);
        }

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
            'lineid' => $model->lineid,
            'bank' => (is_null($model->bank) ? '' : core()->displayBank($model->bank->shortcode, $model->bank->filepic)),
            'acc_no' => $model->acc_no,
            'tel' => $tel,
            'confirm' => '<button class="btn btn-xs ' . ($model->confirm == 'Y' ? 'btn-primary' : 'btn-primary') . '" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->confirm) . "'" . "," . "'confirm'" . ')">' . ($model->confirm == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-check"></i>อนุมัติ') . '</button>',
            'action' => view('admin::module.member_confirm.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
