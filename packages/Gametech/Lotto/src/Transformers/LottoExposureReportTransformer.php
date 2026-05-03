<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoNumberExposure;
use Gametech\Lotto\Support\LottoMarketDisplayFormatter;
use League\Fractal\TransformerAbstract;

class LottoExposureReportTransformer extends TransformerAbstract
{
    public function __construct(private readonly LottoMarketDisplayFormatter $marketDisplayFormatter = new LottoMarketDisplayFormatter) {}

    public function transform(LottoNumberExposure $model): array
    {
        return [
            'id' => (int) $model->id,
            'draw_id' => (int) $model->draw_id,
            'draw_date' => optional($model->draw?->draw_date)->format('d/m/Y') ?? '-',
            'market_name' => $this->marketDisplayFormatter->formatHtml(
                (string) ($model->draw?->market?->name ?? '-'),
                (string) ($model->draw?->market?->logo ?? ''),
                (string) ($model->draw?->market?->icon ?? ''),
                (string) ($model->draw?->market?->result_mode ?? ''),
                $model->draw && isset($model->draw->yeekee_round_no) ? (int) $model->draw->yeekee_round_no : null
            ),
            'bet_type' => BetType::label((string) $model->bet_type),
            'number' => '<code>'.e((string) $model->number).'</code>',
            'sold_amount' => number_format((float) $model->sold_amount, 2),
        ];
    }
}
