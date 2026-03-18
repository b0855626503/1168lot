<?php

namespace Gametech\Admin\Transformers;

use Gametech\LogAdmin\Contracts\Activity;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class RpStaffLogTransformer extends TransformerAbstract
{


    public function transform(Activity $model)
    {


        return [
            'id' => (int)$model->id,
            'desctiption' => $model->description,
            'detail' => Str::limit($model->details,100),
            'user_name' => (is_null($model->admin) ? '-' : $model->admin->user_name),
            'member_name' => (is_null($model->admin) ? 'Guest' : $model->admin->name . ' ' . $model->admin->surname),
            'route' => '<span class="text-long" data-toggle="tooltip" title="' . $model->route . '">' . Str::limit($model->route, 50) . '</span>',
            'time' => $model->created_at->format('d-m-Y H:i:s'),
            'ip' => $model->ipAddress
        ];
    }


}
