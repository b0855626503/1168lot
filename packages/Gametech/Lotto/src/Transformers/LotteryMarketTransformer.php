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
            'draw_mode'  => $this->drawModeLabel((string) ($model->draw_mode ?? 'manual')),
            'auto_open_time' => $this->formatTime((string) ($model->auto_open_time ?? '')),
            'auto_close_time' => $this->formatTime((string) ($model->auto_close_time ?? '')),
            'auto_result_time' => $this->formatTime((string) ($model->auto_result_time ?? '')),
            'result_url' => $model->result_url
                ? '<a href="' . e($model->result_url) . '" target="_blank">ลิงก์ผล</a>'
                : '-',
            'is_enabled' => '<button type="button" class="btn ' . ($model->is_enabled ? 'btn-success' : 'btn-danger') . ' btn-xs"'
                . ' onclick="editdata(' . $model->id . ',' . ($model->is_enabled ? '0' : '1') . ',\'is_enabled\')">'
                . ($model->is_enabled ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>')
                . '</button>',
            'action' => view('admin::module.lotto.markets.datatables_actions', [
                'id' => $model->id,
            ])->render(),
        ];
    }

    private function drawModeLabel(string $mode): string
    {
        if ($mode === 'daily') {
            return 'Auto ทุกวัน';
        }

        if ($mode === 'weekdays') {
            return 'Auto จันทร์-ศุกร์';
        }

        return 'Manual';
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

        return '<img src="' . e($src) . '" alt="market" style="width:32px;height:32px;object-fit:cover;border-radius:4px;">';
    }
}
