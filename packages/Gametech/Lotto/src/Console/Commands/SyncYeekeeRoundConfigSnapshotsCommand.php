<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\YeekeeMarketSetting;
use Gametech\Lotto\Models\YeekeeRound;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncYeekeeRoundConfigSnapshotsCommand extends Command
{
    protected $signature = 'lotto:sync-yeekee-round-config-snapshots
        {--market_id= : Sync only one Yeekee market}
        {--dry-run : Preview only without updating rounds}';

    protected $description = 'Sync current Yeekee market settings into draft/open Yeekee round config snapshots';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $marketId = $this->option('market_id');

        $marketQuery = LotteryMarket::query()
            ->where('result_mode', LotteryMarket::RESULT_MODE_YEEKEE);

        if ($marketId !== null && $marketId !== '') {
            $marketQuery->where('id', (int) $marketId);
        }

        $markets = $marketQuery->orderBy('id')->get();
        $summary = [
            'dry_run' => $dryRun,
            'market_count' => $markets->count(),
            'rounds_matched' => 0,
            'rounds_updated' => 0,
            'rounds_unchanged' => 0,
            'skipped_missing_setting' => 0,
            'items' => [],
        ];

        foreach ($markets as $market) {
            $setting = YeekeeMarketSetting::query()->where('market_id', (int) $market->id)->first();
            if (! $setting instanceof YeekeeMarketSetting) {
                $summary['skipped_missing_setting']++;

                continue;
            }

            $snapshot = $this->buildRoundConfigSnapshot($setting);
            $roundQuery = YeekeeRound::query()
                ->join('lotto_draws', 'lotto_draws.id', '=', 'yeekee_rounds.lotto_draw_id')
                ->where('yeekee_rounds.market_id', (int) $market->id)
                ->whereIn('yeekee_rounds.status', ['draft', 'open'])
                ->whereIn('lotto_draws.status', ['draft', 'open'])
                ->select('yeekee_rounds.*')
                ->orderBy('yeekee_rounds.id');

            $roundQuery->chunkById(200, function ($rounds) use (&$summary, $snapshot, $dryRun): void {
                foreach ($rounds as $round) {
                    if (! $round instanceof YeekeeRound) {
                        continue;
                    }

                    $summary['rounds_matched']++;
                    $current = is_array($round->config_snapshot_json) ? $round->config_snapshot_json : [];
                    $merged = array_replace($current, $snapshot);

                    if ($current == $merged) {
                        $summary['rounds_unchanged']++;

                        continue;
                    }

                    if (! $dryRun) {
                        DB::table('yeekee_rounds')
                            ->where('id', (int) $round->id)
                            ->update([
                                'config_snapshot_json' => json_encode($merged, JSON_UNESCAPED_UNICODE),
                                'updated_at' => now(),
                            ]);
                    }

                    $summary['rounds_updated']++;
                    $summary['items'][] = [
                        'yeekee_round_id' => (int) $round->id,
                        'lotto_draw_id' => (int) $round->lotto_draw_id,
                        'market_id' => (int) $round->market_id,
                        'round_no' => (int) $round->round_no,
                        'status' => $dryRun ? 'would_update' : 'updated',
                    ];
                }
            }, 'yeekee_rounds.id', 'id');
        }

        $this->line(json_encode($summary, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    /**
     * @return array<string,mixed>
     */
    private function buildRoundConfigSnapshot(YeekeeMarketSetting $setting): array
    {
        $formulaConfig = is_array($setting->formula_config) ? $setting->formula_config : [];
        $formulaPreset = strtoupper(trim((string) ($formulaConfig['default_preset'] ?? $formulaConfig['preset'] ?? 'SHOOTS_SUM_MINUS_POSITION')));
        if (! in_array($formulaPreset, ['SHOOTS_SUM_MINUS_POSITION', 'SHOOTS_SUM_ONLY'], true)) {
            $formulaPreset = 'SHOOTS_SUM_MINUS_POSITION';
        }

        $normalizedFormulaConfig = [
            'preset' => $formulaPreset,
            'version' => (int) ($formulaConfig['version'] ?? 1),
        ];

        if ($formulaPreset === 'SHOOTS_SUM_MINUS_POSITION') {
            $normalizedFormulaConfig['subtract_position'] = (int) ($formulaConfig['subtract_position'] ?? 16);
        }

        if ($formulaPreset === 'SHOOTS_SUM_ONLY') {
            $normalizedFormulaConfig['modulo'] = (int) ($formulaConfig['modulo'] ?? 100000);
            $configuredInputRules = is_array($formulaConfig['input_rules'] ?? null) ? $formulaConfig['input_rules'] : [];
            $normalizedFormulaConfig['input_rules'] = [
                'cutoff_seconds_before_close' => max(0, (int) ($configuredInputRules['cutoff_seconds_before_close'] ?? 0)),
            ];
        }

        return [
            'round_config' => is_array($setting->round_config) ? $setting->round_config : [],
            'formula_config' => $normalizedFormulaConfig,
            'reward_enabled' => (bool) ($setting->reward_enabled ?? false),
            'reward_config' => is_array($setting->reward_config) ? $setting->reward_config : [],
            'refund_config' => is_array($setting->refund_config) ? $setting->refund_config : [],
            'refund_if_bet_entries_below_min' => (bool) ($setting->refund_if_bet_entries_below_min ?? false),
            'external_seed_config' => [],
        ];
    }
}
