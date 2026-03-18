<?php

namespace Gametech\Admin\Transformers;

use Gametech\Payment\Contracts\Withdraw;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class RpWithdrawTransformer extends TransformerAbstract
{
    public function transform(Withdraw $model)
    {
        // status (1=อนุมัติ, 2=ไม่อนุมัติ, 0=รอดำเนินการ)
        // เพิ่ม 3 เพื่อรองรับ ambiguous_failure / HOLD review
        $status = [
            '0' => 'รอดำเนินการ',
            '1' => 'อนุมัติ',
            '2' => 'ไม่อนุมัติ',
            '3' => 'รอตรวจสอบ (HOLD)',
        ];

        // status_withdraw: เพิ่ม H/P เพื่อกัน key หาย (ระบบมีใช้)
        $status_wd = [
            'W' => 'ไม่ได้เลือก Payment Gateway',
            'A' => 'ระหว่างดำเนินการ',
            'P' => 'กำลังประมวลผล',
            'H' => 'รอตรวจสอบ (HOLD)',
            'C' => 'โอนให้แล้ว',
            'R' => 'คืนยอดให้ลุกค้าแล้ว',
        ];

        $model->status_withdraw = ($model->status_withdraw ?? 'W');

        $bankDisplay = '';
        try {
            $bankDisplay = ($model->bank ? core()->displayBank($model->bank->shortcode, $model->bank->filepic) : '');
        } catch (\Throwable $e) {
            $bankDisplay = '';
        }

        $accountDisplay = '';
        try {
            if ($model->bank_tran && $model->bank_tran->bank) {
                $accountDisplay = core()->displayBank($model->bank_tran->bank->shortcode, $model->bank_tran->bank->filepic) . ' ' . $model->bank_tran->acc_no;
            }
        } catch (\Throwable $e) {
            $accountDisplay = '';
        }

        $dateRecord = '';
        try {
            $dateRecord = ($model->date_record ? $model->date_record->format('d/m/y') : '');
        } catch (\Throwable $e) {
            $dateRecord = '';
        }

        $dateApprove = '';
        try {
            $dateBank = ($model->date_bank ? $model->date_bank->format('d/m/y') : '');
            $timeBank = (string) ($model->time_bank ?? '');
            $dateApprove = trim($dateBank . ' ' . $timeBank);
        } catch (\Throwable $e) {
            $dateApprove = '';
        }

        $dateCreate = '';
        try {
            $dateCreate = ($model->date_create ? $model->date_create->format('d/m/y H:i:s') : '');
        } catch (\Throwable $e) {
            $dateCreate = '';
        }

        $dateUpdate = '';
        try {
            $dateUpdate = ($model->date_update ? $model->date_update->format('d/m/y H:i:s') : '');
        } catch (\Throwable $e) {
            $dateUpdate = '';
        }

        $statusKey = (string) ($model->status ?? '0');
        $statusWithdrawKey = (string) ($model->status_withdraw ?? 'W');

        return [
            'code' => (int) $model->code,
            'bank' => $bankDisplay,
            'account_code' => $accountDisplay,
            'date' => trim($dateRecord . ' ' . (string) ($model->timedept ?? '')),
            'time' => (string) ($model->timedept ?? ''),
            'txid' => (string) ($model->txid ?? ''),
            'pro_name' => (string) ($model->pro_name ?? ''),
            'balance' => core()->textcolor(core()->currency($model->balance), 'text-blue'),
            'amount_limit_rate' => core()->textcolor(core()->currency($model->amount_limit_rate), 'text-danger'),
            'amount' => core()->textcolor(core()->currency($model->amount), 'text-danger'),
            'member_name' => (is_null($model->member) ? '' : (string) ($model->member->name ?? '')),
            'user_name' => (is_null($model->member) ? '' : (string) ($model->member->user_name ?? '')),
            'game_user' => (is_null($model->user) ? '' : (string) ($model->user->user_name ?? '')),
            'status' => $status[$statusKey] ?? ('ไม่ทราบสถานะ (' . $statusKey . ')'),
            'date_approve' => $dateApprove,
            'status_withdraw' => $status_wd[$statusWithdrawKey] ?? ('ไม่ทราบสถานะถอน (' . $statusWithdrawKey . ')'),
            'remark' => 'Admin : ' . (string) ($model->remark_admin ?? ''),
            'emp_name' => ($model->emp_approve === 0 ? '' : (is_null($model->admin) ? '' : (string) ($model->admin->name ?? ''))),
            'date_create' => $dateCreate,
            'date_update' => $dateUpdate,
            'ip' => 'User : ' . (string) ($model->ip ?? '') . '<br>Admin : ' . (string) ($model->ip_admin ?? ''),
//            'action' => view('admin::module.rp_withdraw.datatables_actions', ['code' => $model->code])->render(),
        ];
    }
}