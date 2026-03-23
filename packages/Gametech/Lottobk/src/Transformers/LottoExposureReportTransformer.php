<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoNumberExposure;
use League\Fractal\TransformerAbstract;

class LottoExposureReportTransformer extends TransformerAbstract
{
    public function transform(LottoNumberExposure $model): array
    {
        return [
            'id' => (int) $model->id,
            'draw_id' => (int) $model->draw_id,
            'draw_date' => optional($model->draw?->draw_date)->format('d/m/Y') ?? '-',
            'market_name' => $model->draw?->market?->name ?? '-',
            'bet_type' => BetType::label((string) $model->bet_type),
            'number' => '<code>' . e((string) $model->number) . '</code>',
            'sold_amount' => number_format((float) $model->sold_amount, 2),
        ];
    }
}

