<?php

namespace Gametech\Lotto\Observers;

use App\Events\LottoDrawClosed;
use Gametech\Lotto\Models\LottoDraw;
use Illuminate\Support\Facades\DB;

class LottoDrawRealtimeObserver
{
    public function updated(LottoDraw $draw): void
    {
        if (! $draw->wasChanged('status')) {
            return;
        }

        $fromStatus = (string) $draw->getOriginal('status');
        $toStatus = (string) $draw->status;

        if ($fromStatus === $toStatus || $toStatus !== 'closed') {
            return;
        }

        $draw->loadMissing('market:id,name');

        $marketName = (string) ($draw->market->name ?? '-');
        $drawDate = $draw->draw_date ? $draw->draw_date->format('Y-m-d') : '-';
        DB::afterCommit(function () use ($draw, $marketName, $drawDate): void {
            broadcast(new LottoDrawClosed((int) $draw->id, $marketName, $drawDate));
        });
    }
}
