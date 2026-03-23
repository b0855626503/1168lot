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
        $marketName = $model->draw && $model->draw->market ? $model->draw->market->name : '-';

        return [
            'id'         => (int) $model->id,
            'draw'       => $drawDate . ' (' . $marketName . ')',
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

