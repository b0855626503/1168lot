<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Support\LottoMarketDisplayFormatter;
use League\Fractal\TransformerAbstract;

class LottoBlockedNumbersReportTransformer extends TransformerAbstract
{
    public function __construct(private readonly LottoMarketDisplayFormatter $marketDisplayFormatter = new LottoMarketDisplayFormatter) {}

    public function transform($row): array
    {
        return [
            'draw_date' => $row->draw_date ? date('d/m/Y', strtotime((string) $row->draw_date)) : '-',
            'market_name' => $this->marketDisplayFormatter->formatHtml(
                (string) ($row->market_name ?? '-'),
                (string) ($row->market_logo ?? ''),
                (string) ($row->market_icon ?? ''),
                (string) ($row->market_result_mode ?? ''),
                isset($row->yeekee_round_no) ? (int) $row->yeekee_round_no : null
            ),
            'bet_type' => BetType::label((string) ($row->bet_type ?? '')),
            'number' => '<code>'.e((string) ($row->number ?? '-')).'</code>',
            'mode' => (string) ($row->mode ?? '') === 'limit_future'
                ? '<span class="badge badge-warning">จำกัดอนาคต</span>'
                : '<span class="badge badge-danger">อั้น</span>',
            'blocked_at' => $row->blocked_at ? date('d/m/Y H:i', strtotime((string) $row->blocked_at)) : '-',
            'updated_at' => $row->updated_at ? date('d/m/Y H:i', strtotime((string) $row->updated_at)) : '-',
        ];
    }
}
