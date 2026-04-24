<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoTicketsCancelReportDataTable;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LottoTicketsCancelReportController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(LottoTicketsCancelReportDataTable $dataTable)
    {
        $marketOptions = LotteryMarket::query()
            ->with('group:id,name,sort')
            ->orderBy('group_id')
            ->orderBy('name')
            ->get(['id', 'group_id', 'name', 'logo', 'icon'])
            ->groupBy(static function (LotteryMarket $market): string {
                return (string) optional($market->group)->name ?: 'ไม่ระบุกลุ่ม';
            })
            ->map(static function ($markets, $groupName): array {
                return [
                    'label' => (string) $groupName,
                    'options' => $markets->map(static fn (LotteryMarket $market): array => [
                        'value' => (int) $market->id,
                        'text' => (string) $market->name,
                        'logo' => (string) ($market->logo ?: $market->icon ?: ''),
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();

        $statusOptions = [
            ['value' => 'active', 'text' => 'รอผล'],
            ['value' => 'cancelled', 'text' => 'ยกเลิก'],
            ['value' => 'resulted', 'text' => 'ตัดสินแล้ว'],
            ['value' => 'won', 'text' => 'ถูกรางวัล'],
        ];

        return $dataTable->render($this->_config['view'], [
            'marketOptions' => $marketOptions,
            'statusOptions' => $statusOptions,
            'ticketDetailUrl' => route('admin.lotto.reports.tickets_cancel.ticket_detail'),
        ]);
    }

    public function ticketDetail(Request $request)
    {
        $id = (int) $request->query('id', 0);
        if ($id <= 0) {
            return response()->json(['message' => 'ไม่พบข้อมูลโพยที่ระบุ'], 422);
        }

        $ticket = LottoTicket::query()
            ->with([
                'member:code,user_name,name',
                'draw:id,market_id,draw_date',
                'draw.market:id,name',
                'items:id,ticket_id,bet_type,number,amount,package_name_at_time,payout_at_time,discount_percent_at_time,discount_amount_at_time,payable_amount_at_time,win_amount,result_status',
            ])
            ->find($id);

        if (! $ticket) {
            return response()->json(['message' => 'ไม่พบโพยที่เลือก'], 404);
        }

        $cancelTx = DB::table('wallet_transactions')
            ->where('ref_type', 'LOTTO_CANCEL')
            ->where('ref_id', (int) $ticket->id)
            ->orderByDesc('id')
            ->first(['created_by_type', 'created_by_id', 'meta']);

        $cancelledBy = '-';
        if ($cancelTx && ! empty($cancelTx->created_by_id)) {
            if ((string) $cancelTx->created_by_type === 'admin') {
                $adminName = (string) DB::table('employees')
                    ->where('code', (int) $cancelTx->created_by_id)
                    ->value('user_name');
                $cancelledBy = $adminName !== '' ? $adminName : '-';
            } elseif ((string) $cancelTx->created_by_type === 'member') {
                $memberName = (string) DB::table('members')
                    ->where('code', (int) $cancelTx->created_by_id)
                    ->value('user_name');
                $cancelledBy = $memberName !== '' ? $memberName : '-';
            }
        } elseif (! empty($ticket->cancelled_by)) {
            $adminName = (string) DB::table('employees')
                ->where('code', (int) $ticket->cancelled_by)
                ->value('user_name');
            if ($adminName !== '') {
                $cancelledBy = $adminName;
            } else {
                $memberName = (string) DB::table('members')
                    ->where('code', (int) $ticket->cancelled_by)
                    ->value('user_name');
                $cancelledBy = $memberName !== '' ? $memberName : '-';
            }
        }

        $memberDisplay = trim((string) ($ticket->member?->user_name ?: $ticket->member?->name ?: ''));
        if ($memberDisplay === '') {
            $memberDisplay = 'MEM-'.(int) $ticket->member_id;
        }
        $memberDisplay .= ' ('.(int) $ticket->member_id.')';

        $reason = trim((string) ($ticket->reason ?? ''));
        if ($reason === '' && $cancelTx && is_string($cancelTx->meta) && trim($cancelTx->meta) !== '') {
            $meta = json_decode((string) $cancelTx->meta, true);
            $reason = is_array($meta) ? trim((string) ($meta['reason'] ?? '')) : '';
        }
        if ($reason === '') {
            $reason = '-';
        }

        $packageNames = collect($ticket->items ?? [])
            ->pluck('package_name_at_time')
            ->map(static fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values();

        return response()->json([
            'ticket' => [
                'id' => (int) $ticket->id,
                'member_display' => $memberDisplay,
                'market_name' => (string) ($ticket->draw?->market?->name ?? '-'),
                'draw_date' => $ticket->draw?->draw_date ? $ticket->draw->draw_date->format('d/m/Y') : '-',
                'status' => (string) $ticket->status,
                'status_label' => $this->statusLabel((string) $ticket->status),
                'reason' => $reason,
                'cancelled_by_name' => $cancelledBy,
                'created_at' => $ticket->created_at ? $ticket->created_at->format('d/m/Y H:i') : '-',
                'cancelled_at' => $ticket->cancelled_at ? $ticket->cancelled_at->format('d/m/Y H:i') : '-',
                'latest_updated_at' => ($ticket->cancelled_at ?: $ticket->updated_at ?: $ticket->created_at)?->format('d/m/Y H:i') ?: '-',
                'total_bet_amount' => (float) ($ticket->total_bet_amount ?? $ticket->total_amount ?? 0),
                'total_discount_amount' => (float) ($ticket->total_discount_amount ?? 0),
                'total_net_amount' => (float) ($ticket->total_net_amount ?? $ticket->total_amount ?? 0),
                'total_win_amount' => (float) ($ticket->total_win_amount ?? 0),
                'refund_amount' => (float) ($ticket->refund_amount ?? 0),
                'packages' => $packageNames->isNotEmpty() ? $packageNames->implode(', ') : '-',
            ],
            'items' => collect($ticket->items ?? [])->map(static function ($item): array {
                $betType = (string) ($item->bet_type ?? '');
                $knownBetType = in_array($betType, BetType::all(), true);

                return [
                    'bet_type' => $betType,
                    'bet_type_label' => $knownBetType ? BetType::label($betType) : $betType,
                    'number' => (string) ($item->number ?? '-'),
                    'package_name' => (string) ($item->package_name_at_time ?? '-'),
                    'amount' => (float) ($item->amount ?? 0),
                    'discount_amount' => (float) ($item->discount_amount_at_time ?? 0),
                    'net_amount' => (float) ($item->payable_amount_at_time ?? $item->amount ?? 0),
                    'payout' => (float) ($item->payout_at_time ?? 0),
                    'win_amount' => (float) ($item->win_amount ?? 0),
                    'result_status' => (string) ($item->result_status ?? ''),
                ];
            })->values()->all(),
        ]);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'รอผล',
            'cancelled' => 'ยกเลิก',
            'resulted' => 'ตัดสินแล้ว',
            default => $status !== '' ? $status : '-',
        };
    }
}
