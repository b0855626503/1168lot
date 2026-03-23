<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoTicketDataTable;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LotteryMarket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LottoTicketController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(LottoTicketDataTable $dataTable)
    {
        $marketOptions = LotteryMarket::query()
            ->with('group:id,name,sort')
            ->orderBy('group_id')
            ->orderBy('name')
            ->get(['id', 'group_id', 'name'])
            ->groupBy(static function (LotteryMarket $market): string {
                return (string) optional($market->group)->name ?: 'ไม่ระบุกลุ่ม';
            })
            ->map(static function ($markets, $groupName): array {
                return [
                    'label' => (string) $groupName,
                    'options' => $markets->map(static fn (LotteryMarket $market): array => [
                        'value' => (int) $market->id,
                        'text' => (string) $market->name,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->toArray();

        $drawOptionsByMarket = LottoDraw::query()
            ->orderByDesc('draw_date')
            ->orderByDesc('id')
            ->get(['id', 'market_id', 'draw_date'])
            ->groupBy(static fn (LottoDraw $draw): int => (int) $draw->market_id)
            ->map(static function ($draws): array {
                return $draws->map(static function (LottoDraw $draw): array {
                    return [
                        'value' => (int) $draw->id,
                        'text' => 'งวด #' . (int) $draw->id . ' - ' . ($draw->draw_date ? $draw->draw_date->format('d/m/Y') : '-'),
                    ];
                })->values()->all();
            })
            ->toArray();

        return $dataTable->render($this->_config['view'], [
            'marketOptions' => $marketOptions,
            'drawOptionsByMarket' => $drawOptionsByMarket,
        ]);
    }

    public function loadData(Request $request): JsonResponse
    {
        $ticket = LottoTicket::query()
            ->with(['member', 'draw.market', 'items'])
            ->withSum('items as total_win_amount', 'win_amount')
            ->find((int) $request->input('id'));

        if (! $ticket) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $payload = [
            'id' => (int) $ticket->id,
            'member_id' => (int) $ticket->member_id,
            'member_name' => $ticket->member->user_name ?? $ticket->member->name ?? ('MEM-' . $ticket->member_id),
            'draw' => [
                'id' => (int) $ticket->draw_id,
                'date' => optional($ticket->draw?->draw_date)->format('d/m/Y'),
                'market' => $ticket->draw?->market?->name,
            ],
            'status' => (string) $ticket->status,
            'total_amount' => (float) $ticket->total_amount,
            'total_win_amount' => (float) ($ticket->total_win_amount ?? 0),
            'items' => $ticket->items->map(function ($item) {
                return [
                    'bet_type' => (string) $item->bet_type,
                    'bet_type_label' => BetType::label((string) $item->bet_type),
                    'number' => (string) $item->number,
                    'amount' => (float) $item->amount,
                    'payout_at_time' => (float) $item->payout_at_time,
                    'discount_percent_at_time' => (float) ($item->discount_percent_at_time ?? 0),
                    'discount_amount_at_time' => (float) ($item->discount_amount_at_time ?? 0),
                    'payable_amount_at_time' => (float) ($item->payable_amount_at_time ?? 0),
                    'potential_win_amount_at_time' => (float) ($item->potential_win_amount_at_time ?? 0),
                    'result_status' => $item->result_status,
                    'win_amount' => (float) ($item->win_amount ?? 0),
                ];
            })->values()->all(),
        ];

        return $this->sendResponse($payload, 'ดำเนินการเสร็จสิ้น');
    }
}
