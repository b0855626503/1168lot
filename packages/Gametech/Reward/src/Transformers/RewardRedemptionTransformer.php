<?php

namespace Gametech\Reward\Transformers;

use League\Fractal\TransformerAbstract;
use Illuminate\Support\Carbon;

class RewardRedemptionTransformer extends TransformerAbstract
{
    protected function badge(string $text, string $class = 'bg-secondary'): string
    {
        $t = e($text);
        return '<span class="badge '.$class.'">'.$t.'</span>';
    }

    protected function yesNoBadge($value): string
    {
        $yes = ((int) $value === 1) || $this->toBool($value);
        return $yes
            ? $this->badge('แนะนำ', 'bg-warning text-dark')
            : '';
    }

    public function transform($row): array
    {
        // ===== featured (จาก rewards_list.is_featured ที่ alias เป็น reward_is_featured) =====
        $featured = $this->yesNoBadge(data_get($row, 'reward_is_featured'));

        // ===== snapshot (หลักฐาน ณ ตอนแลก) =====
        $rewardCode = (string) (data_get($row, 'reward_code_snapshot') ?? '');
        $rewardName = (string) (data_get($row, 'reward_name_snapshot') ?? '');

        $typeSnap = (string) (data_get($row, 'reward_type_snapshot') ?? '');
        $modeSnap = (string) (data_get($row, 'fulfillment_mode_snapshot') ?? '');

        $pointCost = (int) (data_get($row, 'point_cost_snapshot') ?? 0);
        $creditAmt = data_get($row, 'credit_amount_snapshot');
        $gemAmt    = data_get($row, 'gem_amount_snapshot');

        // ===== computed: limit label / stock remaining (อิง rewards_list ปัจจุบัน) =====
        $limitLabel = $this->limitLabelFromRewardRow($row);
        $stockRemaining = $this->stockRemainingFromRewardRow($row);

        // ===== status badge ของ "งานแลก" =====
        $status = (string) (data_get($row, 'status') ?? '');
        $statusBadge = $this->redemptionStatusBadge($status, $typeSnap, $modeSnap);

        // ===== action view (fallback-safe) =====
        $actionViewCandidates = [
            // แนะนำให้คุณสร้าง view อันนี้ไว้สำหรับงานแลก
            'reward::reward_redemptions.datatables_actions',
            'reward::reward_redemption.datatables_actions',
            'reward::reward_redemptions.datatables_actions',
            'admin::module.reward_redemptions.datatables_actions',
            'admin::module.reward_redemption.datatables_actions',

            // fallback เก่า (เผื่อคุณยังไม่มีของ redemption)
            'reward::reward_list.datatables_actions',
            'reward::rewards_list.datatables_actions',
            'admin::module.reward_list.datatables_actions',
            'admin::module.reward.datatables_actions',
        ];

        $actionHtml = '';
        foreach ($actionViewCandidates as $viewName) {
            if (view()->exists($viewName)) {
                $actionHtml = view($viewName, [
                    // ✅ สำหรับ redemption ให้ส่ง id ไปชัด ๆ
                    'id' => (int) data_get($row, 'id', 0),
                    'code' => (int) data_get($row, 'id', 0), // เผื่อ view เก่าใช้ชื่อ code
                    'reward_id' => (int) data_get($row, 'reward_id', 0),
                    'member_id' => (int) data_get($row, 'member_id', 0),
                ])->render();
                break;
            }
        }

        if($status !== 'pending'){
            $actionHtml = '';
        }

        $memberCode = data_get($row, 'member_code');
        $username   = data_get($row, 'member_username');
        $name       = data_get($row, 'member_name');
        $tel        = data_get($row, 'member_tel');

        $memberHtml = '';
        if ($memberCode) {
            $label = $name;

            $memberHtml  = '<div class="fw-bold">ID: ' . e($username) . '</div>';
            $memberHtml .= '<div class="text-muted small">' . e($name) . '</div>';

            if ($tel) {
                $memberHtml .= '<div class="text-muted small">' . e($tel) . '</div>';
            }

            // ถ้ามีหน้า admin member
            if (function_exists('route')) {
                try {
                    $url = route('admin.members.edit', $memberCode);
                    $memberHtml = '<a href="'.$url.'" target="_blank" class="text-decoration-none">'
                        .$memberHtml.'</a>';
                } catch (\Throwable $e) {
                    // route ไม่มี ก็ไม่เป็นไร
                }
            }
        }

        // ===== output keys ต้องตรงกับ DataTable columns =====
        return [
            'id' => (int) data_get($row, 'id', 0),

            // reward featured
            'reward_is_featured' => $featured,
            'member' => $memberHtml,
            // snapshot
            'reward_code_snapshot' => $rewardCode,
            'reward_name_snapshot' => $rewardName,

            'reward_type_snapshot' => $this->rewardTypeLabel($typeSnap),
            'fulfillment_mode_snapshot' => $this->fulfillmentModeLabel($modeSnap),

            'point_cost_snapshot' => $this->fmtInt($pointCost),
            'credit_amount_snapshot' => $this->fmtMoney($creditAmt),
            'gem_amount_snapshot' => $this->fmtInt($gemAmt),

            // computed
            'limit_label' => $limitLabel,
            'stock_remaining' => $stockRemaining,

            // redemption status
            'status' => $statusBadge,

            // audit time
            'created_at' => $this->fmtDateTime(data_get($row, 'created_at')),
            'fulfilled_at' => $this->fmtDateTime(data_get($row, 'fulfilled_at')),

            'action' => $actionHtml,
        ];
    }

