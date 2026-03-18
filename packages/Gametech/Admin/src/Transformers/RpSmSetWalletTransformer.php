<?php

namespace Gametech\Admin\Transformers;

use Gametech\Member\Models\MemberCreditLog;
use League\Fractal\TransformerAbstract;

class RpSmSetWalletTransformer extends TransformerAbstract
{

    protected $no;

    public function __construct($no = 0)
    {
        $this->no = $no;

    }

    public function transform(MemberCreditLog $model)
    {


        return [
            'code' => (int)$model->code,
            'no' => ++$this->no,
            'date_approve' => (is_null($model->date_create) ? '' : $model->date_create ->format('d/m/y H:i:s')),
            'amount' => core()->textcolor(core()->currency($model->amount), 'text-success'),
            'username' => (is_null($model->member) ? '' : $model->member->user_name),
            'msg' => $model->remark,
            'type' => $model->credit_type == 'D' ? '<span class="badge badge-danger">เพิ่ม</span>' : '<span class="badge badge-info">ลด</span>',
        ];
    }


}
