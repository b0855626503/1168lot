<?php

namespace Gametech\Admin\Transformers;


use Gametech\Member\Contracts\MemberCreditLog;
use League\Fractal\TransformerAbstract;

class RpWalletTransformer extends TransformerAbstract
{

    protected $no;

    public function __construct($no = 1)
    {
        $this->no = $no;

    }

    public function transform(MemberCreditLog $model)
    {


        return [
            'code' => ++$this->no,
            'member_name' => (is_null($model->member) ? '' : $model->member->name),
            'user_name' => (is_null($model->member) ? '' : $model->member->user_name),
            'credit_type' => ($model->credit_type == 'D' ? "<span class='badge badge-success'> เพิ่ม Wallet </span>" : "<span class='badge badge-danger'> ลด Wallet </span>"),
            'total' => "<span class='text-primary'> " . core()->currency($model->total) . " </span>",
            'balance_before' => "<span class='text-info'> " . core()->currency($model->balance_before) . " </span>",
            'balance_after' => "<span class='text-danger'> " . core()->currency($model->balance_after) . " </span>",
            'remark' => $model->remark,
            'emp_name' => ($model->emp_code === 0 ? '' : (is_null($model->admin) ? '' : $model->admin->name)),
            'date_create' => $model->date_create->format('d/m/y H:i:s'),
            'ip' => $model->ip
        ];
    }


}
