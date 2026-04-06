<?php

namespace Gametech\Marketing\Transformers;

use Gametech\Marketing\Contracts\MarketingCampaign;
use League\Fractal\TransformerAbstract;

class MarketingCampaignTransformer extends TransformerAbstract
{
    public function transform(MarketingCampaign $model)
    {

        $baseUrl = rtrim(env('FRONTEND_URL', ''), '/');
        $registrationLink = $model->registrationLink;
        $marketCode = $registrationLink ? (string) $registrationLink->code : '';
        $registerUrl = $baseUrl !== '' && $marketCode !== ''
            ? $baseUrl . '/th/register?market=' . rawurlencode($marketCode)
            : '';

        return [
            'id' => (int) $model->id,
            'name' => $model->name,
            'description' => $model->description,
            'team_id' => ($model->team ? $model->team->name : '<span class="text-muted">-</span>'),
            'link' => $registerUrl
                ? '<a href="' . e($registerUrl) . '" target="_blank">' . e($registerUrl) . '</a>'
                : '<span class="text-muted">-</span>',

            'is_ended' => '<button class="btn btn-xs icon-only '.($model->is_ended == true ? 'btn-danger' : 'btn-success').'" onclick="editdata('.$model->id.','."'".core()->flip2($model->is_ended)."'".','."'is_ended'".')" '.($model->is_ended == true ? 'disabled':'').'>'.($model->is_ended == true ? '<i class="fa fa-lock"></i>' : '<i class="fa fa-check"></i>').'</button>',
            'enable' => '<button class="btn btn-xs icon-only '.($model->enable == true ? 'btn-success' : 'btn-danger').'" onclick="editdata('.$model->id.','."'".core()->flip2($model->enable)."'".','."'enable'".')">'.($model->enable == true ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>').'</button>',
            'action' => view('admin::module.marketing_campaign.datatables_actions', ['code' => $model->id])->render(),
        ];
    }
}
