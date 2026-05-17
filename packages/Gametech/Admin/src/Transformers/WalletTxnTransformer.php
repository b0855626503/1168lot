<?php

namespace Gametech\Admin\Transformers;

use Gametech\Lotto\Models\WalletTransaction;
use League\Fractal\TransformerAbstract;

class WalletTxnTransformer extends TransformerAbstract
{
    public function transform(WalletTransaction $model): array
    {
        return [
            'id' => (int) $model->id,
            'user_name' => e($model->member?->user_name ?? 'N/A'),
            'direction' => $model->direction === 'CREDIT'
                ? "<span class='badge badge-success'>CREDIT</span>"
                : "<span class='badge badge-danger'>DEBIT</span>",
            'amount' => "<span class='text-primary'>".number_format((float) $model->amount, 2).'</span>',
            'balance_before' => "<span class='text-info'>".number_format((float) $model->balance_before, 2).'</span>',
            'balance_after' => "<span class='text-danger'>".number_format((float) $model->balance_after, 2).'</span>',
            'ref_type' => e($model->ref_type),
            'ref_code' => e((string) $model->ref_code) ?: '-',
            'status' => match ($model->status) {
                'SUCCESS' => "<span class='badge badge-success'>SUCCESS</span>",
                'PENDING' => "<span class='badge badge-warning'>PENDING</span>",
                'FAILED' => "<span class='badge badge-danger'>FAILED</span>",
                'REVERSED' => "<span class='badge badge-secondary'>REVERSED</span>",
                default => e($model->status),
            },
            'description' => e((string) $model->description) ?: '-',
            'created_at' => $model->created_at?->format('d/m/Y H:i:s') ?? '-',
        ];
    }
}
