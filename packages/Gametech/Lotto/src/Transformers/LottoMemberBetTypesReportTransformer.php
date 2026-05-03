<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Support\LottoMarketDisplayFormatter;
use League\Fractal\TransformerAbstract;

class LottoMemberBetTypesReportTransformer extends TransformerAbstract
{
    public function __construct(private readonly LottoMarketDisplayFormatter $marketDisplayFormatter = new LottoMarketDisplayFormatter) {}

    public function transform($row): array
    {
        return [
            'member_display' => $this->memberDisplay($row),
            'market_name' => $this->marketDisplayFormatter->formatHtml(
                (string) ($row->market_name ?? '-'),
                (string) ($row->market_logo ?? ''),
                (string) ($row->market_icon ?? ''),
                (string) ($row->market_result_mode ?? ''),
                isset($row->yeekee_round_no) ? (int) $row->yeekee_round_no : null
            ),
            'bet_type' => BetType::label((string) ($row->bet_type ?? '')),
            'ticket_count' => (int) ($row->ticket_count ?? 0),
            'total_bet_amount' => number_format((float) ($row->total_bet_amount ?? 0), 2),
            'net_result' => $this->signedMoney((float) ($row->net_result ?? 0)),
        ];
    }

    private function memberDisplay($row): string
    {
        $name = trim((string) ($row->member_user_name ?? $row->member_name ?? ''));
        $memberId = (int) ($row->member_id ?? 0);

        if ($name === '') {
            $name = 'MEM-'.$memberId;
        }

        return e($name.($memberId > 0 ? ' ('.$memberId.')' : ''));
    }

    private function signedMoney(float $value): string
    {
        $formatted = number_format(abs($value), 2);

        if ($value > 0) {
            return '<span class="text-success">+'.$formatted.'</span>';
        }

        if ($value < 0) {
            return '<span class="text-danger">-'.$formatted.'</span>';
        }

        return number_format(0, 2);
    }
}
