<?php

namespace Gametech\Lotto\Http\Controllers\Api;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoNumberExposure;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Services\WalletTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TicketController extends AppBaseController
{
    private const DAILY_CANCEL_LIMIT = 4;
    private const CANCEL_CUTOFF_MINUTES = 10;

    public function index(Request $request): JsonResponse
    {
        $memberId = $this->resolveMemberId($request);
        if (! $memberId) {
            return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
        }

        $tickets = $this->ticketQuery($memberId)
            ->orderByDesc('id')
            ->limit($this->resolveLimit($request))
            ->get();

        $payload = $tickets->map(fn (LottoTicket $ticket) => $this->mapTicketSummary($ticket))->values();

        return $this->sendResponse($payload, 'ดึงประวัติโพยสำเร็จ');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $memberId = $this->resolveMemberId($request);
        if (! $memberId) {
            return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
        }

        $ticket = $this->ticketQuery($memberId)
            ->find($id);

        if (! $ticket) {
            return $this->sendError('ไม่พบโพยที่ระบุ', 404);
        }

        $payload = $this->mapTicketSummary($ticket);
        $payload['items'] = $ticket->items->map(static function ($item): array {
            $rawStatus = $item->result_status;
            $itemResultStatus = in_array($rawStatus, ['win', 'lose'], true)
                ? (string) $rawStatus
                : 'pending';

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
                'result_status' => $itemResultStatus,
                'raw_result_status' => $rawStatus,
                'is_winner' => $itemResultStatus === 'win',
                'win_amount' => (float) ($item->win_amount ?? 0),
            ];
        })->values();

