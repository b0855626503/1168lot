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
            if ($action === 'updated' && ! $model->wasChanged('sold_amount')) {
                return;
            }

            $payload['draw_id'] = (int) ($model->draw_id ?? 0);
            $payload['risk_dates'] = array_filter([
                $model->updated_at,
                $model->getOriginal('updated_at'),
            ]);
            $sections = [LottoDashboardMetricConfig::SECTION_RISK];
        } elseif ($model instanceof LottoDraw) {
            if ($action === 'updated' && ! $model->wasChanged(['status', 'result_at', 'result_number'])) {
                return;
            }

            $payload['operation_dates'] = array_filter([
                $model->updated_at,
                $model->getOriginal('updated_at'),
                $model->result_at,
                $model->getOriginal('result_at'),
            ]);
            $payload['draw_id'] = (int) ($model->getKey() ?? 0);
            $payload['product_dates'] = array_filter([
                $model->result_at,
                $model->getOriginal('result_at'),
            ]);
            $sections = [
                LottoDashboardMetricConfig::SECTION_OPERATIONS,
                LottoDashboardMetricConfig::SECTION_PRODUCT,
                LottoDashboardMetricConfig::SECTION_RISK,
            ];

            $drawSourceType = $this->resolveDrawSourceType($model);
            if ($drawSourceType !== null) {
                $payload['source_type_override'] = $drawSourceType;
            }
        } else {
            return;
        }

        $sourceTypeOverride = $payload['source_type_override'] ?? null;
        unset($payload['source_type_override']);

        $dispatch = function () use ($payload, $sections, $sourceTypeOverride): void {
            $service = app(DashboardSummarySyncService::class);
            $service->dispatchForModelChange('lotto', $payload, $sections, $sourceTypeOverride);

            $drawId = (int) ($payload['draw_id'] ?? 0);
            if ($drawId > 0 && in_array(LottoDashboardMetricConfig::SECTION_RISK, $sections, true)) {
                $service->dispatchRiskCurrentForDraw(
                    drawId: $drawId,
                    sourceType: $sourceTypeOverride ?? 'lotto',
                    sourceId: (string) ($payload['id'] ?? '')
                );
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($dispatch);

            return;
        }

        $dispatch();
    }

    private function resolveDrawSourceType(LottoDraw $draw): ?string
    {
        $status = strtolower((string) $draw->status);
        $originalStatus = strtolower((string) $draw->getOriginal('status'));
        $resultAt = $draw->result_at;
        $originalResultAt = $draw->getOriginal('result_at');

        if (($status === 'resulted' && $originalStatus !== 'resulted') || (! empty($resultAt) && empty($originalResultAt))) {
            return 'draw_resulted';
        }

        if ($status === 'closed' && $originalStatus !== 'closed' && empty($resultAt)) {
            return 'draw_closed';
        }

        return null;
    }
}
