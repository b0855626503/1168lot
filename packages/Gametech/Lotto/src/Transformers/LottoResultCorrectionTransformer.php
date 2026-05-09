<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Models\LottoResultCorrection;
use League\Fractal\TransformerAbstract;

class LottoResultCorrectionTransformer extends TransformerAbstract
{
    public function transform(LottoResultCorrection $model): array
    {
        return [
            'id' => (int) $model->id,
            'draw_id' => (int) $model->draw_id,
            'market_name' => (string) ($model->draw?->market?->name ?? '-'),
            'old_result_number' => $this->renderResultNumber($model->old_result_number),
            'new_result_number' => $this->renderResultNumber($model->new_result_number),
            'reason' => (string) ($model->reason ?? '-'),
            'affected_ticket_count' => (int) ($model->affected_ticket_count ?? 0),
            'total_reversed_amount' => number_format((float) ($model->total_reversed_amount ?? 0), 2),
            'total_reverse_failed_amount' => number_format((float) ($model->total_reverse_failed_amount ?? 0), 2),
            'total_new_payout_amount' => number_format((float) ($model->total_new_payout_amount ?? 0), 2),
            'status' => $this->renderStatusBadge((string) ($model->status ?? '')),
            'created_at' => $model->created_at ? $model->created_at->format('d/m/Y H:i:s') : '-',
            'action' => '<button type="button" class="btn btn-outline-primary btn-xs js-result-correction-detail" data-correction-id="'.(int) $model->id.'"><i class="fa fa-file-text-o"></i> รายละเอียด</button>',
        ];
    }

    /**
     * @param  array<string,mixed>|null  $resultNumber
     */
    private function renderResultNumber(?array $resultNumber): string
    {
        if (! is_array($resultNumber) || $resultNumber === []) {
            return '-';
        }

        $top3 = (string) ($resultNumber['top_3'] ?? '');
        $top2 = (string) ($resultNumber['top_2'] ?? '');
        $bottom2 = (string) ($resultNumber['bottom_2'] ?? ($resultNumber['last_2_digits'] ?? ''));

        return trim(sprintf('3บน:%s / 2บน:%s / 2ล่าง:%s', $top3 !== '' ? $top3 : '-', $top2 !== '' ? $top2 : '-', $bottom2 !== '' ? $bottom2 : '-'));
    }

    private function renderStatusBadge(string $status): string
    {
        $map = [
            'pending' => 'badge-secondary',
            'previewed' => 'badge-info',
            'processing' => 'badge-warning',
            'completed' => 'badge-success',
            'partial_failed' => 'badge-danger',
            'failed' => 'badge-danger',
        ];

        $class = $map[$status] ?? 'badge-secondary';
        $label = $status !== '' ? $status : 'unknown';

        return '<span class="badge '.$class.'">'.e($label).'</span>';
    }
}