        return $this->sendResponse($payload, 'ดึงรายละเอียดโพยสำเร็จ');
    }

    public function cancel(Request $request, int $id, WalletTransactionService $walletTransactionService): JsonResponse
    {
        $memberId = $this->resolveMemberId($request);
        if (! $memberId) {
            return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
        }

        try {
            DB::transaction(function () use ($id, $memberId, $walletTransactionService): void {
                $ticket = $this->ticketQuery($memberId)
                    ->with(['draw', 'items'])
                    ->lockForUpdate()
                    ->findOrFail($id);

                if ((string) $ticket->status !== 'active') {
                    throw new \RuntimeException('โพยนี้ไม่สามารถยกเลิกได้');
                }

                if ((string) ($ticket->draw->status ?? '') !== 'open') {
                    throw new \RuntimeException('ยกเลิกได้เฉพาะโพยที่อยู่ในงวดเปิดรับเท่านั้น');
                }

                $this->guardCancelWindow($ticket);
                $this->guardDailyCancelLimit($memberId);

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

                    $nextAmount = max(0, (float) $exposure->sold_amount - (float) $item->amount);
                    $exposure->update(['sold_amount' => $nextAmount]);
                }

                $debitTxn = DB::table('wallet_transactions')
                    ->where('member_id', $memberId)
                    ->where('ref_type', 'LOTTO_BET')
                    ->where('ref_id', (int) $ticket->id)
                    ->orderByDesc('id')
                    ->first(['id']);

                $groupCode = 'LOTTO_CANCEL_' . $ticket->id . '_' . now()->format('YmdHis');
                $refundAmount = (float) ($ticket->total_net_amount ?? $ticket->total_amount ?? 0);
                $walletTransactionService->creditMemberBalance(
                    memberId: $memberId,
                    amount: $refundAmount,
                    refType: 'LOTTO_CANCEL',
                    refId: (int) $ticket->id,
                    refCode: (string) $ticket->id,
                    groupCode: $groupCode,
                    relatedTxnId: isset($debitTxn->id) ? (int) $debitTxn->id : null,
                    meta: [
                        'draw_id' => (int) $ticket->draw_id,
                        'ticket_id' => (int) $ticket->id,
                    ],
                    createdByType: 'member',
                    createdById: $memberId,
                    description: 'คืนเงินจากการยกเลิกโพยหวย'
                );

                $updatePayload = [
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'refund_amount' => $refundAmount,
                ];

                if (Schema::hasColumn('lotto_tickets', 'cancelled_by')) {
                    $updatePayload['cancelled_by'] = $memberId;
                }

                $ticket->update($updatePayload);
            });

            return $this->sendSuccess('ยกเลิกโพยสำเร็จ');
        } catch (\RuntimeException $exception) {
            return $this->sendError($exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            return $this->sendError('ยกเลิกโพยไม่สำเร็จ', 500);
        }
    }

    private function resolveMemberId(Request $request): ?int
    {
        $member = $request->user('customer');

        if (! $member || ! isset($member->code)) {
            return null;
        }

        return (int) $member->code;
    }

    private function resolveLimit(Request $request): int
    {
        return max(1, min((int) $request->input('limit', 20), 100));
    }

    private function ticketQuery(int $memberId)
    {
        return LottoTicket::query()
            ->with(['draw.market', 'items'])
            ->where('member_id', $memberId);
    }

    private function mapTicketSummary(LottoTicket $ticket): array
    {
        $resultContext = $this->ticketResultContext($ticket);
        $itemSummary = $this->ticketItemSummary($ticket);
        $cancellationInfo = $this->resolveCancellationInfo($ticket);

        return [
            'id' => (int) $ticket->id,
            'draw_id' => (int) $ticket->draw_id,
            'draw_date' => optional($ticket->draw?->draw_date)->toDateString(),
            'market_name' => $ticket->draw?->market?->name,
            'status' => (string) $ticket->status,
            'draw_status' => (string) ($ticket->draw?->status ?? ''),
            'draw_result_at' => optional($ticket->draw?->result_at)->toDateTimeString(),
            'total_amount' => (float) ($ticket->total_net_amount ?? $ticket->total_amount ?? 0),
            'total_bet_amount' => (float) ($ticket->total_bet_amount ?? 0),
            'total_discount_amount' => (float) ($ticket->total_discount_amount ?? 0),
            'total_net_amount' => (float) ($ticket->total_net_amount ?? $ticket->total_amount ?? 0),
            'total_win_amount' => (float) ($ticket->total_win_amount ?? 0),
            'refund_amount' => (float) ($ticket->refund_amount ?? 0),
            'cancelled_at' => optional($ticket->cancelled_at)->toDateTimeString(),
            'cancelled_by_name' => $cancellationInfo['name'],
            'cancelled_by_type' => $cancellationInfo['type'],
            'cancel_reason' => $this->resolveTicketReason($ticket),
            'result_outcome' => $resultContext['result_outcome'],
            'is_final' => $resultContext['is_final'],
            'is_winner' => $resultContext['is_winner'],
            'item_count' => $itemSummary['item_count'],
            'winning_item_count' => $itemSummary['winning_item_count'],
            'losing_item_count' => $itemSummary['losing_item_count'],
            'pending_item_count' => $itemSummary['pending_item_count'],
            'created_at' => optional($ticket->created_at)->toDateTimeString(),
        ];
    }

    /**
     * @return array{result_outcome:string,is_final:bool,is_winner:bool}
     */
    private function ticketResultContext(LottoTicket $ticket): array
    {
        $draw = $ticket->draw;
        $drawStatus = (string) ($draw?->status ?? '');
        $resultNumber = is_array($draw?->result_number) ? $draw->result_number : [];
        $ticketStatus = (string) $ticket->status;
        $isNoResult = (bool) ($resultNumber['no_result'] ?? false)
            || (string) ($resultNumber['status'] ?? '') === 'no_result';
        $isRefunded = (bool) ($resultNumber['manual_cancelled_all_tickets'] ?? false);
        $isWinner = (float) ($ticket->total_win_amount ?? 0) > 0;

        $resultOutcome = match (true) {
            $ticketStatus === 'cancelled' => 'cancelled',
            $isRefunded => 'refunded',
            $isNoResult => 'no_result',
            $ticketStatus === 'resulted' || $drawStatus === 'resulted' => $isWinner ? 'won' : 'lose',
            $drawStatus === 'open' => 'betting_open',
            default => 'pending_result',
        };

        return [
            'result_outcome' => $resultOutcome,
            'is_final' => in_array($resultOutcome, ['won', 'lose', 'cancelled', 'no_result', 'refunded'], true),
            'is_winner' => $resultOutcome === 'won',
        ];
    }

    /**
     * @return array{item_count:int,winning_item_count:int,losing_item_count:int,pending_item_count:int}
     */
    private function ticketItemSummary(LottoTicket $ticket): array
    {
        $items = $ticket->items ?? collect();
        $winningCount = (int) $items->where('result_status', 'win')->count();
        $losingCount = (int) $items->where('result_status', 'lose')->count();
        $itemCount = (int) $items->count();

        return [
            'item_count' => $itemCount,
            'winning_item_count' => $winningCount,
            'losing_item_count' => $losingCount,
            'pending_item_count' => max(0, $itemCount - $winningCount - $losingCount),
        ];
    }

    private function guardCancelWindow(LottoTicket $ticket): void
    {
        $closeAt = $ticket->draw?->close_at;

        if (! $closeAt instanceof Carbon) {
            throw new \RuntimeException('โพยนี้ไม่สามารถยกเลิกได้ เพราะไม่พบเวลาปิดรับ');
        }

        $cutoffAt = $closeAt->copy()->subMinutes(self::CANCEL_CUTOFF_MINUTES);
        if (! now()->lt($cutoffAt)) {
            throw new \RuntimeException('ยกเลิกโพยได้ก่อนเวลาปิดรับอย่างน้อย 10 นาทีเท่านั้น');
        }
    }

    private function guardDailyCancelLimit(int $memberId): void
    {
        $today = now()->toDateString();

        $dailyCancelledCount = DB::table('wallet_transactions')
            ->where('member_id', $memberId)
            ->where('ref_type', 'LOTTO_CANCEL')
            ->where('created_by_type', 'member')
            ->where('created_by_id', $memberId)
            ->whereDate('created_at', $today)
            ->count();

        if ($dailyCancelledCount >= self::DAILY_CANCEL_LIMIT) {
            throw new \RuntimeException('สมาชิกยกเลิกโพยได้ไม่เกินวันละ 4 ครั้ง');
        }
    }

    /**
     * @return array{name:string,type:string}
     */
    private function resolveCancellationInfo(LottoTicket $ticket): array
    {
        if (Schema::hasTable('wallet_transactions')) {
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
        }

        if (! empty($ticket->cancelled_by)) {
            $name = $this->resolveActorName('member', (int) $ticket->cancelled_by);
            if ($name !== '') {
                return [
                    'name' => $name,
                    'type' => 'member',
                ];
            }

            $name = $this->resolveActorName('admin', (int) $ticket->cancelled_by);
            if ($name !== '') {
                return [
                    'name' => $name,
                    'type' => 'admin',
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
            'admin' => Schema::hasTable('employees')
                ? trim((string) DB::table('employees')->where('code', $actorId)->value('user_name'))
                : '',
            'member' => Schema::hasTable('members')
                ? trim((string) (DB::table('members')->where('code', $actorId)->value('user_name')
                    ?: DB::table('members')->where('code', $actorId)->value('name')))
                : '',
            default => '',
        };
    }

    private function resolveTicketReason(LottoTicket $ticket): string
    {
        if ($this->hasTicketReasonColumn()) {
            return trim((string) ($ticket->reason ?? ''));
        }

        if (! Schema::hasTable('wallet_transactions')) {
            return '';
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

    private function hasTicketReasonColumn(): bool
    {
        return Schema::hasColumn('lotto_tickets', 'reason');
    }
}
