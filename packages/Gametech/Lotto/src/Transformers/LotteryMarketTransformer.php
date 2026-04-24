<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Contracts\LotteryMarket;
use League\Fractal\TransformerAbstract;

class LotteryMarketTransformer extends TransformerAbstract
{
    public function transform(LotteryMarket $model): array
    {
        return [
            'id'         => (int) $model->id,
            'thumbnail'  => $this->renderThumbnail((string) ($model->logo ?? ''), (string) ($model->icon ?? '')),
            'name'       => $model->name,
            'group_name' => optional($model->group)->name ?? '-',
            'code'       => '<code>' . $model->code . '</code>',
            'draw_mode'  => $this->drawScheduleLabel($model),
            'auto_open_time' => $this->formatTime((string) ($model->auto_open_time ?? '')),
            'auto_close_time' => $this->formatTime((string) ($model->auto_close_time ?? '')),
            'auto_result_time' => $this->formatTime((string) ($model->auto_result_time ?? '')),
            'result_url' => $model->result_url
                ? '<a href="' . e($model->result_url) . '" target="_blank">ลิงก์ผล</a>'
                : '-',
            'result_apply_mode' => $this->renderResultApplyModeToggle((int) $model->id, (bool) ($model->auto_settle_on_result ?? true)),
            'auto_refund_mode' => $this->renderAutoRefundModeToggle((int) $model->id, (bool) ($model->auto_refund_on_no_result ?? false)),
            'auto_result_source_status' => $this->renderAutoResultSourceStatus((int) ($model->auto_result_sources_count ?? 0)),
            'is_enabled' => '<button type="button" class="btn ' . ($model->is_enabled ? 'btn-success' : 'btn-danger') . ' btn-xs"'
                . ' onclick="editdata(' . $model->id . ',' . ($model->is_enabled ? '0' : '1') . ',\'is_enabled\')">'
                . ($model->is_enabled ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>')
                . '</button>',
            'action' => view('admin::module.lotto.markets.datatables_actions', [
                'id' => $model->id,
                'market_name' => (string) ($model->name ?? ''),
            ])->render(),
        ];
    }

    private function drawScheduleLabel(LotteryMarket $model): string
    {
        $scheduleType = trim((string) ($model->draw_schedule_type ?? ''));
        $drawDays = is_array($model->draw_days ?? null) ? $model->draw_days : [];
        $drawDates = is_array($model->draw_dates ?? null) ? $model->draw_dates : [];

        if ($scheduleType === 'weekly') {
            $labels = $this->drawDayLabels($drawDays);
            if (count($labels) > 0) {
                return 'Auto: ' . implode(', ', $labels);
            }
        }

        if ($scheduleType === 'monthly') {
            $dateLabels = $this->drawDateLabels($drawDates);
            if (count($dateLabels) > 0) {
                return 'Auto: วันที่ ' . implode(', ', $dateLabels);
            }
        }

        if ($scheduleType === 'manual') {
            return 'Manual';
        }

        $legacyMode = (string) ($model->draw_mode ?? 'manual');
        if ($legacyMode === 'daily') {
            return 'Auto: จันทร์, อังคาร, พุธ, พฤหัสบดี, ศุกร์, เสาร์, อาทิตย์';
        }

        if ($legacyMode === 'weekdays') {
            return 'Auto: จันทร์, อังคาร, พุธ, พฤหัสบดี, ศุกร์';
        }

        if ($legacyMode === 'wed_sat_sun') {
            return 'Auto: พุธ, เสาร์, อาทิตย์';
        }

        return 'Manual';
    }

    /**
     * @param mixed $days
     * @return array<int,string>
     */
    private function drawDayLabels($days): array
    {
        if (! is_array($days)) {
            return [];
        }

        $map = [
            1 => 'จันทร์',
            2 => 'อังคาร',
            3 => 'พุธ',
            4 => 'พฤหัสบดี',
            5 => 'ศุกร์',
            6 => 'เสาร์',
            7 => 'อาทิตย์',
        ];

        $labels = [];
        foreach ($days as $day) {
            $value = (int) $day;
            if (! array_key_exists($value, $map)) {
                continue;
            }

            $labels[$value] = $map[$value];
        }

        return array_values($labels);
    }

    /**
     * @param mixed $dates
     * @return array<int,int>
     */
    private function drawDateLabels($dates): array
    {
        if (! is_array($dates)) {
            return [];
        }

        $values = [];
        foreach ($dates as $date) {
            $value = (int) $date;
            if ($value < 1 || $value > 31) {
                continue;
            }

            $values[$value] = $value;
        }

        ksort($values);

        return array_values($values);
    }

    private function formatTime(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '-';
        }

        return substr($trimmed, 0, 5);
    }

    private function renderThumbnail(string $logo, string $icon): string
    {
        $src = trim($logo) !== '' ? $logo : (trim($icon) !== '' ? $icon : '');
        if ($src === '') {
            return '-';
        }

        return '<img src="' . e($src) . '" alt="market" style="width:20px;height:20px;object-fit:cover;border-radius:50%;border:1px solid #e5e7eb;">';
    }

    private function renderAutoResultSourceStatus(int $count): string
    {
        if ($count > 0) {
            return '<span class="market-source-indicator market-source-indicator-on" title="ผูกแล้ว (' . $count . ')">'
                . '<span class="market-source-light"></span>'
                . '<span class="market-source-count">' . $count . '</span>'
                . '</span>';
        }

        return '<span class="market-source-indicator market-source-indicator-off" title="ยังไม่ผูก">'
            . '<span class="market-source-light"></span>'
            . '</span>';
    }

    private function renderResultApplyModeToggle(int $id, bool $autoSettleOnResult): string
    {
        $next = $autoSettleOnResult ? '0' : '1';
        $label = $autoSettleOnResult ? 'Auto' : 'Manual';
        $class = $autoSettleOnResult ? 'btn-success' : 'btn-secondary';

        return '<button type="button" class="btn ' . $class . ' btn-xs"'
            . ' onclick="editdata(' . $id . ',' . $next . ',\'auto_settle_on_result\')">'
            . $label
            . '</button>';
    }

    private function renderAutoRefundModeToggle(int $id, bool $autoRefundOnNoResult): string
    {
        $next = $autoRefundOnNoResult ? '0' : '1';
        $label = $autoRefundOnNoResult ? 'Auto' : 'Manual';
        $class = $autoRefundOnNoResult ? 'btn-success' : 'btn-secondary';

        return '<button type="button" class="btn ' . $class . ' btn-xs"'
            . ' onclick="editdata(' . $id . ',' . $next . ',\'auto_refund_on_no_result\')">'
            . $label
            . '</button>';
    }
}
