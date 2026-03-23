<?php

namespace Gametech\Lotto\Observers;

use App\Services\Dashboard\DashboardSummarySyncService;
use App\Services\Dashboard\LottoDashboardMetricConfig;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoNumberExposure;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LottoTicketItem;
use Illuminate\Support\Facades\DB;

class LottoDashboardSummaryObserver
{
    public function created($model): void
    {
        $this->dispatchFromModel($model, 'created');
    }

    public function updated($model): void
    {
        $this->dispatchFromModel($model, 'updated');
    }

    public function deleted($model): void
    {
        $this->dispatchFromModel($model, 'deleted');
    }

    private function dispatchFromModel($model, string $action): void
    {
        $sections = [];
        $payload = [
            'id' => (string) ($model->getKey() ?? ''),
        ];

        if ($model instanceof LottoTicket) {
            $payload['product_dates'] = array_filter([
                $model->created_at,
                $model->getOriginal('created_at'),
            ]);
            $payload['insight_dates'] = array_filter([
                $model->bet_confirmed_at,
                $model->getOriginal('bet_confirmed_at'),
            ]);
            $payload['risk_dates'] = array_filter([
                $model->updated_at,
                $model->getOriginal('updated_at'),
            ]);
            $sections = [
                LottoDashboardMetricConfig::SECTION_PRODUCT,
                LottoDashboardMetricConfig::SECTION_RISK,
                LottoDashboardMetricConfig::SECTION_BET_TYPE_INSIGHTS,
            ];
        } elseif ($model instanceof LottoTicketItem) {
            $ticket = $model->relationLoaded('ticket')
                ? $model->ticket
                : LottoTicket::query()->find($model->ticket_id);

            $payload['insight_dates'] = array_filter([
                optional($ticket)->bet_confirmed_at,
                optional($ticket)->getOriginal('bet_confirmed_at'),
            ]);
            $payload['risk_dates'] = array_filter([
                $model->updated_at,
                $model->getOriginal('updated_at'),
            ]);
            $sections = [
                LottoDashboardMetricConfig::SECTION_RISK,
                LottoDashboardMetricConfig::SECTION_BET_TYPE_INSIGHTS,
            ];
        } elseif ($model instanceof LottoNumberExposure) {
            if ($action === 'updated' && !$model->wasChanged('sold_amount')) {
                return;
            }

            $payload['risk_dates'] = array_filter([
                $model->updated_at,
                $model->getOriginal('updated_at'),
            ]);
            $sections = [LottoDashboardMetricConfig::SECTION_RISK];
        } elseif ($model instanceof LottoDraw) {
            if ($action === 'updated' && !$model->wasChanged(['status', 'result_at', 'result_number'])) {
                return;
            }

            $payload['operation_dates'] = array_filter([
                $model->updated_at,
                $model->getOriginal('updated_at'),
                $model->result_at,
                $model->getOriginal('result_at'),
            ]);
            $payload['product_dates'] = array_filter([
                $model->result_at,
                $model->getOriginal('result_at'),
            ]);
            $sections = [
                LottoDashboardMetricConfig::SECTION_OPERATIONS,
                LottoDashboardMetricConfig::SECTION_PRODUCT,
                LottoDashboardMetricConfig::SECTION_RISK,
            ];
        } else {
            return;
        }

        DB::afterCommit(function () use ($payload, $sections): void {
            app(DashboardSummarySyncService::class)->dispatchForModelChange('lotto', $payload, $sections);
        });
    }
}
