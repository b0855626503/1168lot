<?php

namespace Gametech\Admin\Transformers;

use Gametech\Member\Contracts\Member;
use League\Fractal\TransformerAbstract;

class MemberTransformer extends TransformerAbstract
{
    protected $config;
    protected $canViewTel;
    protected $canViewPass;

    /**
     * โหมดส่งออกข้อความล้วน (ไม่มี HTML)
     * - false: ใช้สำหรับ DataTables (มีปุ่ม, span, HTML)
     * - true : ใช้สำหรับ export (plain text)
     */
    protected bool $plain;

    public function __construct($config, bool $canViewTel = false, bool $canViewPass = false, bool $plain = false)
    {
        $this->config      = $config;
        $this->canViewTel  = $canViewTel;
        $this->canViewPass = $canViewPass;
        $this->plain       = $plain;
    }

    protected function toggleButton(bool $active, string $onClick): string
    {
        // โหมด plain: คืนค่าเป็นข้อความล้วน เพื่อ export ได้สะอาด
        if ($this->plain) {
            return $active ? 'Y' : 'N';
        }

        $class = $active ? 'btn-success' : 'btn-danger';
        $icon  = $active ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>';

        return '<button class="btn btn-xs icon-only '.$class.'" onclick="'.$onClick.'">'.$icon.'</button>';
    }

    /** สร้าง action HTML แบบ inline (เร็วกว่า view()->render()) */
    protected function buildActionHtml(int $code): string
    {
        // โหมด plain: ไม่ส่ง action HTML
        if ($this->plain) {
            return '';
        }

        // ใช้ template + strtr() เร็วและอ่านง่าย
        static $tpl = null;
        if ($tpl === null) {
            $tpl = <<<'HTML'
<div class="btn-group btn-group-sm">
    <button type="button" class="btn btn-primary" onclick="showModalNew({code},'gameuser')">
        <i class="fas fa-gamepad"></i> Game
    </button>
    <button type="button" class="btn btn-primary dropdown-toggle dropdown-icon dropdown-toggle-split"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <span class="sr-only">Toggle Dropdown</span>
    </button>
    <div class="dropdown-menu" role="menu">
        <a class="dropdown-item" href="javascript:void(0)" onclick="refill({code})">ทำรายการฝากเงิน</a>
        <a class="dropdown-item" href="javascript:void(0)" onclick="money({code})">เพิ่ม-ลด Credit</a>
        <a class="dropdown-item" href="javascript:void(0)" onclick="point({code})">เพิ่ม-ลด Point</a>
        <a class="dropdown-item" href="javascript:void(0)" onclick="diamond({code})">เพิ่ม-ลด Diamond</a>
        <a class="dropdown-item" href="javascript:void(0)" onclick="eventRefund({code})">คืนยอดกิจกรรม</a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="javascript:void(0)" onclick="showModalNew({code},'setwallet')">ประวัติการเพิ่ม-ลด Credit</a>
        <a class="dropdown-item" href="javascript:void(0)" onclick="showModalNew({code},'setpoint')">ประวัติการเพิ่ม-ลด Point</a>
        <a class="dropdown-item" href="javascript:void(0)" onclick="showModalNew({code},'setdiamond')">ประวัติการเพิ่ม-ลด Diamond</a>
        <a class="dropdown-item" href="javascript:void(0)" onclick="showModalNew({code},'deposit')">ประวัติการฝาก</a>
        <a class="dropdown-item" href="javascript:void(0)" onclick="showModalNew({code},'withdraw')">ประวัติการถอน</a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="javascript:void(0)" onclick="editModal({code})">แก้ไขข้อมูล</a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="javascript:void(0)" onclick="commentModal({code})">เพิ่มหมายเหตุ</a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="javascript:void(0)" onclick="delModal({code})">ลบข้อมูล</a>
    </div>
</div>
HTML;
        }

        // แทนที่ {code} ด้วย int (กัน XSS โดยธรรมชาติ)
        return strtr($tpl, ['{code}' => (string) $code]);
    }

