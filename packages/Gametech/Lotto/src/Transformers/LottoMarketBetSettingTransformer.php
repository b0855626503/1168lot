<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Contracts\LottoMarketBetSetting;
use Gametech\Lotto\Enums\BetType;
use League\Fractal\TransformerAbstract;

class LottoMarketBetSettingTransformer extends TransformerAbstract
{
    public function transform(LottoMarketBetSetting $model): array
    {
        return [
            'id'               => (int) $model->id,
            'market_name'      => $model->market->name ?? '-',
            'bet_type'         => $model->bet_type . ' = ' . BetType::label((string) $model->bet_type),
            'payout'           => (float) $model->payout,
            'min_bet'          => (float) $model->min_bet,
            'max_bet'          => (float) $model->max_bet,
            'max_per_number'   => (float) $model->max_per_number,
            'is_enabled'       => '<button type="button" class="btn ' . ($model->is_enabled ? 'btn-success' : 'btn-danger') . ' btn-xs"'
                . ' onclick="editdata(' . $model->id . ',' . ($model->is_enabled ? '0' : '1') . ',\'is_enabled\')">'
                . ($model->is_enabled ? '<i class="fa fa-check"></i> เปิด' : '<i class="fa fa-times"></i> ปิด')
                . '</button>',
            'action'           => view('admin::module.lotto.default_settings.datatables_actions', [
                'id' => $model->id,
            ])->render(),
        ];
    }
}
