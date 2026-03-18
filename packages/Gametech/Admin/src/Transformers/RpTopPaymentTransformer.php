<?php

namespace Gametech\Admin\Transformers;

use Gametech\Member\Contracts\Member;
use League\Fractal\TransformerAbstract;

//use Gametech\Payment\Contracts\BankPayment;

class RpTopPaymentTransformer extends TransformerAbstract
{


    protected $no;

    public function __construct($no = 0)
    {
        $this->no = $no;

    }

    public function transform(Member $model)
    {


        return [
            'code' => ++$this->no,
            'username' => $model->user_name,
            'name' => $model->name,
            'amount' => core()->currency($model->amount),
            'refill' => $model->date_refill
        ];
    }


}
