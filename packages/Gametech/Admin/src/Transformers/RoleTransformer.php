<?php

namespace Gametech\Admin\Transformers;


use Gametech\Admin\Contracts\Role;
use League\Fractal\TransformerAbstract;

class RoleTransformer extends TransformerAbstract
{


    public function transform(Role $model)
    {

        return [
            'code' => (int)$model->code,
            'name' => $model->name,
            'description' => $model->description,
            'permission_type' => $model->permission_type,
            'action' => view('admin::module.roles.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
