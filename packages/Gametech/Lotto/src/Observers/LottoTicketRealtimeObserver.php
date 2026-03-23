<?php

namespace Gametech\Lotto\Observers;

use App\Events\LottoTicketListChanged;
use Gametech\Lotto\Models\LottoTicket;
use Illuminate\Support\Facades\DB;

class LottoTicketRealtimeObserver
{
    public function created(LottoTicket $ticket): void
    {
        $total = $this->resolveTotalTickets();

        DB::afterCommit(function () use ($total): void {
            broadcast(new LottoTicketListChanged('created', $total));
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

        DB::afterCommit(function () use ($total, $action): void {
            broadcast(new LottoTicketListChanged($action, $total));
        });
    }

    private function resolveTotalTickets(): int
    {
        return (int) LottoTicket::query()->count();
    }
}
