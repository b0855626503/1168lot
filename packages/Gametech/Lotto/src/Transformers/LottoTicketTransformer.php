<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Contracts\LottoTicket;
use League\Fractal\TransformerAbstract;

class LottoTicketTransformer extends TransformerAbstract
{
    public function transform(LottoTicket $model): array
    {
        $drawDate = $model->draw && $model->draw->draw_date ? $model->draw->draw_date->format('d/m/Y') : '-';
        $marketName = $model->draw && $model->draw->market ? $model->draw->market->name : '-';
        $marketLogo = $model->draw && $model->draw->market
            ? ((string) ($model->draw->market->logo ?: $model->draw->market->icon ?: ''))
            : '';
        $memberName = $model->member->user_name ?? $model->member->name ?? ('MEM-' . $model->member_id);
        $packageNames = collect($model->items ?? [])
            ->pluck('package_name_at_time')
            ->map(static fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values();

        return [
            'id' => (int) $model->id,
            'member_code' => $memberName . ' (' . $model->member_id . ')',
            'draw_date' => $drawDate,
            'market' => $this->formatMarket($marketName, $marketLogo),
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
            default => '<span class="badge badge-light">' . $status . '</span>',
        };
    }

    private function formatMarket(string $marketName, string $marketLogo): string
    {
        $safeName = e($marketName !== '' ? $marketName : '-');

        if (trim($marketLogo) === '') {
            return $safeName;
        }

        return sprintf(
            '<span style="display:flex;align-items:center;gap:8px;"><img src="%s" alt="" style="width:20px;height:20px;object-fit:cover;border-radius:50%%;border:1px solid #e5e7eb;"><span>%s</span></span>',
            e($marketLogo),
            $safeName
        );
    }
}
