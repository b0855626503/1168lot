<?php

namespace Gametech\Lotto\Observers;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Services\AutoResultHardeningService;

class LottoDrawAutoResultObserver
{
    public function updated(LottoDraw $draw): void
    {
        if (! $draw->wasChanged('result_fetch_status')) {
            return;
        }

        app(AutoResultHardeningService::class)->handleExhaustedTransition($draw);
    }
}
