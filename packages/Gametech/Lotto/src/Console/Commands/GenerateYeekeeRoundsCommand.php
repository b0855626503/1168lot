<?php

namespace Gametech\Lotto\Console\Commands;

use Carbon\Carbon;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\YeekeeMarketSetting;
use Gametech\Lotto\Models\YeekeeRound;
use Illuminate\Console\Command;

class GenerateYeekeeRoundsCommand extends Command
{
    protected $signature = 'lotto:generate-yeekee-rounds
        {--date= : Draw date (Y-m-d), default=today}
        {--market_id= : Generate only one market}
        {--dry-run : Preview only without insert}';

    protected $description = 'Generate yeekee rounds mapped to existing lotto_draws';

    public function handle(): int
    {
        $date = $this->resolveDate((string) $this->option('date'));
        if (! $date) {
            $this->error('Invalid --date format. Use Y-m-d');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $marketId = $this->option('market_id');

        $query = LottoDraw::query()
            ->select('lotto_draws.*')
            ->join('lotto_markets', 'lotto_markets.id', '=', 'lotto_draws.market_id')
            ->whereDate('lotto_draws.draw_date', $date->format('Y-m-d'))
            ->where('lotto_markets.result_mode', LotteryMarket::RESULT_MODE_YEEKEE);

        if ($marketId !== null && $marketId !== '') {
            $query->where('lotto_draws.market_id', (int) $marketId);
        }

        $draws = $query->orderBy('lotto_draws.market_id')->orderBy('lotto_draws.id')->get();
        $created = 0;
        $exists = 0;
        $skippedCrossDay = 0;

        foreach ($draws as $draw) {
            $settings = YeekeeMarketSetting::query()->where('market_id', (int) $draw->market_id)->first();
            $roundConfig = is_array($settings?->round_config) ? $settings->round_config : [];

            $shootWindowSeconds = (int) ($roundConfig['shoot_window_after_bet_close_seconds'] ?? 60);
            $settlementDelaySeconds = (int) ($roundConfig['settlement_delay_after_shoot_close_seconds'] ?? 60);
            $expectedPayoutSlaMinutes = (int) ($roundConfig['expected_payout_sla_minutes'] ?? 5);

            $betOpenAt = Carbon::parse((string) $draw->open_at);
            $betCloseAt = Carbon::parse((string) $draw->close_at);
            $shootOpenAt = $betCloseAt->copy();
            $shootCloseAt = $betCloseAt->copy()->addSeconds(max(0, $shootWindowSeconds));
            $resultComputeAt = $shootCloseAt->copy()->addSeconds(max(0, $settlementDelaySeconds));
            $expectedSettlementDeadlineAt = $resultComputeAt->copy()->addMinutes(max(0, $expectedPayoutSlaMinutes));
            $roundDate = Carbon::parse((string) $draw->draw_date)->toDateString();
            $roundEndOfDay = Carbon::parse($roundDate.' 23:59:59');

            if (
                $betOpenAt->gt($roundEndOfDay)
                || $betCloseAt->gt($roundEndOfDay)
                || $shootOpenAt->gt($roundEndOfDay)
                || $shootCloseAt->gt($roundEndOfDay)
                || $resultComputeAt->gt($roundEndOfDay)
                || $expectedSettlementDeadlineAt->gt($roundEndOfDay)
            ) {
                $skippedCrossDay++;

                continue;
            }

            $payload = [
                'market_id' => (int) $draw->market_id,
                'lotto_draw_id' => (int) $draw->id,
                'round_date' => (string) $draw->draw_date,
                'round_no' => 1,
                'bet_open_at' => $betOpenAt->format('Y-m-d H:i:s'),
                'bet_close_at' => $betCloseAt->format('Y-m-d H:i:s'),
                'shoot_open_at' => $shootOpenAt->format('Y-m-d H:i:s'),
                'shoot_close_at' => $shootCloseAt->format('Y-m-d H:i:s'),
                'result_compute_at' => $resultComputeAt->format('Y-m-d H:i:s'),
                'expected_settlement_deadline_at' => $expectedSettlementDeadlineAt->format('Y-m-d H:i:s'),
                'status' => 'draft',
                'config_snapshot_json' => [
                    'round_config' => [
                        'shoot_window_after_bet_close_seconds' => $shootWindowSeconds,
                        'settlement_delay_after_shoot_close_seconds' => $settlementDelaySeconds,
                        'expected_payout_sla_minutes' => $expectedPayoutSlaMinutes,
                    ],
                ],
            ];

            $alreadyExists = YeekeeRound::query()
                ->where('lotto_draw_id', (int) $draw->id)
                ->exists();

            if ($alreadyExists) {
                $exists++;

                continue;
            }

            if (! $dryRun) {
                YeekeeRound::query()->create($payload);
            }

            $created++;
        }

        $this->line(json_encode([
            'date' => $date->format('Y-m-d'),
            'dry_run' => $dryRun,
            'draw_count' => $draws->count(),
            'created' => $created,
            'exists' => $exists,
            'skipped_cross_day' => $skippedCrossDay,
        ], JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    private function resolveDate(string $date): ?Carbon
    {
        if (trim($date) === '') {
            return now()->startOfDay();
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
