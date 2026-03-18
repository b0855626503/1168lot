<?php

namespace Gametech\Admin\Transformers;


use Gametech\Member\Contracts\MemberLog;
use Gametech\Promotion\Contracts\Promotion;
use League\Fractal\TransformerAbstract;

class MemberLogTransformer extends TransformerAbstract
{


    public function transform(MemberLog $model)
    {

        return [
            'code' => (int)$model->code,
            'date_create' => $model->date_create->format('Y-m-d H:i:s'),
            'username' => $model->username,
            'password' => $model->password,
            'username_real' => $model->username_real,
            'password_real' => $model->password_real,
            'summary' => $model->summary,
          ];
    }


}
