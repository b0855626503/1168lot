<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Contracts\LottoTicket;
use Gametech\Lotto\Support\LottoMarketDisplayFormatter;
use League\Fractal\TransformerAbstract;

class LottoTicketTransformer extends TransformerAbstract
{
    public function __construct(private readonly LottoMarketDisplayFormatter $marketDisplayFormatter = new LottoMarketDisplayFormatter) {}

    public function transform(LottoTicket $model): array
    {
        $drawDate = $model->draw && $model->draw->draw_date ? $model->draw->draw_date->format('d/m/Y') : '-';
        $marketName = $model->draw && $model->draw->market ? $model->draw->market->name : '-';
        $memberName = $model->member->user_name ?? $model->member->name ?? ('MEM-'.$model->member_id);
        $packageNames = collect($model->items ?? [])
            ->pluck('package_name_at_time')
            ->map(static fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values();

        return [
            'id' => (int) $model->id,
            'member_code' => $memberName.' ('.$model->member_id.')',
            'draw_date' => $drawDate,
            'market' => $this->marketDisplayFormatter->formatHtml(
                (string) $marketName,
                (string) ($model->draw->market->logo ?? ''),
                (string) ($model->draw->market->icon ?? ''),
                (string) ($model->draw->market->result_mode ?? ''),
                isset($model->draw->yeekee_round_no) ? (int) $model->draw->yeekee_round_no : null
            ),
            'package_name' => $packageNames->isNotEmpty() ? e($packageNames->implode(', ')) : '-',
            'total_bet_amount' => number_format((float) ($model->total_bet_amount ?? 0), 2),
            'total_discount_amount' => number_format((float) ($model->total_discount_amount ?? 0), 2),
            'total_net_amount' => number_format((float) ($model->total_net_amount ?? 0), 2),
            'status' => $this->statusBadge((string) $model->status),
            'action' => view('admin::module.lotto.tickets.datatables_actions', [
                'id' => $model->id,
            ])->render(),
        ];
    }

    private function statusBadge(string $status): string
    {
        return match ($status) {
            'active' => '<span class="badge badge-success">รอผล</span>',
            'cancelled' => '<span class="badge badge-danger">ยกเลิก</span>',
            'resulted' => '<span class="badge badge-info">ตัดสินแล้ว</span>',
            default => '<span class="badge badge-light">'.$status.'</span>',
        };
    }
}
