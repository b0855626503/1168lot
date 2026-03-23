<?php

namespace Gametech\Lotto\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillLottoPayoutCommand extends Command
{
    protected $signature = 'lotto:backfill-payout {--dry-run : Calculate and report without writing database}';

    protected $description = 'Backfill payout from legacy member/rate-plan sources into global lotto bet settings';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $timestamp = Carbon::now()->format('Ymd_His');

        $successRows = [];
        $ambiguityRows = [];
        $missingRows = [];

        $settings = DB::table('lotto_market_bet_settings as s')
            ->join('lotto_markets as m', 'm.id', '=', 's.market_id')
            ->select('s.id', 's.market_id', 's.bet_type', 's.payout as current_payout', 'm.group_id')
            ->orderBy('s.id')
            ->get();

        foreach ($settings as $setting) {
            $candidates = $this->resolveCandidates(
                (int) $setting->group_id,
                (string) $setting->bet_type,
                (float) $setting->current_payout,
                (int) $setting->id
            );

            if (empty($candidates)) {
                $missingRows[] = [
                    'setting_id' => (int) $setting->id,
                    'market_id' => (int) $setting->market_id,
                    'group_id' => (int) $setting->group_id,
                    'bet_type' => (string) $setting->bet_type,
                ];
                continue;
            }

            usort($candidates, function (array $left, array $right): int {
                $priorityCompare = $left['priority'] <=> $right['priority'];
                if ($priorityCompare !== 0) {
                    return $priorityCompare;
                }

                $leftUpdated = $left['updated_at'];
                $rightUpdated = $right['updated_at'];
                if ($leftUpdated !== $rightUpdated) {
                    if ($leftUpdated === null) {
                        return 1;
                    }
                    if ($rightUpdated === null) {
                        return -1;
                    }
                    return strcmp((string) $rightUpdated, (string) $leftUpdated);
                }

                return $right['row_id'] <=> $left['row_id'];
            });

            $topPriority = $candidates[0]['priority'];
            $topCandidates = array_values(array_filter($candidates, static fn (array $row): bool => $row['priority'] === $topPriority));
            $distinctPayouts = collect($topCandidates)
                ->pluck('payout')
                ->map(static fn ($value): string => number_format((float) $value, 2, '.', ''))
                ->unique()
                ->values()
                ->all();

            if (count($distinctPayouts) > 1) {
                $ambiguityRows[] = [
                    'setting_id' => (int) $setting->id,
                    'market_id' => (int) $setting->market_id,
                    'group_id' => (int) $setting->group_id,
                    'bet_type' => (string) $setting->bet_type,
                    'candidate_count' => count($topCandidates),
                    'distinct_payouts' => $distinctPayouts,
                    'selected' => $candidates[0],
                ];
            }

            $selected = $candidates[0];

            if (! $dryRun) {
                DB::table('lotto_market_bet_settings')
                    ->where('id', (int) $setting->id)
                    ->update([
                        'payout' => (float) $selected['payout'],
                    ]);
            }

            $successRows[] = [
                'setting_id' => (int) $setting->id,
                'market_id' => (int) $setting->market_id,
                'group_id' => (int) $setting->group_id,
                'bet_type' => (string) $setting->bet_type,
                'source' => (string) $selected['source'],
                'payout' => (float) $selected['payout'],
                'row_id' => (int) $selected['row_id'],
                'updated_at' => $selected['updated_at'],
            ];
        }

        if (! $dryRun) {
            $this->backfillDrawSnapshotPayout();
        }

        $coverage = $settings->count() === 0
            ? 100.0
            : round((count($successRows) / $settings->count()) * 100, 2);

        $report = [
            'dry_run' => $dryRun,
            'generated_at' => Carbon::now()->toDateTimeString(),
            'total_settings' => $settings->count(),
            'success_count' => count($successRows),
            'missing_count' => count($missingRows),
            'ambiguity_count' => count($ambiguityRows),
            'coverage_percent' => $coverage,
            'cutover_blocked' => ($coverage < 100.0) || count($missingRows) > 0,
            'manual_review_required' => count($ambiguityRows) > 0,
        ];

        $this->writeReport($timestamp, $report, $successRows, $ambiguityRows, $missingRows);

        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if ($report['cutover_blocked']) {
            $this->error('Cutover blocked: payout coverage is not 100% or missing rows exist.');
            return self::FAILURE;
        }

