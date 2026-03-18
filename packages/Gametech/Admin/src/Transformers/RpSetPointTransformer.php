<?php

namespace Gametech\Admin\Transformers;


use Gametech\Member\Contracts\MemberPointLog;
use League\Fractal\TransformerAbstract;

class RpSetPointTransformer extends TransformerAbstract
{

    protected $no;

    public function __construct($no = 1)
    {
        $this->no = $no;

    }

    public function transform(MemberPointLog $model)
    {


        return [
            'code' => ++$this->no,
            'member_name' => (is_null($model->member) ? '' : $model->member->name),
            'user_name' => (is_null($model->member) ? '' : $model->member->user_name),
            'credit_type' => ($model->point_type == 'D' ? "<span class='badge badge-success'> เพิ่ม Point </span>" : "<span class='badge badge-danger'> ลด Point </span>"),
            'total' => "<span class='text-primary'> " . core()->currency($model->point_amount) . " </span>",
            'balance_before' => "<span class='text-info'> " . core()->currency($model->point_before) . " </span>",
            'balance_after' => "<span class='text-danger'> " . core()->currency($model->point_balance) . " </span>",
            'remark' => $model->remark,
            'emp_name' => ($model->emp_code === 0 ? $model->user_create : ($model->admin ? $model->admin->user_name : $model->user_create)),
            'date_create' => $model->date_create->format('d/m/y H:i:s'),
            'ip' => $model->ip
        ];
    }


}