    public function transform(Member $model): array
    {
        $config = $this->config;

        $codeInt = (int) $model->code;

        // สิทธิ์การเห็นข้อมูล
        $tel  = $this->canViewTel  ? (string) $model->tel       : '*****';
        $pass = $this->canViewPass ? (string) $model->user_pass : '*****';

        // ===== legacy (มาจาก SQL selectRaw) =====
        // รองรับกรณีไม่ได้ join มา: fallback เป็น 0
        $isLegacy = 0;
        if (isset($model->is_legacy)) {
            $isLegacy = (int) $model->is_legacy;
        } elseif (isset($model->legacy_at) && ! empty($model->legacy_at)) {
            $isLegacy = 1;
        }

        $legacyLabel = $isLegacy === 1 ? 'เก่า' : 'ใหม่';

        // ปุ่มโปรโมชัน/ผู้ใช้ใหม่/เปิดใช้งาน
        if ($this->plain) {
            $proVal     = (string) $model->promotion;          // 'Y'/'N'
            $newVal     = (int) $model->status_pro;            // 1/0
            $enableVal  = (string) $model->enable;             // 'Y'/'N'

            $proBtn     = $proVal;
            $newBtn     = $newVal;
            $enableBtn  = $enableVal;
        } else {
            $proBtn = $this->toggleButton(
                $model->promotion === 'Y',
                "editdata({$codeInt},'".core()->flip($model->promotion)."','promotion')"
            );

            $newBtn = $this->toggleButton(
                (int) $model->status_pro === 1,
                "editdata({$codeInt},'".core()->flipnum($model->status_pro)."','status_pro')"
            );

            $enableBtn = $this->toggleButton(
                $model->enable === 'Y',
                "editdata({$codeInt},'".core()->flip($model->enable)."','enable')"
            );
        }

        // อื่น ๆ (เดิม)
        $dateRegis = $model->date_create ? $model->date_create->format('d/m/Y H:i:s') : '';
        $up        = ($model->upline_code == 0) ? '' : ($model->up->name ?? '');

        // bank: เดิมเป็น HTML icon/รูปธนาคาร
        if ($this->plain) {
            $bankHtml = $model->bank->shortcode
                ?? $model->bank->name
                ?? '';
        } else {
            $bankHtml  = ($model->bank && $model->bank->shortcode && $model->bank->filepic)
                ? core()->displayBank($model->bank->shortcode, $model->bank->filepic)
                : '';
        }

        $refer = $model->refers->name ?? '';

        // ตัวเลข: plain ไม่ครอบ span
        if ($this->plain) {
            $point       = (string) $model->point_deposit;
            $sumDeposit  = (string) $model->sum_deposit;
            $sumWithdraw = (string) $model->sum_withdraw;
            $balance     = (string) $model->balance;
            $diamond     = (string) $model->diamond;
        } else {
            $point       = "<span class='text-primary'>{$model->point_deposit}</span>";
            $sumDeposit  = "<span class='text-primary'>{$model->sum_deposit}</span>";
            $sumWithdraw = "<span class='text-success'>{$model->sum_withdraw}</span>";
            $balance     = "<span class='text-success'>{$model->balance}</span>";
            $diamond     = "<span class='text-indigo'>{$model->diamond}</span>";
        }

        // legacy_at สำหรับ export/debug (ถ้า join มา)
        $legacyAt = '';
        if (isset($model->legacy_at) && ! empty($model->legacy_at)) {
            // ถ้าเป็น Carbon/DateTime จะ cast เป็น string ได้อยู่แล้วในหลายเคส
            $legacyAt = (string) $model->legacy_at;
        }

        return [
            'code'           => $codeInt,
            'date_regis'     => $dateRegis,
            'name'           => (string) $model->name,
            'firstname'      => (string) $model->firstname,
            'lastname'       => (string) $model->lastname,
            'up'             => $up,
            'down'           => (int) $model->downs_count,
            'bank'           => $bankHtml,
            'acc_no'         => (string) $model->acc_no,
            'user_name'      => (string) $model->user_name,
            'tel'            => $tel,
            'pass'           => $pass,
            'lineid'         => (string) $model->lineid,

            'count_deposit'  => (int) $model->count_deposit,
            'point'          => $point,
//            'sum_deposit'    => $sumDeposit,
//            'sum_withdraw'   => $sumWithdraw,
            'balance'        => $balance,
            'diamond'        => $diamond,

            'cashback' => $model->cashback,
            'bonus' => $model->bonus,
            'faststart' => $model->faststart,
            'ic' => $model->ic,

            // ===== legacy fields (เพิ่มใหม่) =====
            // ใช้ sort/filter ด้วย is_legacy (0/1)
            'is_legacy'      => $isLegacy,
            // ใช้แสดงผลในตาราง (เก่า/ใหม่)
            'legacy_label'   => $this->plain ? $legacyLabel : $legacyLabel,
            // เก็บไว้ export/debug
            'legacy_at'      => $legacyAt,

            'refer'          => $refer,
            'pro'            => $proBtn,
            'newuser'        => $newBtn,
            'game_user'      => (string) $model->game_user,
            'enable'         => $enableBtn,
            'action'         => $this->buildActionHtml($codeInt),
        ];
    }
}
