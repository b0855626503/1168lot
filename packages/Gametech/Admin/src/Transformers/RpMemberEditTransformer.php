<?php

namespace Gametech\Admin\Transformers;

use Gametech\Core\Contracts\Refer;
use Gametech\Member\Models\MemberEditLog;
use League\Fractal\TransformerAbstract;

class RpMemberEditTransformer extends TransformerAbstract
{


    public function transform(MemberEditLog $model)
    {

//        dd($model->toJson(JSON_PRETTY_PRINT));


        if($model->menu  === 'bank_code'){
            $item_before = $model->bank_before->name_th;
            $item = $model->bank_after->name_th;
        }else{
            $item_before = strip_tags($model->item_before);
            $item = strip_tags($model->item);
        }
        return [
            'date_create' => $model->date_create->format('Y-m-d H:i:s'),
            'user_name' => ($model->member ? $model->member->user_name : ''),
            'mode' => $model->mode,
            'item_before' => $item_before,
            'item' => $item,
            'remark' => strip_tags($model->remark),
            'emp_user' => $model->emp_user,
            'ip' => $model->ip
        ];
    }


}
