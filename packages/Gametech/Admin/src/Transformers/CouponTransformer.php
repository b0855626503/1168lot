<?php

namespace Gametech\Admin\Transformers;

use Gametech\Core\Contracts\Coupon;
use League\Fractal\TransformerAbstract;

class CouponTransformer extends TransformerAbstract
{
    public function transform(Coupon $model)
    {
        $free = [
            'Y' => 'ฟรีเครดิต',
            'N' => 'เครดิตปกติ',
        ];

        return [
            'code'         => (int) $model->code,
            'name'         => $model->name,
            'cashback'     => $free[$model->cashback] ?? $model->cashback,
            'amount'       => $model->amount,
            'value'        => $model->value,
            'turnpro'      => $model->turnpro,
            'amount_limit' => $model->amount_limit,
            'date_start'   => core()->formatDate($model->date_start, 'Y-m-d'),
            'date_stop'    => core()->formatDate($model->date_stop, 'Y-m-d'),

            // ====== ฟิลด์สรุปคูปองจาก withCount ======
            'total_generated' => (int) ($model->couponlists_count ?? 0),
            'total_unused'    => (int) ($model->coupon_unused_count ?? 0),
            // ==========================================

            'enable' => '<button type="button" class="btn ' . ($model->enable == 'Y' ? 'btn-success' : 'btn-danger')
                . ' btn-xs icon-only" onclick="editdata('
                . $model->code . ','
                . "'" . core()->flip($model->enable) . "'" . ','
                . "'enable'"
                . ')">'
                . ($model->enable == 'Y'
                    ? '<i class="fa fa-check"></i>'
                    : '<i class="fa fa-times"></i>')
                . '</button>',

            'gen' => view('admin::module.coupon.gen', [
                'code' => $model->code,
                'gen'  => $model->gen,
            ])->render(),

            'action' => view('admin::module.coupon.datatables_actions', [
                'code' => $model->code,
                'gen'  => $model->gen,
            ])->render(),
        ];
    }
}
