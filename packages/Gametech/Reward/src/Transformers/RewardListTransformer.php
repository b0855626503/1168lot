<?php

namespace Gametech\Reward\Transformers;

use Gametech\Reward\Contracts\RewardList;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Carbon;

class RewardListTransformer extends TransformerAbstract
{

    protected function toggleButton(bool $active, string $onClick): string
    {
        $class = $active ? 'btn-success' : 'btn-danger';
        $icon  = $active ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>';
        return '<button class="btn btn-xs icon-only '.$class.'" onclick="'.$onClick.'">'.$icon.'</button>';
    }
    public function transform(RewardList $model): array
    {
        $stockUnlimited = $this->toBool($model->stock_unlimited ?? false);

        $stock     = $model->stock;
        $reserved  = (int) ($model->reserved_stock ?? 0);
        $remaining = $stockUnlimited
            ? 'ไม่จำกัด'
            : max(((int) ($stock ?? 0)) - $reserved, 0);

        // ===== action view (fallback-safe) =====
        $actionViewCandidates = [
            'reward::reward_list.datatables_actions',
            'reward::rewards_list.datatables_actions',
            'admin::module.reward_list.datatables_actions',
            'admin::module.reward.datatables_actions',
        ];

        $actionHtml = '';
        foreach ($actionViewCandidates as $viewName) {
            if (view()->exists($viewName)) {
                $actionHtml = view($viewName, ['code' => $model->id])->render();
                break;
            }
        }
        $codeInt = (int) $model->id;
        $featuredBtn = $this->toggleButton((int)$model->is_featured === 1, "editdata({$codeInt},'".core()->flipnum((int)$model->is_featured)."','is_featured')");

        return [
            'id' => $model->id,

            // core
            'code' => (string) ($model->code ?? ''),
            'name' => (string) ($model->name ?? ''),
            'is_featured ' => $featuredBtn,

            // type / mode (label)
            'reward_type'       => $this->rewardTypeLabel($model->reward_type),
            'fulfillment_mode'  => $this->fulfillmentModeLabel($model->fulfillment_mode),

            // cost / amount
            'point_cost'    => number_format((int) ($model->point_cost ?? 0)),
            'credit_amount' => $this->fmtMoney($model->credit_amount),
            'gem_amount'    => $this->fmtInt($model->gem_amount),

            // NEW: rule label
            'limit_label' => $this->limitLabel($model),

            // availability
            'start_at' => $this->fmtDateTime($model->start_at),
            'end_at'   => $this->fmtDateTime($model->end_at),

            // stock
            'stock_unlimited'  => $stockUnlimited ? 'ไม่จำกัด' : 'จำกัด',
            'stock'            => $stockUnlimited ? '-' : $this->fmtInt($stock),
            'reserved_stock'   => $stockUnlimited ? '-' : $this->fmtInt($reserved),
            'stock_remaining'  => $remaining,

            // status / audit
            'status'      => $this->statusBadge($model->status),
            'created_at'  => $this->fmtDateTime($model->created_at),

            'action' => $actionHtml,
        ];
    }

    /* ===============================
     | Label helpers
     =============================== */

    private function rewardTypeLabel(?string $type): string
    {
        return match ($type) {
            'wallet_credit' => 'เครดิต',
            'wallet_gem'    => 'เพชร',
            'external'      => 'ภายนอก',
            default         => (string) $type,
        };
    }

    private function fulfillmentModeLabel(?string $mode): string
    {
        return match ($mode) {
            'auto'     => 'อัตโนมัติ',
            'manual'   => 'ทีมงาน',
            'approval' => 'รออนุมัติ',
            default    => (string) $mode,
        };
    }

    /**
     * สรุปกติกาการแลก (ใช้ field ใหม่)
     */
    private function limitLabel(RewardList $model): string
    {
        $type = $model->limit_type ?? 'unlimited';

        if ($type === 'unlimited') {
            return 'ไม่จำกัด';
        }

        if ($type === 'per_reward') {
            $n = (int) ($model->limit_per_user ?? 1);
            return "ไม่เกิน {$n} ครั้ง / คน";
        }

        if ($type === 'per_period') {
            $n = (int) ($model->limit_per_period ?? 1);
            $period = match ($model->limit_period) {
                'day'   => 'วัน',
                'week'  => 'สัปดาห์',
                'month' => 'เดือน',
                'event' => 'กิจกรรม',
                default => '',
            };
            return "ไม่เกิน {$n} ครั้ง / {$period}";
        }

        return '-';
    }

    private function statusBadge(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'active'   => 'ใช้งาน',
            'inactive' => 'ปิด',
            'draft'    => 'ร่าง',
            'archived' => 'เก็บ',
            default    => (string) $status,
        };
    }

    /* ===============================
     | Format helpers
     =============================== */

    private function fmtDateTime($value): string
    {
        if (empty($value)) return '';

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function fmtMoney($value): string
    {
        if ($value === null || $value === '') return '';
        return number_format((float) $value, 2, '.', ',');
    }

    private function fmtInt($value): string
    {
        if ($value === null || $value === '') return '';
        return number_format((int) $value, 0, '.', ',');
    }

    private function toBool($value): bool
    {
        if (is_bool($value)) return $value;
        $v = strtolower(trim((string) $value));
        return in_array($v, ['1', 'true', 'yes', 'y', 'on'], true);
    }
}
