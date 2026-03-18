<?php

namespace Gametech\Marketing\Transformers;

use Gametech\Marketing\Contracts\MarketingTeam;
use League\Fractal\TransformerAbstract;

class MarketingTeamTransformer extends TransformerAbstract
{
    public function transform(MarketingTeam $model)
    {

        return [
            'id' => (int) $model->id,
            'name' => $model->name,
            'username' => $model->username,
            'commission_rate' => $model->commission_rate,
            'link' => $model->registrationLink
                ? '<a href="'.route('customer.session.store', ['id' => $model->registrationLink->code]).'" target="_blank">'.$model->registrationLink->code.'</a>'
                : '<span class="text-muted">-</span>',

            'enable' => '<button class="btn btn-xs icon-only '.($model->enable == true ? 'btn-success' : 'btn-danger').'" onclick="editdata('.$model->id.','."'".core()->flip2($model->enable)."'".','."'enable'".')">'.($model->enable == true ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>').'</button>',
            'action' => view('admin::module.marketing_team.datatables_actions', ['code' => $model->id])->render(),
        ];
    }
}
