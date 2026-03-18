<?php

namespace Gametech\Admin\Transformers;

use Gametech\Payment\Contracts\Withdraw;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class WithdrawTransformer extends TransformerAbstract
{
    protected function toggleButton(bool $active, string $onClick): string
    {
        $class = $active ? 'btn-success' : 'btn-danger';
        $icon  = $active ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>';
        return '<button type="button" class="btn '.$class.' btn-xs icon-only" onclick="'.$onClick.'">'.$icon.'</button>';
    }

    protected function buildConfirmHtml(int $code, int $status, int $empApprove): string
    {
        if ($status === 0 && $empApprove === 0) {
            return '<button class="btn btn-xs btn-secondary icon-only" onclick="editModal('.$code.')"><i class="fas fa-check"></i></button>';
        }
        if ($status === 0 && $empApprove !== 0) {
            return '<button class="btn btn-xs btn-secondary icon-only" onclick="fixModal('.$code.')"><i class="fas fa-check-double"></i></button>';
        }
        return '';
    }

    protected function buildCancelHtml(int $code, int $status): string
    {
        if ($status === 0) {
            return '<button class="btn btn-xs btn-warning icon-only" onclick="clearModal('.$code.')"><i class="fas fa-times"></i></button>';
        }
        return '';
    }

    protected function buildDeleteHtml(int $code, int $status): string
    {
        if ($status === 0) {
            return '<button class="btn btn-xs btn-danger icon-only" onclick="delModal('.$code.')"><i class="fas fa-trash"></i></button>';
        }
        return '';
    }

    /** ดึงค่า string จากอ็อบเจ็กต์หรืออาเรย์ (ปลอดภัย, เบา) */
    protected function getMixed($source, string $key): ?string
    {
        if (is_array($source)) {
            return isset($source[$key]) ? (string)$source[$key] : null;
        }
        if (is_object($source)) {
            return isset($source->{$key}) ? (string)$source->{$key} : null;
        }
        return null;
    }

    public function transform(Withdraw $model): array
    {
        $statusMap = [0 => 'รอดำเนินการ', 1 => 'อนุมัติ', 2 => 'ไม่อนุมัติ'];

        $code    = (int)$model->code;
        $status  = (int)($model->status ?? 0);
        $empCode = (int)($model->emp_approve ?? 0);

        // --- โลโก้ธนาคาร + เลขบัญชี ---
        static $bankCache = [];
        $accNoHtml = '';
        if ($model->bank && $model->bank->shortcode && $model->bank->filepic) {
            $label = $model->bank->shortcode.' ['.(string)($model->member->acc_no ?? '').']';
            $key   = $label.'|'.$model->bank->filepic;
            if (!isset($bankCache[$key])) {
                $bankCache[$key] = core()->displayBank($label, $model->bank->filepic);
            }
            $accNoHtml = $bankCache[$key];
        }

        // --- ตัวเลข/เครดิตพร้อมสี ---
        $balanceHtml         = '<span style="color:blue">'.(string)$model->balance.'</span>';
        $amountHtml          = '<span style="color:red">'.(string)$model->amount.'</span>';
        $amountBalanceHtml   = '<span style="color:black">'.(string)$model->amount_balance.'</span>';
        $amountLimitHtml     = '<span style="color:black">'.(string)$model->amount_limit.'</span>';
        $amountLimitRateHtml = '<span style="color:black">'.(string)$model->amount_limit_rate.'</span>';
        $beforeHtml          = '<span style="color:gray">'.(string)$model->oldcredit.'</span>';
        $afterHtml           = '<span style="color:gray">'.(string)$model->aftercredit.'</span>';

        // --- วันเวลา + สมาชิก ---
        $date     = $model->date_record ? $model->date_record->format('d/m/y') : '';
        $time     = (string)($model->timedept ?? '');
        $username = (string)($model->member_user ?? '');
        $gameUser = (string)($model->member->game_user ?? '');
        $name     = (string)($model->member->name ?? '');

        // --- หมายเหตุสมาชิก (รองรับทั้ง latestMemberRemark หรือ collection เดิม) ---
//        $remarkModel = method_exists($model, 'latestMemberRemark')
//            ? ($model->latestMemberRemark ?? null)
//            : ($model->member_remark ? $model->member_remark->first() : null);
//        $remark = $remarkModel->remark ?? (string)($model->remark ?? '');

        // --- IP (escape + tooltip) ---
        $ipText = (string)($model->ip ?? '');
        $ipHtml = '<span class="text-long" data-toggle="tooltip" title="'.e($ipText).'">'
            . e(Str::limit($ipText, 10))
            . '</span>';

        // --- BONUS: โปรของ "บิลล่าสุด" (รองรับ object/array + fallback) ---
        $bonus = '';
        // 1) ถ้ามี relation latestBill ให้ใช้ก่อน (แนะนำให้ eager load)
//        $bill = $model->latestBill ?? null;
//
//        // 2) ถ้าไม่มี latestBill แต่โหลด bills มาด้วย ให้ fallback เป็น first()
//        if (!$bill && $model->relationLoaded('bills') && $model->bills) {
//            $bill = $model->bills->first();
//        }

//        if ($bill) {
//            // promotion อาจเป็น object หรือ array
//            $promotion = $bill->promotion ?? null;
//            $pname = $this->getMixed($promotion, 'name_th') ?? '';   // รองรับทั้ง $obj->name_th และ $arr['name_th']
//            // date_create อาจเป็น Carbon หรือ string
//            $pdate = '';
//            if (isset($bill->date_create)) {
//                $pdate = ($bill->date_create instanceof \Carbon\Carbon)
//                    ? $bill->date_create->format('d/m/Y')
//                    : (string)$bill->date_create;
//            }
//            $bonus = trim($pname . ($pdate ? ' ['.$pdate.']' : ''));
//        }else{
//            $bonus = $model->promotion?->name_th ?? '';
//        }

        $bonus = $model->promotion?->name_th ?? '';

        // --- REFILL: รองรับ array/object/relation ---
        $refill = '';
        $pl = $model->payment_last ?? null;          // บางระบบเป็น cast array หรือ accessors
        if (!$pl && method_exists($model, 'paymentLast')) {
            $pl = $model->paymentLast;               // ถ้าเป็นรีเลชันชื่อ camelCase
        }

        if ($pl) {
            $bankName = $this->getMixed($pl, 'bank')  ?? '';
            $val      = $this->getMixed($pl, 'value') ?? '';
            if ($bankName !== '') {
                $refill = $bankName.($val !== '' ? ' [ '.$val.' ]' : '');
            }
        }

        // --- สถานะ / วันอนุมัติ / แอดมิน ---
        $statusText  = $statusMap[$status] ?? '-';
        $dateApprove = ($model->date_approve instanceof \Carbon\Carbon)
            ? $model->date_approve->format('d/m/y H:i:s')
            : '';
        $empApprove  = $empCode === 0 ? '' : (string)($model->admin->user_name ?? '');

        // --- ปุ่ม toggle check ---
        $checkBtn = $this->toggleButton(
            (string)$model->check_status === 'Y',
            "editdata({$code},'".core()->flip($model->check_status)."','check_status')"
        );

        // --- ปุ่มยืนยัน/ยกเลิก/ลบ (inline HTML) ---
        $waitingHtml = $this->buildConfirmHtml($code, $status, $empCode);
        $cancelHtml  = $this->buildCancelHtml($code, $status);
        $deleteHtml  = $this->buildDeleteHtml($code, $status);

        return [
            'code'               => $code,
            'acc_no'             => $accNoHtml,
            'balance'            => $balanceHtml,
            'amount'             => $amountHtml,
            'amount_balance'     => $amountBalanceHtml,
            'amount_limit'       => $amountLimitHtml,
            'amount_limit_rate'  => $amountLimitRateHtml,
            'before'             => $beforeHtml,
            'after'              => $afterHtml,
            'date'               => $date,
            'time'               => $time,
            'username'           => $username,
            'name'               => $name,
            'game_user'          => $gameUser,
//            'remark'             => $remark,
            'ip'                 => $ipHtml,
            'bonus'              => $bonus,   // ← เติมได้แล้ว ไม่ว่างเพราะรองรับได้หลายรูปแบบ
            'refill'             => $refill,  // ← เช่นกัน
            'status'             => $statusText,
            'date_approve'       => $dateApprove,
            'emp_approve'        => $empApprove,
            'check'              => $checkBtn,
            'waiting'            => $waitingHtml,
            'cancel'             => $cancelHtml,
            'delete'             => $deleteHtml,
        ];
    }
}
