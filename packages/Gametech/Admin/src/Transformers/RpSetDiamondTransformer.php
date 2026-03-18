<?php

namespace Gametech\Admin\Transformers;


use Gametech\Member\Contracts\MemberDiamondLog;
use League\Fractal\TransformerAbstract;

class RpSetDiamondTransformer extends TransformerAbstract
{

    protected $no;

    public function __construct($no = 1)
    {
        $this->no = $no;

    }

    public function transform(MemberDiamondLog $model)
    {


        return [
            'code' => ++$this->no,
            'member_name' => (is_null($model->member) ? '' : $model->member->name),
            'user_name' => (is_null($model->member) ? '' : $model->member->user_name),
            'credit_type' => ($model->diamond_type == 'D' ? "<span class='badge badge-success'> เพิ่ม Diamond </span>" : "<span class='badge badge-danger'> ลด Diamond </span>"),
            'total' => "<span class='text-primary'> " . core()->currency($model->diamond_amount) . " </span>",
            'balance_before' => "<span class='text-info'> " . core()->currency($model->diamond_before) . " </span>",
            'balance_after' => "<span class='text-danger'> " . core()->currency($model->diamond_balance) . " </span>",
            'remark' => $model->remark,
            'emp_name' => ($model->emp_code === 0 ? $model->user_create : (isset($model->admin->name) ? $model->admin->name : '') ),
            'date_create' => $model->date_create->format('d/m/y H:i:s'),
            'ip' => $model->ip
        ];
    }


}