        if (count($ambiguityRows) > 0) {
            $this->warn('Ambiguity rows require manual review before cutover.');
        }

        return self::SUCCESS;
    }

    private function resolveCandidates(int $groupId, string $betType, float $currentPayout, int $settingId): array
    {
        $memberCandidates = [];
        $rateCandidates = [];

        $hasRatePlanTables = Schema::hasTable('lotto_rate_plans')
            && Schema::hasTable('lotto_rate_plan_items');

        if ($hasRatePlanTables && Schema::hasTable('member_lotto_settings')) {
            $memberCandidates = DB::table('member_lotto_settings as mls')
                ->join('lotto_rate_plans as rp', 'rp.id', '=', 'mls.rate_plan_id')
                ->join('lotto_rate_plan_items as rpi', 'rpi.rate_plan_id', '=', 'rp.id')
                ->where('rp.group_id', $groupId)
                ->where('rp.is_enabled', true)
                ->where('rpi.bet_type', $betType)
                ->select([
                    DB::raw("'member_override' as source"),
                    DB::raw('1 as priority'),
                    'rpi.payout as payout',
                    'mls.updated_at as updated_at',
                    'mls.id as row_id',
                ])
                ->get()
                ->map(static fn ($row): array => [
                    'source' => (string) $row->source,
                    'priority' => (int) $row->priority,
                    'payout' => (float) $row->payout,
                    'updated_at' => $row->updated_at ? (string) $row->updated_at : null,
                    'row_id' => (int) $row->row_id,
                ])
                ->all();
        }

        if ($hasRatePlanTables) {
            $rateCandidates = DB::table('lotto_rate_plans as rp')
                ->join('lotto_rate_plan_items as rpi', 'rpi.rate_plan_id', '=', 'rp.id')
                ->where('rp.group_id', $groupId)
                ->where('rp.is_enabled', true)
                ->where('rpi.bet_type', $betType)
                ->select([
                    DB::raw("'rate_plan' as source"),
                    DB::raw('2 as priority'),
                    'rpi.payout as payout',
                    DB::raw('null as updated_at'),
                    'rpi.id as row_id',
                ])
                ->get()
                ->map(static fn ($row): array => [
                    'source' => (string) $row->source,
                    'priority' => (int) $row->priority,
                    'payout' => (float) $row->payout,
                    'updated_at' => null,
                    'row_id' => (int) $row->row_id,
                ])
                ->all();
        }

        $defaultCandidates = [];
        if ($currentPayout > 0) {
            $defaultCandidates[] = [
                'source' => 'default',
                'priority' => 3,
                'payout' => $currentPayout,
                'updated_at' => null,
                'row_id' => $settingId,
            ];
        }

        return array_merge($memberCandidates, $rateCandidates, $defaultCandidates);
    }

    private function backfillDrawSnapshotPayout(): void
    {
        $drawSettings = DB::table('lotto_draw_bet_settings as ds')
            ->join('lotto_draws as d', 'd.id', '=', 'ds.draw_id')
            ->join('lotto_market_bet_settings as ms', function ($join): void {
                $join->on('ms.market_id', '=', 'd.market_id')
                    ->on('ms.bet_type', '=', 'ds.bet_type');
            })
            ->select('ds.id', 'ms.payout')
            ->orderBy('ds.id')
            ->get();

        foreach ($drawSettings as $row) {
            DB::table('lotto_draw_bet_settings')
                ->where('id', (int) $row->id)
                ->update(['payout' => (float) $row->payout]);
        }
    }

    private function writeReport(
        string $timestamp,
        array $summary,
        array $successRows,
        array $ambiguityRows,
        array $missingRows
    ): void {
        $dir = storage_path('app/lotto');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $dir . "/backfill_payout_{$timestamp}_summary.json",
            json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        file_put_contents(
            $dir . "/backfill_payout_{$timestamp}_success.json",
            json_encode($successRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        file_put_contents(
            $dir . "/backfill_payout_{$timestamp}_ambiguity.json",
            json_encode($ambiguityRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        file_put_contents(
            $dir . "/backfill_payout_{$timestamp}_missing.json",
            json_encode($missingRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