    /* ===============================
     | Business labels
     =============================== */

    private function rewardTypeLabel(?string $type): string
    {
        $t = strtolower(trim((string) $type));

        return match ($t) {
            'wallet_credit' => 'เครดิต',
            'wallet_gem'    => 'เพชร',
            'external'      => 'ภายนอก',
            default         => (string) $type,
        };
    }

    private function fulfillmentModeLabel(?string $mode): string
    {
        $m = strtolower(trim((string) $mode));

        return match ($m) {
            'auto'     => 'อัตโนมัติ',
            'manual'   => 'ทีมงาน',
            'approval' => 'รออนุมัติ',
            default    => (string) $mode,
        };
    }

    private function redemptionStatusBadge(string $status, string $typeSnap, string $modeSnap): string
    {
        $s = strtolower(trim($status));
        $type = strtolower(trim($typeSnap));
        $mode = strtolower(trim($modeSnap));

        // “งานภายนอก” ให้เด่นกว่าปกติ
        $isExternalLike = ($type === 'external') || in_array($mode, ['manual', 'approval'], true);

        return match ($s) {
            'pending' => $isExternalLike
                ? $this->badge('รอดำเนินการ', 'bg-warning text-dark')
                : $this->badge('รอดำเนินการ', 'bg-warning text-dark'),

            'assigned' => $this->badge('มีคนรับงานแล้ว', 'bg-info text-dark'),
            'fulfilled' => $this->badge('สำเร็จ', 'bg-success'),
            'rejected' => $this->badge('ไม่ผ่าน', 'bg-danger'),
            'canceled', 'cancelled' => $this->badge('ยกเลิก', 'bg-secondary'),

            default => $this->badge($status !== '' ? $status : '-', 'bg-dark'),
        };
    }

    /**
     * สรุปกติกาการแลก (อ่านจาก reward_* ที่ join มา)
     */
    private function limitLabelFromRewardRow($row): string
    {
        $type = (string) (data_get($row, 'reward_limit_type') ?? 'unlimited');
        $type = strtolower(trim($type));

        if ($type === '' || $type === 'unlimited') {
            return 'ไม่จำกัด';
        }

        if ($type === 'per_reward') {
            $n = (int) (data_get($row, 'reward_limit_per_user') ?? 1);
            if ($n <= 0) $n = 1;
            return "ไม่เกิน {$n} ครั้ง / คน";
        }

        if ($type === 'per_period') {
            $n = (int) (data_get($row, 'reward_limit_per_period') ?? 1);
            if ($n <= 0) $n = 1;

            $period = (string) (data_get($row, 'reward_limit_period') ?? 'day');
            $period = strtolower(trim($period));

            $p = match ($period) {
                'day'   => 'วัน',
                'week'  => 'สัปดาห์',
                'month' => 'เดือน',
                'event' => 'กิจกรรม',
                default => 'ช่วงเวลา',
            };

            return "ไม่เกิน {$n} ครั้ง / {$p}";
        }

        return '-';
    }

    private function stockRemainingFromRewardRow($row): string
    {
        $unlimited = data_get($row, 'reward_stock_unlimited');
        if (((int) $unlimited === 1) || $this->toBool($unlimited)) {
            return 'ไม่จำกัด';
        }

        $stock = (int) (data_get($row, 'reward_stock') ?? 0);
        $reserved = (int) (data_get($row, 'reward_reserved_stock') ?? 0);

        $remain = $stock - $reserved;
        if ($remain < 0) $remain = 0;

        return $this->fmtInt($remain);
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
