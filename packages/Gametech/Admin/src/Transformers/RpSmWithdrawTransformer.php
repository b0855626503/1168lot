<?php

namespace Gametech\Admin\Transformers;

use Gametech\Payment\Models\Withdraw;
use League\Fractal\TransformerAbstract;

class RpSmWithdrawTransformer extends TransformerAbstract
{

    protected $no;

    public function __construct($no = 0)
    {
        $this->no = $no;

    }

    public function transform(Withdraw $model)
    {


        return [
            'code' => (int)$model->code,
            'no' => ++$this->no,
            'date_approve' => (is_null($model->date_approve) ? '' : $model->date_approve->format('d/m/y H:i:s')),
            'amount' => core()->textcolor(core()->currency($model->amount), 'text-success'),
            'username' => (is_null($model->member) ? '' : $model->member->user_name),
            'msg' => (!is_null($model->bills->first()) ? (is_null($model->bills->first()->promotion) ? '' : $model->bills->first()->promotion['name_th']) . ' [' . $model->bills->first()->date_create->format('d/m/Y') . ']' : ''),
            'fee' => $model->fee,
        ];
    }


}
