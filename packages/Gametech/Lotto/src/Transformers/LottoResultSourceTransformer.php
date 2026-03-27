<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Contracts\LottoResultSource;
use League\Fractal\TransformerAbstract;

class LottoResultSourceTransformer extends TransformerAbstract
{
    public function transform(LottoResultSource $model): array
    {
        $active = (bool) ($model->is_active ?? false);

        return [
            'id' => (int) $model->id,
            'group_id' => (int) ($model->group_id ?? 0),
            'group_name' => (string) ($model->group_name ?? '-'),
            'market_id' => (int) ($model->market_id ?? 0),
            'market_name' => (string) ($model->market_name ?? '-'),
            'priority' => (int) $model->priority,
            'source_type' => strtoupper((string) $model->source_type),
            'http_method' => strtoupper((string) $model->http_method),
            'endpoint_url' => (string) $model->endpoint_url,
            'lookup_date_mode' => (string) $model->lookup_date_mode,
            'parser_type' => (string) $model->parser_type,
            'is_active' => '<button type="button" class="btn ' . ($active ? 'btn-success' : 'btn-danger') . ' btn-xs"'
                . ' onclick="editSourceStatus(' . (int) $model->id . ',' . ($active ? '0' : '1') . ')">'
                . ($active ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>')
                . '</button>',
            'action' => view('admin::module.lotto.result_sources.datatables_actions', [
                'id' => (int) $model->id,
            ])->render(),
        ];
    }
}
