<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Contracts\LottoNumberBlock;
use Gametech\Lotto\Enums\BetType;
use League\Fractal\TransformerAbstract;

class LottoNumberBlockTransformer extends TransformerAbstract
{
    public function transform(LottoNumberBlock $model): array
    {
        $drawDate = $model->draw && $model->draw->draw_date ? $model->draw->draw_date->format('d/m/Y') : '-';
        $market = $model->draw ? $model->draw->market : null;
        $marketName = $market ? (string) $market->name : '-';
        $logo = $market ? (string) ($market->logo ?: $market->icon ?: '') : '';
        $escapedMarketName = htmlspecialchars($marketName, ENT_QUOTES, 'UTF-8');
        $marketDisplay = $escapedMarketName;
        if ($logo !== '') {
            $escapedLogo = htmlspecialchars($logo, ENT_QUOTES, 'UTF-8');
            $marketDisplay = '<span class="d-inline-flex align-items-center">'
                . '<img src="' . $escapedLogo . '" alt="" style="width:18px;height:18px;object-fit:contain;margin-right:6px;" />'
                . '<span>' . $escapedMarketName . '</span>'
                . '</span>';
        }

        return [
            'select'     => '<input type="checkbox" class="js-lotto-row-selector-number-blocks" value="' . (int) $model->id . '">',
            'id'         => (int) $model->id,
            'draw_date'  => $drawDate,
            'market'     => $marketDisplay,
            'bet_type'   => $model->bet_type . ' = ' . BetType::label((string) $model->bet_type),
            'number'     => $model->number,
            'mode'       => $model->mode === 'limit_future' ? 'จำกัดอนาคต' : 'อั้น',
            'blocked_at' => $model->blocked_at ? $model->blocked_at->format('d/m/Y H:i') : '-',
            'action'     => view('admin::module.lotto.number_blocks.datatables_actions', [
                'id' => $model->id,
            ])->render(),
        ];
    }
}
