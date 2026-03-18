<?php

namespace Gametech\Admin\Transformers;


use Gametech\Admin\Contracts\Admin;
use League\Fractal\TransformerAbstract;

class AdminTransformer extends TransformerAbstract
{


    public function transform(Admin $model)
    {

        return [
            'code' => (int)$model->code,
            'name' => $model->name,
            'surname' => $model->surname,
            'username' => $model->user_name,
            'mobile' => $model->mobile,
            'lastlogin' => (is_null($model->lastlogin) ? '' : $model->lastlogin->format('d/m/Y H:i')),
            'role' => (is_null($model->role) ? '' : $model->role->name),
            'auth' => '<button type="button" class="btn ' . ($model->google2fa_enable == 1 ? 'btn-success' : 'btn-danger') . ' btn-xs icon-only" onclick="editdata(' . $model->code . "," . "'" . core()->flipnum($model->google2fa_enable) . "'" . "," . "'google2fa_enable'" . ')">' . ($model->google2fa_enable == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>',
            'enable' => '<button type="button" class="btn ' . ($model->enable == 'Y' ? 'btn-success' : 'btn-danger') . ' btn-xs icon-only" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->enable) . "'" . "," . "'enable'" . ')">' . ($model->enable == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>',
            'action' => view('admin::module.employees.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
