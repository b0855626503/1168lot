<?php

namespace Gametech\Admin\Transformers;


use Gametech\Payment\Contracts\BankAccount;
use League\Fractal\TransformerAbstract;

class BankAccountTransformer extends TransformerAbstract
{


    public function transform(BankAccount $model)
    {


        return [
            'code' => (int)$model->code,
            'acc_name' => $model->acc_name,
            'acc_no' => $model->acc_no,
            'banks' => $model->banks
        ];
    }


}
