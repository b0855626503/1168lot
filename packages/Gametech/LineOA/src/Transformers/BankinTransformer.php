<?php

namespace Gametech\LineOA\Transformers;

use Gametech\Payment\Contracts\BankPayment;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class BankinTransformer extends TransformerAbstract
{
    /** ปุ่มสั้นๆ ไวๆ */
    protected function btn(string $class, string $iconHtml, string $onClick): string
    {
        return '<button class="btn '.$class.' btn-xs icon-only" onclick="'.$onClick.'">'.$iconHtml.'</button>';
    }

    /** ปุ่มยืนยัน/เติม (แทน blade: datatables_refill) */
    protected function buildConfirmHtml(int $code, int $status, string $txid, string $autocheck): string
    {
        // เงื่อนไขจาก blade เดิม:
        // 1) status == 0 && txid == ''  -> ปุ่ม + เรียก editModal
        // 2) status == 0 && txid != '' && autocheck != 'W' -> ปุ่ม ✔ เรียก approveModal
        if ($status === 0 && $txid === '') {
            return $this->btn('btn-secondary', '<i class="fas fa-plus"></i>', "LineOaChatActions.edit({$code})");
        }
        if ($status === 0 && $txid !== '' && $autocheck !== 'W') {
            return $this->btn('btn-info', '<i class="fas fa-check"></i>', "LineOaChatActions.approve({$code})");
        }
        return '';
    }

    /** ปุ่มแก้ไข (แทน blade: datatables_edit) — เดาว่าเปิด modal แก้ไขรายการ */
    protected function buildEditHtml(int $code, int $status): string
    {
        // ส่วนใหญ่ระบบจะให้แก้ไขได้ตอนสถานะยัง 0
        if ($status === 0) {
            return $this->btn('btn-primary', '<i class="fas fa-pen"></i>', "LineOaChatActions.edit({$code})");
        }
        return '';
    }

    /** ปุ่มยกเลิก (แทน blade: datatables_clear) */
    protected function buildCancelHtml(int $code, int $status): string
    {
        if ($status === 0) {
            return $this->btn('btn-warning', '<i class="fas fa-times"></i>', "LineOaChatActions.cancel({$code})");
        }
        return '';
    }

    /** ปุ่มลบ (แทน blade: datatables_delete) */
    protected function buildDeleteHtml(int $code, int $status): string
    {
        if ($status === 0) {
            return $this->btn('btn-danger', '<i class="fas fa-trash"></i>', "LineOaChatActions.delete({$code})");
        }
        return '';
    }

    public function transform(BankPayment $model): array
    {
        $code      = (int) $model->code;
        $status    = (int) ($model->status ?? 0);
        $txid      = (string) ($model->txid ?? '');
        $autocheck = (string) ($model->autocheck ?? '');

        // โลโก้ธนาคารของบัญชีรับเงิน (bank_account->bank)
        static $bankCache = [];
        $bankHtml = '';
        if ($model->bank_account && $model->bank_account->bank) {
            $short = (string) $model->bank_account->bank->shortcode;
            $pic   = (string) $model->bank_account->bank->filepic;
            $key   = $short.'|'.$pic;
            if (!isset($bankCache[$key])) {
                $bankCache[$key] = core()->displayBank($short, $pic);
            }
            $bankHtml = $bankCache[$key];
        }

        // ช่องทาง + ผู้บันทึก
        $channelText = (string) ($model->channel ?? '');
        $channelHtml = e(Str::limit($channelText, 10)).' ('.e((string)($model->create_by ?? '')).')';

        // รายละเอียด + remark/auto
        $detailText = (string) ($model->detail ?? '');
        $codeNote   = ($model->remark_admin ?? '') !== ''
            ? (string) $model->remark_admin
            : (($model->autocheck ?? '') === 'Y' ? (string) ($model->user_create ?? '') : '');
        $detailHtml = e($detailText).' <code>'.e($codeNote).'</code>';

        return [
            'code'       => $code,
            'bankcode'   => $bankHtml,
            'acc_no'     => $model->bank_account->acc_no ?? '',
            'bank_time'  => $model->bank_time ? $model->bank_time->format('d/m/y H:i:s') : '',
            'user_name'  => $model->member->user_name ?? '',
            'channel'    => $channelHtml,
            'detail'     => $detailHtml,
            'value'      => '<span style="color:blue">'.(string) $model->value.'</span>',
            'date'       => $model->date_create ? $model->date_create->format('d/m/y H:i:s') : '',

            // ปุ่ม inline ทั้งหมด (ไม่ใช้ view()->render())
            'confirm'    => $this->buildConfirmHtml($code, $status, $txid, $autocheck),
            'edit'       => $this->buildEditHtml($code, $status),
            'cancel'     => $this->buildCancelHtml($code, $status),
            'delete'     => $this->buildDeleteHtml($code, $status),
        ];
    }
}
