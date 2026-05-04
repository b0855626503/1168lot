<?php

namespace Gametech\Lotto\Observers;

use App\Events\LottoTicketListChanged;
use App\Events\RealtimePublicActivityUpdated;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Support\LottoMarketDisplayFormatter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LottoTicketRealtimeObserver
{
    public function created(LottoTicket $ticket): void
    {
        $total = $this->resolveTotalTickets();
        [$marketName, $drawDate, $resultMode, $roundNo] = $this->resolveDrawContext($ticket);
        [$ownerId, $amount] = $this->resolveTicketRealtimeContext($ticket);

        $this->afterCommit(function () use ($total, $marketName, $drawDate, $ownerId, $amount, $resultMode, $roundNo): void {
            $this->broadcastTicketListChanged('created', $total, $marketName, $drawDate, null, $ownerId, $amount, $resultMode, $roundNo);
        });
    }

    public function updated(LottoTicket $ticket): void
    {
        if (! $ticket->wasChanged('status')) {
            return;
        }

        $toStatus = (string) $ticket->status;
        $fromStatus = (string) $ticket->getOriginal('status');

        if ($toStatus !== 'cancelled') {
            return;
        }

        if ($toStatus === $fromStatus) {
            return;
        }

        $total = $this->resolveTotalTickets();
        $action = 'cancelled';
        [$marketName, $drawDate, $resultMode, $roundNo] = $this->resolveDrawContext($ticket);
        [$ownerId, $actorId] = $this->resolveCancelledRealtimeContext($ticket);

        $this->afterCommit(function () use ($total, $action, $marketName, $drawDate, $ownerId, $actorId, $resultMode, $roundNo): void {
            $this->broadcastTicketListChanged($action, $total, $marketName, $drawDate, $ownerId, $actorId, null, $resultMode, $roundNo);
        });
    }

    protected function resolveTotalTickets(): int
    {
        return (int) LottoTicket::query()
            ->where('status', 'active')
            ->count();
    }

    protected function afterCommit(callable $callback): void
    {
        DB::afterCommit($callback);
    }

    protected function broadcastTicketListChanged(
        string $action,
        int $total,
        ?string $marketName,
        ?string $drawDate,
        ?string $ownerId,
        ?string $actorId,
        ?float $amount,
        ?string $resultMode = null,
        ?int $roundNo = null
    ): void {
        broadcast(new LottoTicketListChanged($action, $total, $marketName, $drawDate, $ownerId, $actorId, $amount, $resultMode, $roundNo));
    }

    protected function broadcastPublicActivityUpdated(
        string $action,
        int $total,
        ?string $marketName,
        ?string $drawDate,
        ?string $ownerId,
        ?string $actorId,
        ?float $amount,
        ?string $resultMode = null,
        ?int $roundNo = null
    ): void {
        broadcast(new RealtimePublicActivityUpdated(
            'lotto',
            'lotto.ticket.list.changed',
            [
                'action' => $action,
                'total' => $total,
                'market_name' => $marketName,
                'draw_date' => $drawDate,
                'owner_id' => $ownerId,
                'actor_id' => $actorId,
                'amount' => $amount,
                'result_mode' => $resultMode,
                'round_no' => $roundNo,
            ],
            $this->buildPublicActivityMessage($action, $marketName, $drawDate, $ownerId, $actorId, $amount, $resultMode, $roundNo)
        ));
    }

    private function buildPublicActivityMessage(
        string $action,
        ?string $marketName,
        ?string $drawDate,
        ?string $ownerId,
        ?string $actorId,
        ?float $amount,
        ?string $resultMode = null,
        ?int $roundNo = null
    ): string {
        $formatter = new LottoMarketDisplayFormatter;
        $subject = $formatter->formatDrawSubject((string) $marketName, (string) $drawDate, $resultMode, $roundNo, true);

        if ($action === 'created') {
            $message = "มีรายการโพยหวยใหม่: {$subject}";

            if ($actorId !== null && $actorId !== '') {
                $message .= " โดย {$actorId}";
            }

            if ($amount !== null) {
                $message .= ' จำนวน '.number_format($amount, 2, '.', '');
            }

            return $message;
        }

        if ($action === 'cancelled') {
            $message = "มีการคืนโพยหวย: {$subject}";

            if ($ownerId !== null && $ownerId !== '') {
                $message .= " ของ {$ownerId}";
            }

            if ($actorId !== null && $actorId !== '') {
                $message .= " โดย {$actorId}";
            }

            return $message;
        }

        if ($action === 'resulted') {
            return $formatter->formatStatusMessage((string) $marketName, (string) $drawDate, 'อัปเดตรายการโพยหลังออกผลแล้ว', $resultMode, $roundNo, true);
        }

        return "มีการอัปเดตรายการโพยหวย: {$subject}";
    }

    /**
     * @return array{0:?string,1:?string,2:?string,3:?int}
     */
    private function resolveDrawContext(LottoTicket $ticket): array
    {
        $ticket->loadMissing(['draw.market']);

        $marketName = trim((string) data_get($ticket, 'draw.market.name', ''));
        $drawDate = $ticket->draw?->draw_date?->format('Y-m-d');
        $resultMode = (string) data_get($ticket, 'draw.market.result_mode', '');

        if ($resultMode === \Gametech\Lotto\Models\LotteryMarket::RESULT_MODE_YEEKEE) {
            $ticket->loadMissing(['draw.yeekeeRound']);
        }

        $roundNo = null;
        if ($resultMode === \Gametech\Lotto\Models\LotteryMarket::RESULT_MODE_YEEKEE) {
            $roundNo = (int) data_get($ticket, 'draw.yeekeeRound.round_no', 0);
            if ($roundNo <= 0) {
                \Illuminate\Support\Facades\Log::warning('yeekee draw missing round_no in ticket broadcast', [
                    'ticket_id' => (int) $ticket->id,
                    'draw_id' => (int) $ticket->lotto_draw_id
                ]);
                $roundNo = null;
            }
        }

        return [
            $marketName !== '' ? $marketName : null,
            $drawDate !== '' ? $drawDate : null,
            $resultMode !== '' ? $resultMode : null,
            $roundNo,
        ];
    }

    /**
     * @return array{0:?string,1:?float}
     */
    private function resolveTicketRealtimeContext(LottoTicket $ticket): array
    {
        $ticket->loadMissing('member');

        $actorId = trim((string) (
            $ticket->member->user_name
            ?? $ticket->member->tel
            ?? $ticket->member->name
            ?? $ticket->member_id
            ?? ''
        ));

        $amount = $ticket->total_amount
            ?? $ticket->total_bet_amount
            ?? $ticket->total_net_amount;

        return [
            $actorId !== '' ? $actorId : null,
            $amount !== null ? (float) $amount : null,
        ];
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function resolveCancelledRealtimeContext(LottoTicket $ticket): array
    {
        $ticket->loadMissing('member');

        $ownerId = trim((string) (
            $ticket->member->user_name
            ?? $ticket->member->tel
            ?? $ticket->member->name
            ?? $ticket->member_id
            ?? ''
        ));

        $actorId = $this->resolveCancellationActorId($ticket);

        return [
            $ownerId !== '' ? $ownerId : null,
            $actorId !== '' ? $actorId : null,
        ];
    }

    private function resolveCancellationActorId(LottoTicket $ticket): string
    {
        if (Schema::hasTable('wallet_transactions')) {
            $cancelTxn = DB::table('wallet_transactions')
                ->where('ref_type', 'LOTTO_CANCEL')
                ->where('ref_id', (int) $ticket->id)
                ->orderByDesc('id')
                ->first(['created_by_type', 'created_by_id']);

            if ($cancelTxn) {
                $resolved = $this->resolveActorName(
                    (string) ($cancelTxn->created_by_type ?? ''),
                    (int) ($cancelTxn->created_by_id ?? 0)
                );

                if ($resolved !== '') {
                    return $resolved;
                }
            }
        }

        $cancelledBy = (int) ($ticket->cancelled_by ?? 0);
        if ($cancelledBy <= 0) {
            return '';
        }

        $adminName = $this->resolveActorName('admin', $cancelledBy);
        if ($adminName !== '') {
            return $adminName;
        }

        return $this->resolveActorName('member', $cancelledBy);
    }

    private function resolveActorName(string $actorType, int $actorId): string
    {
        if ($actorId <= 0) {
            return '';
        }

        return match ($actorType) {
            'admin' => Schema::hasTable('employees')
                ? trim((string) (DB::table('employees')->where('code', $actorId)->value('user_name')
                    ?: DB::table('employees')->where('code', $actorId)->value('name')))
                : '',
            'member' => Schema::hasTable('members')
                ? trim((string) (DB::table('members')->where('code', $actorId)->value('user_name')
                    ?: DB::table('members')->where('code', $actorId)->value('name')))
                : '',
            default => '',
        };
    }
}
