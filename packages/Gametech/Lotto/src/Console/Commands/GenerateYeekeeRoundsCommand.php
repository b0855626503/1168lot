<?php

namespace Gametech\Lotto\Console\Commands;

use Carbon\Carbon;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\YeekeeMarketSetting;
use Gametech\Lotto\Models\YeekeeRound;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateYeekeeRoundsCommand extends Command
{
    protected $signature = 'lotto:generate-yeekee-draws
        {--date= : Draw date (Y-m-d)}
        {--window= : Top-up horizon เช่น +6h}
        {--market_id= : Generate only one market}
        {--dry-run : Preview only without insert}';

    protected $description = 'Generate yeekee draws and rounds from yeekee market configuration';

    public function handle(): int
    {
        $lotteryTimezone = (string) config('app.timezone', 'UTC');
        $dateOption = trim((string) $this->option('date'));
        $windowOption = trim((string) $this->option('window'));
        $targetDates = $this->resolveTargetDates($dateOption, $windowOption, $lotteryTimezone);
        if ($targetDates === null) {
            $this->error('Invalid options: use --date=Y-m-d or --window=+6h');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $marketId = $this->option('market_id');

        $marketQuery = LotteryMarket::query()
            ->with('group')
            ->where('is_enabled', true)
            ->where('result_mode', LotteryMarket::RESULT_MODE_YEEKEE);

        if ($marketId !== null && $marketId !== '') {
            $marketQuery->where('id', (int) $marketId);
        }

        $markets = $marketQuery->orderBy('id')->get();

        $summary = [
            'dates' => $targetDates,
            'dry_run' => $dryRun,
            'window' => $windowOption !== '' ? $windowOption : null,
            'market_count' => $markets->count(),
            'rounds_expected' => 0,
            'draw_created' => 0,
            'draw_exists' => 0,
            'round_created' => 0,
            'round_exists' => 0,
            'skipped_group_disabled' => 0,
            'skipped_cross_day' => 0,
            'days' => [],
            'items' => [],
        ];

        foreach ($targetDates as $targetDate) {
            $date = Carbon::createFromFormat('Y-m-d', $targetDate, $lotteryTimezone)->startOfDay();
            $daySummary = [
                'date' => $targetDate,
                'rounds_expected' => 0,
                'draw_created' => 0,
                'draw_exists' => 0,
                'round_created' => 0,
                'round_exists' => 0,
                'skipped_cross_day' => 0,
            ];

            foreach ($markets as $market) {
                if (! $market->group || ! (bool) $market->group->is_enabled) {
                    $summary['skipped_group_disabled']++;

                    continue;
                }

                $setting = YeekeeMarketSetting::query()->where('market_id', (int) $market->id)->first();
                $roundConfig = is_array($setting?->round_config) ? $setting->round_config : [];

                $durationMinutes = max(1, (int) ($roundConfig['round_duration_minutes'] ?? 15));
                $shootWindowSeconds = max(0, (int) ($roundConfig['shoot_window_after_bet_close_seconds'] ?? 60));
                $settlementDelaySeconds = max(0, (int) ($roundConfig['settlement_delay_after_shoot_close_seconds'] ?? 60));
                $expectedPayoutSlaMinutes = max(0, (int) ($roundConfig['expected_payout_sla_minutes'] ?? 5));

                $roundCount = (int) floor((24 * 60) / $durationMinutes);
                if ($roundCount <= 0) {
                    $roundCount = 1;
                }

                $summary['rounds_expected'] += $roundCount;
                $daySummary['rounds_expected'] += $roundCount;

                $dayStart = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d').' 00:00:00', $lotteryTimezone);

                for ($index = 0; $index < $roundCount; $index++) {
                    $roundNo = $index + 1;
                    $betOpenAt = $dayStart->copy();
                    $betCloseAt = $dayStart->copy()->addMinutes($roundNo * $durationMinutes);
                    $shootOpenAt = $betCloseAt->copy();
                    $shootCloseAt = $shootOpenAt->copy()->addSeconds($shootWindowSeconds);
                    $resultComputeAt = $shootCloseAt->copy()->addSeconds($settlementDelaySeconds);
                    $expectedSettlementDeadlineAt = $resultComputeAt->copy()->addMinutes($expectedPayoutSlaMinutes);

                    if (
                        $betOpenAt->toDateString() !== $date->toDateString()
                        || $betCloseAt->toDateString() !== $date->toDateString()
                        || $resultComputeAt->toDateString() !== $date->toDateString()
                    ) {
                        $summary['skipped_cross_day']++;
                        $daySummary['skipped_cross_day']++;
                        $summary['items'][] = [
                            'date' => $targetDate,
                            'market_id' => (int) $market->id,
                            'round_no' => $roundNo,
                            'draw_close_at' => $betCloseAt->format('Y-m-d H:i:s'),
                            'status' => 'skipped_cross_day',
                        ];

                        continue;
                    }

                    $drawLookup = [
                        'market_id' => (int) $market->id,
                        'draw_date' => $date->format('Y-m-d'),
                        'close_at' => $betCloseAt->format('Y-m-d H:i:s'),
                    ];

                    $drawPayload = [
                        'open_at' => $betOpenAt->format('Y-m-d H:i:s'),
                        'result_at' => $resultComputeAt->format('Y-m-d H:i:s'),
                        'status' => 'open',
                        'created_by' => null,
                    ];

                    $existingRoundByNaturalKey = YeekeeRound::query()
                        ->where('market_id', (int) $market->id)
                        ->where('round_date', $date->format('Y-m-d'))
                        ->where('round_no', $roundNo)
                        ->first();

                    if ($existingRoundByNaturalKey instanceof YeekeeRound) {
                        $summary['draw_exists']++;
                        $summary['round_exists']++;
                        $daySummary['draw_exists']++;
                        $daySummary['round_exists']++;
                        $summary['items'][] = [
                            'date' => $targetDate,
                            'market_id' => (int) $market->id,
                            'round_no' => $roundNo,
                            'draw_close_at' => $betCloseAt->format('Y-m-d H:i:s'),
                            'status' => 'exists_round',
                        ];

                        continue;
                    }

                    $draw = LottoDraw::query()->where($drawLookup)->first();
                    if ($dryRun && ! $draw instanceof LottoDraw) {
                        $summary['draw_created']++;
                        $summary['round_created']++;
                        $daySummary['draw_created']++;
                        $daySummary['round_created']++;
                        $summary['items'][] = [
                            'date' => $targetDate,
                            'market_id' => (int) $market->id,
                            'round_no' => $roundNo,
                            'status' => 'will_create_draw_and_round',
                        ];

                        continue;
                    }

                    $roundPayload = [
                        'market_id' => (int) $market->id,
                        'round_date' => $date->format('Y-m-d'),
                        'round_no' => $roundNo,
                        'bet_open_at' => $betOpenAt->format('Y-m-d H:i:s'),
                        'bet_close_at' => $betCloseAt->format('Y-m-d H:i:s'),
                        'shoot_open_at' => $shootOpenAt->format('Y-m-d H:i:s'),
                        'shoot_close_at' => $shootCloseAt->format('Y-m-d H:i:s'),
                        'result_compute_at' => $resultComputeAt->format('Y-m-d H:i:s'),
                        'expected_settlement_deadline_at' => $expectedSettlementDeadlineAt->format('Y-m-d H:i:s'),
                        'status' => 'open',
                        'config_snapshot_json' => [
                            'round_config' => [
                                'round_duration_minutes' => $durationMinutes,
                                'shoot_window_after_bet_close_seconds' => $shootWindowSeconds,
                                'settlement_delay_after_shoot_close_seconds' => $settlementDelaySeconds,
                                'expected_payout_sla_minutes' => $expectedPayoutSlaMinutes,
                            ],
                        ],
                    ];

                    if ($dryRun) {
                        $summary['draw_exists']++;
                        $daySummary['draw_exists']++;

                        $roundLookup = ['lotto_draw_id' => (int) $draw->id];
                        $existingRound = YeekeeRound::query()->where($roundLookup)->first();
                        if (! $existingRound instanceof YeekeeRound) {
                            $summary['round_created']++;
                            $daySummary['round_created']++;
                            $status = 'will_create_round';
                        } else {
                            $summary['round_exists']++;
                            $daySummary['round_exists']++;
                            $status = 'exists_round';
                        }
                    } else {
                        $draw = null;
                        $round = null;
                        DB::transaction(function () use ($drawLookup, $drawPayload, $roundPayload, &$draw, &$round): void {
                            $draw = LottoDraw::query()->firstOrCreate($drawLookup, $drawPayload);
                            $round = YeekeeRound::query()->firstOrCreate(
                                ['lotto_draw_id' => (int) $draw->id],
                                $roundPayload
                            );
                        });

                        if ($draw instanceof LottoDraw && $draw->wasRecentlyCreated) {
                            $summary['draw_created']++;
                            $daySummary['draw_created']++;
                        } else {
                            $summary['draw_exists']++;
                            $daySummary['draw_exists']++;
                        }

                        if ($round instanceof YeekeeRound && $round->wasRecentlyCreated) {
                            $summary['round_created']++;
                            $daySummary['round_created']++;
                        } else {
                            $summary['round_exists']++;
                            $daySummary['round_exists']++;
                        }

                        $status = $round instanceof YeekeeRound && $round->wasRecentlyCreated
                            ? 'created_round'
                            : 'exists_round';
                    }

                    $summary['items'][] = [
                        'date' => $targetDate,
                        'market_id' => (int) $market->id,
                        'round_no' => $roundNo,
                        'draw_close_at' => $betCloseAt->format('Y-m-d H:i:s'),
                        'status' => $status,
                    ];
                }
            }

            $summary['days'][] = $daySummary;
        }

        $this->line(json_encode($summary, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    private function resolveTargetDates(string $dateOption, string $windowOption, string $timezone): ?array
    {
        if ($dateOption !== '' && $windowOption !== '') {
            return null;
        }

        if ($windowOption !== '') {
            if (! preg_match('/^\+?(\d+)h$/i', $windowOption, $matches)) {
                return null;
            }

            $hours = max(1, (int) $matches[1]);
            $from = now($timezone);
            $to = $from->copy()->addHours($hours);
            $dates = [];
            $cursor = $from->copy()->startOfDay();
            $endDay = $to->copy()->startOfDay();
            while ($cursor->lte($endDay)) {
                $dates[] = $cursor->format('Y-m-d');
                $cursor->addDay();
            }

            return array_values(array_unique($dates));
        }

        if ($dateOption !== '') {
            $date = $this->resolveDate($dateOption);
            if (! $date) {
                return null;
            }

            return [$date->format('Y-m-d')];
        }

        return [now($timezone)->startOfDay()->format('Y-m-d')];
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
