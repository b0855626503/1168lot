<?php

namespace Gametech\Lotto\Observers;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Services\AutoResultHardeningService;
use Gametech\Lotto\Services\Relay\LotteryRelayPublisher;

class LottoDrawAutoResultObserver
{
    public function updated(LottoDraw $draw): void
    {
        if ($draw->wasChanged('result_fetch_status')) {
            app(AutoResultHardeningService::class)->handleExhaustedTransition($draw);
        }

        if ($draw->wasChanged('result_fetch_status') || $draw->wasChanged('result_hash')) {
            $draw->loadMissing('market:id,code');
            app(LotteryRelayPublisher::class)->publishIfReady($draw);
        }
    }
}
