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

        $this->afterCommit(function () use ($total, $marketName, $drawDate): void {
            $this->broadcastTicketListChanged('created', $total, $marketName, $drawDate);
            $this->broadcastPublicActivityUpdated('created', $total, $marketName, $drawDate);
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
            $this->broadcastTicketListChanged($action, $total, $marketName, $drawDate);
            $this->broadcastPublicActivityUpdated($action, $total, $marketName, $drawDate);
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

    protected function broadcastTicketListChanged(string $action, int $total, ?string $marketName, ?string $drawDate): void
    {
        broadcast(new LottoTicketListChanged($action, $total, $marketName, $drawDate));
    }

    protected function broadcastPublicActivityUpdated(string $action, int $total, ?string $marketName, ?string $drawDate): void
    {
        broadcast(new RealtimePublicActivityUpdated(
            'lotto',
            'lotto.ticket.list.changed',
            [
                'action' => $action,
                'total' => $total,
                'market_name' => $marketName,
                'draw_date' => $drawDate,
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
}
