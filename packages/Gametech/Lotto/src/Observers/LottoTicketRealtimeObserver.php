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

        DB::afterCommit(function () use ($total, $marketName, $drawDate): void {
            broadcast(new LottoTicketListChanged('created', $total, $marketName, $drawDate));
            broadcast(new RealtimePublicActivityUpdated(
                'lotto',
                'lotto.ticket.list.changed',
                [
                    'action' => 'created',
                    'total' => $total,
                    'market_name' => $marketName,
                    'draw_date' => $drawDate,
                ]
            ));
        });
    }

    public function updated(LottoTicket $ticket): void
    {
        if (! $ticket->wasChanged('status')) {
            return;
        }

        $toStatus = (string) $ticket->status;
        $fromStatus = (string) $ticket->getOriginal('status');

        if (!in_array($toStatus, ['cancelled', 'resulted'], true)) {
            return;
        }

        if ($toStatus === $fromStatus) {
            return;
        }

        $total = $this->resolveTotalTickets();
        $action = $toStatus === 'cancelled' ? 'cancelled' : 'resulted';
        [$marketName, $drawDate] = $this->resolveDrawContext($ticket);

        DB::afterCommit(function () use ($total, $action, $marketName, $drawDate): void {
            broadcast(new LottoTicketListChanged($action, $total, $marketName, $drawDate));
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
        });
    }

    private function resolveTotalTickets(): int
    {
        return (int) LottoTicket::query()->count();
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
