<?php

namespace Gametech\Admin\Transformers;

use Gametech\LogUser\Contracts\ActivityUser;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class RpUserLogTransformer extends TransformerAbstract
{


    public function transform(ActivityUser $model)
    {

//        dd($model->toJson(JSON_PRETTY_PRINT));

        return [
            'id' => (int)$model->id,
            'desctiption' => $model->description,
            'detail' => $model->details,
            'user_name' => (is_null($model->member) ? '-' : $model->member->user_name),
            'member_name' => (is_null($model->member) ? 'Guest' : $model->member->name),
            'route' => '<span class="text-long" data-toggle="tooltip" title="' . $model->route . '">' . Str::limit($model->route, 50) . '</span>',
            'time' => $model->created_at->format('d-m-Y H:i:s'),
            'ip' => $model->ipAddress
        ];
    }


}
