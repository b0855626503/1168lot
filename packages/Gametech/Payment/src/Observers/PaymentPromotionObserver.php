<?php

namespace Gametech\Payment\Observers;

use App\Services\Dashboard\DashboardSummarySyncService;
use Gametech\Payment\Models\PaymentPromotion as EventData;
use Illuminate\Support\Facades\DB;

class PaymentPromotionObserver
{
    public function created(EventData $data): void
    {
        DB::afterCommit(function () use ($data) {
            $this->dispatchDashboardSync($data);
        });
    }

    public function updated(EventData $data): void
    {
        DB::afterCommit(function () use ($data) {
            $this->dispatchDashboardSync($data);
        });
    }

    public function deleted(EventData $data): void
    {
        DB::afterCommit(function () use ($data) {
            $this->dispatchDashboardSync($data);
        });
    }

    private function dispatchDashboardSync(EventData $data): void
    {
        try {
            app(DashboardSummarySyncService::class)->dispatchForModelChange('bonus', $data, ['bonus']);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
