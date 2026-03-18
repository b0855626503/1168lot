<?php

namespace Gametech\Payment\Observers;

use App\Services\Dashboard\DashboardSummarySyncService;
use Gametech\Payment\Models\Bill as EventData;
use Illuminate\Support\Facades\DB;

class BillObserver
{
    public function created(EventData $data): void
    {
        if (!$this->isBonusRelevant($data)) {
            return;
        }

        DB::afterCommit(function () use ($data) {
            $this->dispatchDashboardSync($data);
        });
    }

    public function updated(EventData $data): void
    {
        if (!$this->isBonusRelevant($data)) {
            return;
        }

        DB::afterCommit(function () use ($data) {
            $this->dispatchDashboardSync($data);
        });
    }

    public function deleted(EventData $data): void
    {
        if (!$this->isBonusRelevant($data)) {
            return;
        }

        DB::afterCommit(function () use ($data) {
            $this->dispatchDashboardSync($data);
        });
    }

    private function isBonusRelevant(EventData $data): bool
    {
        $currentProCode = (int) ($data->pro_code ?? 0);
        $originalProCode = (int) ($data->getOriginal('pro_code') ?? 0);

        if ($currentProCode > 0 || $originalProCode > 0) {
            return true;
        }

        $currentBonus = (float) ($data->credit_bonus ?? 0);
        $originalBonus = (float) ($data->getOriginal('credit_bonus') ?? 0);

        return abs($currentBonus) > 0 || abs($originalBonus) > 0;
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
