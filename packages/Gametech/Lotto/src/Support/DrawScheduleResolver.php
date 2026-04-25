<?php

namespace Gametech\Lotto\Support;

use Gametech\Lotto\Models\LotteryMarket;
use Illuminate\Support\Carbon;

class DrawScheduleResolver
{
    /**
     * @return array{should_generate:bool,schedule_type:string,draw_days:array<int,int>,draw_dates:array<int,int>,skip_reason:string}
     */
    public function resolve(LotteryMarket $market, Carbon $date): array
    {
        $resolvedSchedule = $this->resolveSchedule($market);
        $scheduleType = $resolvedSchedule['schedule_type'];
        $drawDays = $resolvedSchedule['draw_days'];
        $drawDates = $resolvedSchedule['draw_dates'];

        if ($resolvedSchedule['is_valid'] !== true) {
            return [
                'should_generate' => false,
                'schedule_type' => $scheduleType,
                'draw_days' => $drawDays,
                'draw_dates' => $drawDates,
                'skip_reason' => 'invalid_schedule_config',
            ];
        }

        if ($scheduleType === LotteryMarket::DRAW_SCHEDULE_TYPE_MANUAL) {
            return [
                'should_generate' => false,
                'schedule_type' => $scheduleType,
                'draw_days' => $drawDays,
                'draw_dates' => $drawDates,
                'skip_reason' => 'manual',
            ];
        }

        if ($scheduleType === LotteryMarket::DRAW_SCHEDULE_TYPE_WEEKLY) {
            $drawDays = $resolvedSchedule['draw_days'];
            if (in_array($date->dayOfWeekIso, $drawDays, true)) {
                return [
                    'should_generate' => true,
                    'schedule_type' => $scheduleType,
                    'draw_days' => $drawDays,
                    'draw_dates' => $drawDates,
                    'skip_reason' => '',
                ];
            }

            return [
                'should_generate' => false,
                'schedule_type' => $scheduleType,
                'draw_days' => $drawDays,
                'draw_dates' => $drawDates,
                'skip_reason' => 'not_in_weekly_schedule',
            ];
        }

        if ($scheduleType === LotteryMarket::DRAW_SCHEDULE_TYPE_MONTHLY) {
            $drawDates = $resolvedSchedule['draw_dates'];
            if (in_array($date->day, $drawDates, true)) {
                return [
                    'should_generate' => true,
                    'schedule_type' => $scheduleType,
                    'draw_days' => $drawDays,
                    'draw_dates' => $drawDates,
                    'skip_reason' => '',
                ];
            }

            return [
                'should_generate' => false,
                'schedule_type' => $scheduleType,
                'draw_days' => $drawDays,
                'draw_dates' => $drawDates,
                'skip_reason' => 'not_in_monthly_schedule',
            ];
        }

        return [
            'should_generate' => false,
            'schedule_type' => $scheduleType,
            'draw_days' => $drawDays,
            'draw_dates' => $drawDates,
            'skip_reason' => 'invalid_schedule_config',
        ];
    }

    /**
     * @return array{schedule_type:string,draw_days:array<int,int>,draw_dates:array<int,int>,is_valid:bool}
     */
    public function resolveSchedule(LotteryMarket $market): array
    {
        $scheduleType = $this->normalizeScheduleType($market->draw_schedule_type ?? null);
        $drawDays = $this->normalizeIntList($market->draw_days ?? null, 1, 7);
        $drawDates = $this->normalizeIntList($market->draw_dates ?? null, 1, 31);

        if ($scheduleType === null) {
            return $this->resolveFromLegacyDrawMode((string) ($market->draw_mode ?? LotteryMarket::DRAW_MODE_MANUAL));
        }

        if ($scheduleType === LotteryMarket::DRAW_SCHEDULE_TYPE_MANUAL) {
            return [
                'schedule_type' => $scheduleType,
                'draw_days' => [],
                'draw_dates' => [],
                'is_valid' => true,
            ];
        }

        if ($scheduleType === LotteryMarket::DRAW_SCHEDULE_TYPE_WEEKLY) {
            return [
                'schedule_type' => $scheduleType,
                'draw_days' => $drawDays,
                'draw_dates' => [],
                'is_valid' => count($drawDays) > 0,
            ];
        }

        if ($scheduleType === LotteryMarket::DRAW_SCHEDULE_TYPE_MONTHLY) {
            return [
                'schedule_type' => $scheduleType,
                'draw_days' => [],
                'draw_dates' => $drawDates,
                'is_valid' => count($drawDates) > 0,
            ];
        }

        return [
            'schedule_type' => $scheduleType,
            'draw_days' => [],
            'draw_dates' => [],
            'is_valid' => false,
        ];
    }

    private function normalizeScheduleType($value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim(strtolower($value));
        if ($normalized === '') {
            return null;
        }

        if (! in_array($normalized, LotteryMarket::drawScheduleTypes(), true)) {
            return $normalized;
        }

        return $normalized;
    }

    /**
     * @param  mixed  $value
     * @return array<int,int>
     */
    private function normalizeIntList($value, int $min, int $max): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $item) {
            if (is_string($item) && trim($item) === '') {
                continue;
            }

            $intValue = (int) $item;
            if ($intValue < $min || $intValue > $max) {
                continue;
            }

            $normalized[$intValue] = $intValue;
        }

        $values = array_values($normalized);
        sort($values);

        return $values;
    }

    /**
     * @return array{schedule_type:string,draw_days:array<int,int>,draw_dates:array<int,int>,is_valid:bool}
     */
    private function resolveFromLegacyDrawMode(string $drawMode): array
    {
        if ($drawMode === LotteryMarket::DRAW_MODE_DAILY) {
            return [
                'schedule_type' => LotteryMarket::DRAW_SCHEDULE_TYPE_WEEKLY,
                'draw_days' => [1, 2, 3, 4, 5, 6, 7],
                'draw_dates' => [],
                'is_valid' => true,
            ];
        }

        if ($drawMode === LotteryMarket::DRAW_MODE_WEEKDAYS) {
            return [
                'schedule_type' => LotteryMarket::DRAW_SCHEDULE_TYPE_WEEKLY,
                'draw_days' => [1, 2, 3, 4, 5],
                'draw_dates' => [],
                'is_valid' => true,
            ];
        }

        if ($drawMode === LotteryMarket::DRAW_MODE_WED_SAT_SUN) {
            return [
                'schedule_type' => LotteryMarket::DRAW_SCHEDULE_TYPE_WEEKLY,
                'draw_days' => [3, 6, 7],
                'draw_dates' => [],
                'is_valid' => true,
            ];
        }

        return [
            'schedule_type' => LotteryMarket::DRAW_SCHEDULE_TYPE_MANUAL,
            'draw_days' => [],
            'draw_dates' => [],
            'is_valid' => true,
        ];
    }
}
