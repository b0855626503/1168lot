<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Contracts\LottoNavbar;
use League\Fractal\TransformerAbstract;

class LottoNavbarTransformer extends TransformerAbstract
{
    public function transform(LottoNavbar $model): array
    {
        $isPublished = (bool) ($model->is_published ?? false);
        $isActive = (bool) ($model->is_active ?? false);

        $state = $isPublished
            ? '<span class="badge badge-success">PUBLISHED</span>'
            : '<span class="badge badge-secondary">DRAFT</span>';

        if (! $isActive) {
            $state .= ' <span class="badge badge-dark">INACTIVE</span>';
        }

        return [
            'id' => (int) $model->id,
            'code' => e((string) $model->code),
            'name' => e((string) ($model->name ?? '-')),
            'state' => $state,
            'published_version' => $model->published_version !== null ? (int) $model->published_version : '-',
            'updated_at_text' => optional($model->updated_at)->format('Y-m-d H:i:s') ?: '-',
            'action' => view('admin::module.lotto.navbar_configs.datatables_actions', [
                'id' => (int) $model->id,
                'is_published' => $isPublished,
                'is_active' => $isActive,
            ])->render(),
        ];
    }
}
