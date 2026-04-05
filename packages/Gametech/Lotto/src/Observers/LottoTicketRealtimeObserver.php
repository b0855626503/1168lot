<?php

namespace Gametech\Lotto\Observers;

use App\Events\LottoTicketListChanged;
use App\Events\RealtimePublicActivityUpdated;
use Gametech\Lotto\Models\LottoTicket;
use Illuminate\Support\Facades\DB;

class LottoTicketRealtimeObserver
{
    public function created(LottoTicket $ticket): void
    {
        $total = $this->resolveTotalTickets();
        [$marketName, $drawDate] = $this->resolveDrawContext($ticket);
        [$actorId, $amount] = $this->resolveTicketRealtimeContext($ticket);

        $this->afterCommit(function () use ($total, $marketName, $drawDate, $actorId, $amount): void {
            $this->broadcastTicketListChanged('created', $total, $marketName, $drawDate, $actorId, $amount);
            $this->broadcastPublicActivityUpdated('created', $total, $marketName, $drawDate, $actorId, $amount);
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
        [$marketName, $drawDate] = $this->resolveDrawContext($ticket);

        $this->afterCommit(function () use ($total, $action, $marketName, $drawDate): void {
            $this->broadcastTicketListChanged($action, $total, $marketName, $drawDate, null, null);
            $this->broadcastPublicActivityUpdated($action, $total, $marketName, $drawDate, null, null);
        });
    }

    protected function resolveTotalTickets(): int
    {
        return (int) LottoTicket::query()->count();
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
        ?string $actorId,
        ?float $amount
    ): void
    {
        broadcast(new LottoTicketListChanged($action, $total, $marketName, $drawDate, $actorId, $amount));
    }

    protected function broadcastPublicActivityUpdated(
        string $action,
        int $total,
        ?string $marketName,
        ?string $drawDate,
        ?string $actorId,
        ?float $amount
    ): void
    {
        broadcast(new RealtimePublicActivityUpdated(
            'lotto',
            'lotto.ticket.list.changed',
            [
                'action' => $action,
                'total' => $total,
                'market_name' => $marketName,
                'draw_date' => $drawDate,
                'actor_id' => $actorId,
                'amount' => $amount,
            ]
        ));
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function resolveDrawContext(LottoTicket $ticket): array
    {
        $ticket->loadMissing('draw.market');

        $marketName = trim((string) data_get($ticket, 'draw.market.name', ''));
        $drawDate = $ticket->draw?->draw_date?->format('Y-m-d');

        return [
            $marketName !== '' ? $marketName : null,
            $drawDate !== '' ? $drawDate : null,
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
}
