<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoTicketDataTable;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoTicket;
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
        return $dataTable->render($this->_config['view']);
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
                    'result_status' => $item->result_status,
                    'win_amount' => (float) ($item->win_amount ?? 0),
                ];
            })->values()->all(),
        ];

        return $this->sendResponse($payload, 'ดำเนินการเสร็จสิ้น');
    }
}

