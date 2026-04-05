<?php

namespace Gametech\Lotto\Transformers;

use League\Fractal\TransformerAbstract;

class LottoTicketsCancelReportTransformer extends TransformerAbstract
{
    public function transform($row): array
    {
        $eventAt = $row->cancelled_at ?: $row->created_at;
        $drawDate = $row->draw_date ? date('d/m/Y', strtotime((string) $row->draw_date)) : '-';
        $memberName = trim((string) ($row->member_user_name ?? $row->member_name ?? ''));
        $packageNames = collect($row->items ?? [])
            ->pluck('package_name_at_time')
            ->map(static fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values();
        $cancelledBy = $this->resolveCancelledByName($row);

        return [
            'event_at' => $eventAt ? date('d/m/Y H:i', strtotime((string) $eventAt)) : '-',
            'id' => (int) ($row->id ?? 0),
            'member_display' => e(($memberName !== '' ? $memberName : ('MEM-' . (int) ($row->member_id ?? 0))) . ' (' . (int) ($row->member_id ?? 0) . ')'),
            'market_name' => $this->formatMarket((string) ($row->market_name ?? '-'), (string) ($row->market_logo ?? ''), (string) ($row->market_icon ?? '')),
            'draw_date' => $drawDate,
            'package_name' => $packageNames->isNotEmpty() ? e($packageNames->implode(', ')) : '-',
            'total_bet_amount' => number_format((float) ($row->total_bet_amount ?? $row->total_amount ?? 0), 2),
            'total_discount_amount' => number_format((float) ($row->total_discount_amount ?? 0), 2),
            'total_net_amount' => number_format((float) ($row->total_net_amount ?? $row->total_amount ?? 0), 2),
            'total_win_amount' => number_format((float) ($row->total_win_amount ?? 0), 2),
            'status' => $this->statusBadge((string) ($row->status ?? '')),
            'reason' => e($this->resolveReason($row)),
            'cancelled_by_name' => e($cancelledBy !== '' ? $cancelledBy : '-'),
        ];
    }

    private function resolveCancelledByName($row): string
    {
        $cancelTxActor = match ((string) ($row->cancel_tx_created_by_type ?? '')) {
            'admin' => trim((string) ($row->cancel_tx_admin_user_name ?? '')),
            'member' => trim((string) ($row->cancel_tx_member_user_name ?? $row->cancel_tx_member_name ?? '')),
            default => '',
        };

        if ($cancelTxActor !== '') {
            return $cancelTxActor;
        }

        return trim((string) ($row->cancel_admin_user_name ?? $row->cancel_member_user_name ?? $row->cancel_member_name ?? ''));
    }

    private function resolveReason($row): string
    {
        $ticketReason = trim((string) ($row->reason ?? ''));
        if ($ticketReason !== '') {
            return $ticketReason;
        }

        $meta = $row->cancel_tx_meta ?? null;
        if (! is_string($meta) || trim($meta) === '') {
            return '-';
        }

        $decoded = json_decode($meta, true);
        $metaReason = is_array($decoded) ? trim((string) ($decoded['reason'] ?? '')) : '';

        return $metaReason !== '' ? $metaReason : '-';
    }

    private function formatMarket(string $marketName, string $logo, string $icon): string
    {
        $safeName = e(trim($marketName) !== '' ? $marketName : '-');
        $image = trim($logo) !== '' ? $logo : $icon;

        if ($image === '') {
            return $safeName;
        }

        return '<span class="d-inline-flex align-items-center">'
            . '<img src="' . e($image) . '" alt="" style="width:18px;height:18px;object-fit:contain;margin-right:6px;" />'
            . '<span>' . $safeName . '</span>'
            . '</span>';
    }

    private function statusBadge(string $status): string
    {
        return match ($status) {
            'active' => '<span class="badge badge-success">รอผล</span>',
            'cancelled' => '<span class="badge badge-danger">ยกเลิก</span>',
            'resulted' => '<span class="badge badge-info">ตัดสินแล้ว</span>',
            default => '<span class="badge badge-light">' . e($status !== '' ? $status : '-') . '</span>',
        };
    }
}
