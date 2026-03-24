<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Services\DrawService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateAutoLottoDrawsCommand extends Command
{
    protected $signature = 'lotto:generate-auto-draws
        {--date= : Start date (Y-m-d). Default=tomorrow}
        {--days=1 : Number of days to generate (min 1, max 30)}
        {--market_id= : Generate only one market id}
        {--dry-run : Preview only without creating draws}';

    protected $description = 'Generate lotto draws automatically from market schedule settings';

    public function handle(DrawService $drawService): int
    {
        $startDate = $this->resolveStartDate((string) $this->option('date'));
        if (! $startDate) {
            $this->error('Invalid --date format. Use Y-m-d');

            return self::FAILURE;
        }

        $days = (int) $this->option('days');
        $days = max(1, min($days, 30));

        $marketId = $this->option('market_id');
        $dryRun = (bool) $this->option('dry-run');

        $marketQuery = LotteryMarket::query()
            ->with('group')
            ->whereIn('draw_mode', [LotteryMarket::DRAW_MODE_DAILY, LotteryMarket::DRAW_MODE_WEEKDAYS])
            ->where('is_enabled', true);

        if ($marketId !== null && $marketId !== '') {
            $marketQuery->where('id', (int) $marketId);
        }

        $markets = $marketQuery->orderBy('id')->get();

        $summary = [
            'dry_run' => $dryRun,
            'start_date' => $startDate->format('Y-m-d'),
            'days' => $days,
            'market_count' => $markets->count(),
            'created' => 0,
            'exists' => 0,
            'skipped' => 0,
            'items' => [],
        ];

        foreach ($markets as $market) {
            if (! $market->group || ! (bool) $market->group->is_enabled) {
                $summary['skipped']++;
                $summary['items'][] = [
                    'market_id' => (int) $market->id,
                    'draw_date' => null,
                    'status' => 'skip_group_disabled',
                ];
                continue;
            }

            if (! $market->auto_close_time) {
                $summary['skipped']++;
                $summary['items'][] = [
                    'market_id' => (int) $market->id,
                    'draw_date' => null,
                    'status' => 'skip_missing_close_time',
                ];
                continue;
            }

            for ($offset = 0; $offset < $days; $offset++) {
                $targetDate = $startDate->copy()->addDays($offset);

                if (! $this->shouldGenerateForDate($market, $targetDate)) {
                    continue;
                }

                $alreadyExists = LottoDraw::query()
                    ->where('market_id', (int) $market->id)
                    ->whereDate('draw_date', $targetDate->format('Y-m-d'))
                    ->exists();

                if ($alreadyExists) {
                    $summary['exists']++;
                    $summary['items'][] = [
                        'market_id' => (int) $market->id,
                        'draw_date' => $targetDate->format('Y-m-d'),
                        'status' => 'exists',
                    ];
                    continue;
                }

                $payload = $this->buildDrawPayload($market, $targetDate);

                if (! $dryRun) {
                    $drawService->createDraft($payload);
                }

                $summary['created']++;
                $summary['items'][] = [
                    'market_id' => (int) $market->id,
                    'draw_date' => $targetDate->format('Y-m-d'),
                    'status' => $dryRun ? 'will_create' : 'created',
                ];
            }
        }

        $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    private function resolveStartDate(string $date): ?Carbon
    {
        if (trim($date) === '') {
            return now((string) config('app.timezone', 'Asia/Bangkok'))
                ->addDay()
                ->startOfDay();
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function shouldGenerateForDate(LotteryMarket $market, Carbon $date): bool
    {
        if ((string) $market->draw_mode === LotteryMarket::DRAW_MODE_DAILY) {
            return true;
        }

        if ((string) $market->draw_mode === LotteryMarket::DRAW_MODE_WEEKDAYS) {
            return $date->dayOfWeekIso >= 1 && $date->dayOfWeekIso <= 5;
        }

        return false;
    }

    private function buildDrawPayload(LotteryMarket $market, Carbon $date): array
    {
        $openTime = (string) ($market->auto_open_time ?: '00:00:00');
        $closeTime = (string) $market->auto_close_time;
        $resultTime = (string) ($market->auto_result_time ?: '');

        $openAt = $date->format('Y-m-d') . ' ' . substr($openTime, 0, 8);
        $closeAt = $date->format('Y-m-d') . ' ' . substr($closeTime, 0, 8);
        $resultAt = $resultTime !== '' ? $date->format('Y-m-d') . ' ' . substr($resultTime, 0, 8) : null;

        return [
            'market_id' => (int) $market->id,
            'draw_date' => $date->format('Y-m-d'),
            'open_at' => $openAt,
            'close_at' => $closeAt,
            'result_at' => $resultAt,
            'status' => 'draft',
            'created_by' => null,
        ];
    }
}
