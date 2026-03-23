<?php

namespace App\Console\Commands;

use App\Services\Dashboard\DashboardSummaryProjector;
use App\Services\Dashboard\DashboardSummarySyncService;
use App\Services\Dashboard\DashboardWebCodeResolver;
use App\Services\Dashboard\LottoDashboardMetricConfig;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RebuildLottoDashboardSummaryCommand extends Command
{
    protected $signature = 'dashboard:lotto-rebuild
        {--date= : Single date (Y-m-d)}
        {--from= : Start date (Y-m-d)}
        {--to= : End date (Y-m-d)}
        {--web-code= : Dashboard web code}
        {--market-id= : Limit to a market id (for lotto product/risk)}
        {--round-id= : Limit to a draw id (for lotto product/risk)}
        {--only=all : cash|product|risk|all}
        {--dry-run : Preview only, do not write}';

    protected $description = 'Rebuild lotto dashboard summary buckets';

    public function handle(
        DashboardSummarySyncService $syncService,
        DashboardSummaryProjector $projector,
        DashboardWebCodeResolver $webCodeResolver
    ): int {
        $only = strtolower(trim((string) $this->option('only')));
        if (!in_array($only, ['cash', 'product', 'risk', 'all'], true)) {
            $this->error('--only ต้องเป็น cash|product|risk|all');
            return 1;
        }

        $dates = $this->resolveDates();
        if (empty($dates)) {
            $this->error('ไม่พบช่วงวันที่สำหรับ rebuild');
            return 1;
        }

        $webCode = $webCodeResolver->resolve((string) $this->option('web-code'));
        $marketId = $this->option('market-id') !== null ? (int) $this->option('market-id') : null;
        $roundId = $this->option('round-id') !== null ? (int) $this->option('round-id') : null;
        $dryRun = (bool) $this->option('dry-run');

        $sections = match ($only) {
            'cash' => [LottoDashboardMetricConfig::SECTION_CASH, 'net'],
            'product' => [LottoDashboardMetricConfig::SECTION_PRODUCT, LottoDashboardMetricConfig::SECTION_OPERATIONS],
            'risk' => [LottoDashboardMetricConfig::SECTION_RISK],
            default => [
                LottoDashboardMetricConfig::SECTION_CASH,
                LottoDashboardMetricConfig::SECTION_PRODUCT,
                LottoDashboardMetricConfig::SECTION_RISK,
                LottoDashboardMetricConfig::SECTION_OPERATIONS,
                'net',
            ],
        };

        $this->info(sprintf(
            'เริ่ม rebuild lotto dashboard (%d day(s), web_code=%s, only=%s%s%s%s)',
            count($dates),
            $webCode,
            $only,
            $marketId !== null ? ', market_id=' . $marketId : '',
            $roundId !== null ? ', round_id=' . $roundId : '',
            $dryRun ? ', dry-run' : ''
        ));

        foreach ($dates as $date) {
            if ($dryRun) {
                $this->line('[dry-run] ' . $date);
                continue;
            }

            // Filtered rebuild for product/risk dimensions.
            if (($marketId !== null || $roundId !== null) && in_array($only, ['product', 'risk', 'all'], true)) {
                $lottoPayload = $projector->projectLotto($date, $webCode);

                if (in_array($only, ['product', 'all'], true) && Schema::hasTable('lotto_dashboard_market_summary')) {
                    $rows = collect((array) ($lottoPayload['markets'] ?? []))
                        ->when($marketId !== null, fn ($c) => $c->where('market_id', $marketId))
                        ->when($roundId !== null, fn ($c) => $c->where('round_id', $roundId))
                        ->values()
                        ->all();

                    if (!empty($rows)) {
                        DB::table('lotto_dashboard_market_summary')->upsert(
                            $rows,
                            ['summary_date', 'web_code', 'market_id', 'round_id'],
                            ['total_sales', 'total_tickets', 'total_players', 'total_payout', 'status', 'last_synced_at', 'updated_at']
                        );
                    }
                }

                if (in_array($only, ['risk', 'all'], true) && Schema::hasTable('lotto_dashboard_risk_snapshot')) {
                    $rows = collect((array) ($lottoPayload['risk'] ?? []))
                        ->when($marketId !== null, fn ($c) => $c->where('market_id', $marketId))
                        ->when($roundId !== null, fn ($c) => $c->where('round_id', $roundId))
                        ->values()
                        ->all();

                    if (!empty($rows)) {
                        DB::table('lotto_dashboard_risk_snapshot')->upsert(
                            $rows,
                            ['web_code', 'market_id', 'round_id', 'bet_type', 'number', 'snapshot_at'],
                            ['stake_total', 'payout_if_hit', 'liability', 'updated_at']
                        );
                    }
                }

                // Keep daily cash/net in sync for --only=all with filtered rebuild.
                if ($only === 'all') {
                    $syncService->syncBucket(
                        summaryDate: $date,
                        webCode: $webCode,
                        updatedSections: [LottoDashboardMetricConfig::SECTION_CASH, 'net'],
                        sourceType: 'rebuild',
                        sourceId: 'filtered'
                    );
                }
            } else {
                $syncService->syncBucket(
                    summaryDate: $date,
                    webCode: $webCode,
                    updatedSections: $sections,
                    sourceType: 'rebuild',
                    sourceId: 'manual'
                );
            }

            $this->line('synced ' . $date);
        }

        $this->info('rebuild complete');

        return 0;
    }

    /**
     * @return string[]
     */
    private function resolveDates(): array
    {
        $singleDate = trim((string) $this->option('date'));
        $from = trim((string) $this->option('from'));
        $to = trim((string) $this->option('to'));

        if ($singleDate !== '') {
            return [Carbon::parse($singleDate)->toDateString()];
        }

        if ($from === '' && $to === '') {
            return [now()->toDateString()];
        }

        $start = Carbon::parse($from !== '' ? $from : $to)->startOfDay();
        $end = Carbon::parse($to !== '' ? $to : $from)->startOfDay();
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $dates = [];
        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $dates[] = $cursor->toDateString();
        }

        return $dates;
    }
}

