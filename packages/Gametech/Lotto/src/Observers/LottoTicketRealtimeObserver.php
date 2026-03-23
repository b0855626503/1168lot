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

        if ((string) $ticket->status !== 'cancelled') {
            return;
        }

        if ((string) $ticket->getOriginal('status') === 'cancelled') {
            return;
        }

        $total = $this->resolveTotalTickets();

        DB::afterCommit(function () use ($total): void {
            broadcast(new LottoTicketListChanged('cancelled', $total));
        });
    }

    private function resolveTotalTickets(): int
    {
        return (int) LottoTicket::query()->count();
    }
}

