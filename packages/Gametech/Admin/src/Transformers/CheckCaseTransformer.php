<?php

namespace Gametech\Admin\Transformers;


use Gametech\Core\Contracts\CheckCase;
use Gametech\Game\Contracts\Game;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Fractal\TransformerAbstract;

class CheckCaseTransformer extends TransformerAbstract
{


    public function transform(CheckCase $model)
    {


        $methods = [1 => 'ฝาก', 2 => 'ถอน', '' => 'ไม่ระบุ', 0 => 'ไม่ระบุ'];

        return [
            'code' => (int)$model->code,
            'txid' => $model->txid,
            'bank_code' => $model->bank->name_th,
            'method' => $methods[$model->method],
            'username' => $model->username,
            'name' => $model->name,
            'amount' => $model->amount,
            'payamount' => $model->payamount,
            'status' => $model->status,
            'detail' => $model->detail,
            'url' => $model->url,
            'date_create' => $model->date_create->format('Y-m-d H:i:s'),
            'date_update' => $model->date_update->format('Y-m-d H:i:s'),
            'DT_RowClass' => $model->method === 2 ? 'text-red' : '',
        ];
    }


}
