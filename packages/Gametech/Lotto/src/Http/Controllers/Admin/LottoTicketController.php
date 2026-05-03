<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoTicketDataTable;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoNumberExposure;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Services\WalletTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
                        'text' => 'งวด #'.(int) $draw->id.' - '.($draw->draw_date ? $draw->draw_date->format('d/m/Y') : '-'),
                    ];
                })->values()->all();
            })
            ->toArray();

        return $dataTable->render($this->_config['view'], [
            'marketOptions' => $marketOptions,
            'drawOptionsByMarket' => $drawOptionsByMarket,
            'loadDataRouteName' => $this->_config['load_data_route'] ?? 'admin.lotto.tickets.loaddata',
        ]);
    }

    public function loadData(Request $request): JsonResponse
    {
        $hasYeekeeTable = Schema::hasTable('yeekee_rounds');
        $withRelations = ['member', 'draw.market', 'items'];
        if ($hasYeekeeTable) {
            $withRelations[] = 'draw.yeekeeRound';
        }

        $ticket = LottoTicket::query()
            ->with($withRelations)
            ->find((int) $request->input('id'));

        if (! $ticket) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $cancellationInfo = $this->resolveCancellationInfo($ticket);

        $isYeekee = (string) ($ticket->draw?->market?->result_mode ?? '') === LotteryMarket::RESULT_MODE_YEEKEE;
        $roundNo = ($hasYeekeeTable && $ticket->draw?->yeekeeRound)
            ? (int) $ticket->draw->yeekeeRound->round_no
            : null;

        $payload = [
            'id' => (int) $ticket->id,
            'member_id' => (int) $ticket->member_id,
            'member_name' => $ticket->member->user_name ?? $ticket->member->name ?? ('MEM-'.$ticket->member_id),
            'draw' => [
                'id' => (int) $ticket->draw_id,
                'date' => optional($ticket->draw?->draw_date)->format('d/m/Y'),
                'market' => $ticket->draw?->market?->name,
                'is_yeekee' => $isYeekee,
                'round_no' => $roundNo,
            ],
            'status' => (string) $ticket->status,
            'can_cancel' => (string) $ticket->status === 'active' && (string) ($ticket->draw->status ?? '') === 'open',
            'total_amount' => (float) ($ticket->total_bet_amount ?? 0),
            'total_bet_amount' => (float) ($ticket->total_bet_amount ?? 0),
            'total_discount_amount' => (float) ($ticket->total_discount_amount ?? 0),
            'total_net_amount' => (float) ($ticket->total_net_amount ?? 0),
            'total_win_amount' => (float) ($ticket->total_win_amount ?? 0),
            'refund_amount' => (float) ($ticket->refund_amount ?? 0),
            'reason' => $this->resolveTicketReason($ticket),
            'cancelled_at' => optional($ticket->cancelled_at)->format('d/m/Y H:i:s'),
            'cancelled_by_name' => $cancellationInfo['name'],
            'cancelled_by_type' => $cancellationInfo['type'],
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

    public function cancel(Request $request, int $id, WalletTransactionService $walletTransactionService): JsonResponse
    {
        $reason = trim((string) $request->input('reason', ''));
        if ($reason === '') {
            return $this->sendError('กรุณาระบุสาเหตุการยกเลิกโพย', 422);
        }

        if (mb_strlen($reason) > 1000) {
            return $this->sendError('สาเหตุการยกเลิกโพยยาวเกิน 1000 ตัวอักษร', 422);
        }

        $adminId = auth('admin')->id();
        $groupCode = 'LOTTO_ADMIN_CANCEL_'.$id.'_'.now()->format('YmdHis');

        try {
            $payload = DB::transaction(function () use ($id, $reason, $walletTransactionService, $groupCode, $adminId): array {
                /** @var LottoTicket $ticket */
                $ticket = LottoTicket::query()
                    ->with(['draw', 'items', 'member'])
                    ->lockForUpdate()
                    ->findOrFail($id);

                if ((string) $ticket->status !== 'active') {
                    throw new \RuntimeException('โพยนี้ไม่สามารถยกเลิกได้');
                }

                if ((string) ($ticket->draw->status ?? '') !== 'open') {
                    throw new \RuntimeException('ยกเลิกได้เฉพาะโพยที่อยู่ในงวดเปิดรับเท่านั้น');
                }

                foreach ($ticket->items as $item) {
                    $exposure = LottoNumberExposure::query()
                        ->where('draw_id', (int) $ticket->draw_id)
                        ->where('bet_type', (string) $item->bet_type)
                        ->where('number', (string) $item->number)
                        ->lockForUpdate()
                        ->first();

                    if (! $exposure) {
                        continue;
                    }

                    $nextAmount = max(0, round((float) $exposure->sold_amount - (float) $item->amount, 2));
                    $exposure->update(['sold_amount' => $nextAmount]);
                }

                $debitTxn = DB::table('wallet_transactions')
                    ->where('member_id', (int) $ticket->member_id)
                    ->where('ref_type', 'LOTTO_BET')
                    ->where('ref_id', (int) $ticket->id)
                    ->orderByDesc('id')
                    ->first(['id']);

                $refundAmount = round((float) ($ticket->total_net_amount ?? $ticket->total_amount ?? 0), 2);
                if ($refundAmount > 0) {
                    $walletTransactionService->creditMemberBalance(
                        memberId: (int) $ticket->member_id,
                        amount: $refundAmount,
                        refType: 'LOTTO_CANCEL',
                        refId: (int) $ticket->id,
                        refCode: (string) $ticket->id,
                        groupCode: $groupCode,
                        relatedTxnId: isset($debitTxn->id) ? (int) $debitTxn->id : null,
                        meta: [
                            'draw_id' => (int) $ticket->draw_id,
                            'ticket_id' => (int) $ticket->id,
                            'cancel_scope' => 'ticket',
                            'reason' => $reason,
                        ],
                        createdByType: 'admin',
                        createdById: $adminId ? (int) $adminId : null,
                        description: 'คืนเงินจากการยกเลิกโพยหวยโดยทีมงาน'
                    );
                }

                $updatePayload = [
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => $adminId ? (int) $adminId : null,
                    'refund_amount' => $refundAmount,
                    'total_win_amount' => 0,
                    'reason' => $reason,
                ];

                $ticket->update($updatePayload);

                $memberDisplay = trim((string) ($ticket->member->user_name ?? $ticket->member->name ?? ''));

                return [
                    'ticket_id' => (int) $ticket->id,
                    'member_name' => $memberDisplay !== '' ? $memberDisplay : ('MEM-'.(int) $ticket->member_id),
                    'refund_amount' => $refundAmount,
                    'reason' => $reason,
                ];
            });

            return $this->sendResponse($payload, 'ยกเลิกโพยสำเร็จ');
        } catch (\RuntimeException $exception) {
            return $this->sendError($exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            return $this->sendError('ยกเลิกโพยไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 500);
        }
    }

    /**
     * @return array{name:string,type:string}
     */
    private function resolveCancellationInfo(LottoTicket $ticket): array
    {
        $cancelTxn = DB::table('wallet_transactions')
            ->where('ref_type', 'LOTTO_CANCEL')
            ->where('ref_id', (int) $ticket->id)
            ->orderByDesc('id')
            ->first(['created_by_type', 'created_by_id']);

        if ($cancelTxn && ! empty($cancelTxn->created_by_type) && ! empty($cancelTxn->created_by_id)) {
            $name = $this->resolveActorName((string) $cancelTxn->created_by_type, (int) $cancelTxn->created_by_id);
            if ($name !== '') {
                return [
                    'name' => $name,
                    'type' => (string) $cancelTxn->created_by_type,
                ];
            }
        }

        if (! empty($ticket->cancelled_by)) {
            $name = $this->resolveActorName('admin', (int) $ticket->cancelled_by);
            if ($name === '') {
                $name = $this->resolveActorName('member', (int) $ticket->cancelled_by);
            }

            if ($name !== '') {
                return [
                    'name' => $name,
                    'type' => 'unknown',
                ];
            }
        }

        return [
            'name' => '',
            'type' => '',
        ];
    }

    private function resolveActorName(string $actorType, int $actorId): string
    {
        if ($actorId <= 0) {
            return '';
        }

        return match ($actorType) {
            'admin' => trim((string) DB::table('employees')->where('code', $actorId)->value('user_name')),
            'member' => trim((string) (DB::table('members')->where('code', $actorId)->value('user_name')
                ?: DB::table('members')->where('code', $actorId)->value('name'))),
            default => '',
        };
    }

    private function resolveTicketReason(LottoTicket $ticket): string
    {
        $ticketReason = trim((string) ($ticket->reason ?? ''));
        if ($ticketReason !== '') {
            return $ticketReason;
        }

        $cancelTxnMeta = DB::table('wallet_transactions')
            ->where('ref_type', 'LOTTO_CANCEL')
            ->where('ref_id', (int) $ticket->id)
            ->orderByDesc('id')
            ->value('meta');

        if (! is_string($cancelTxnMeta) || trim($cancelTxnMeta) === '') {
            return '';
        }

        $decoded = json_decode($cancelTxnMeta, true);

        return is_array($decoded) ? trim((string) ($decoded['reason'] ?? '')) : '';
    }
}
