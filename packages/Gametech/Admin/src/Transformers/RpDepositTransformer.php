<?php

namespace Gametech\Admin\Transformers;

use Gametech\Payment\Contracts\BankPayment;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class RpDepositTransformer extends TransformerAbstract
{
    protected bool $canDelete = false;

    public function __construct()
    {
        $admin = auth()->guard('admin')->user();

        if ($admin) {
            $this->canDelete =
                (int) $admin->role_id === 1
                || (string) ($admin->superadmin ?? 'N') === 'Y';
        }
    }

    public function transform(BankPayment $model): array
    {
        // ----- code/enable ปุ่มสลับ
        $enable = (string) $model->enable === 'Y';
        $codeCell = (int) $model->code;
        if (! $enable) {
            $next = core()->flip($model->enable);
            $code = (int) $model->code;
            $css = $enable ? 'btn-success' : 'btn-danger';
            $icon = $enable ? '<i class="fa fa-check"></i>' : '<i class="fa fa-reply"></i>';
            $codeCell = '<button class="btn btn-xs icon-only '.$css.'" onclick="editdata('.$code.',\''.$next.'\',\'enable\')">'.$icon.'</button>';
        }

        // ----- relations (ต้อง eager load: member, admin, bank_account.bank)
        $bankAccount = $model->bank_account ?? null;
        $bank = $bankAccount?->bank ?? null;
        $member = $model->member ?? null;
        $admin = $model->admin ?? null;

        // ----- bank logo
        $bankHtml = '';
        if (($bank?->shortcode ?? null) && ($bank?->filepic ?? null)) {
            $bankHtml = core()->displayBank($bank->shortcode, $bank->filepic);
        }

        // ----- channel
        $channelText = (string) ($model->channel ?? '');
        $channelHtml = '<span class="text-long" data-toggle="tooltip" title="'.e($channelText).'">'
            .e(Str::limit($channelText, 10))
            .'</span>';

        // ----- amount
        $amountHtml = core()->textcolor(core()->currency((float) $model->value), 'text-success');

        // ----- fee (ได้จาก joinSub คำนวณแล้ว)
        $feeValue = is_numeric($model->fees ?? null) ? (float) $model->fees : 0.0;
        $feeHtml = core()->currency($feeValue);

        // ----- emp_name
        $empName = $model->emp_topup === 0
            ? ($model->create_by ?: $model->topup_by)
            : ($admin?->user_name ?? '');

        $code = (int) $model->code;
        $actionBtn = '';
        if ($this->canDelete) {
            $actionBtn = '
    <button
        type="button"
        class="btn btn-xs btn-danger"
        onclick="delModal('.$code.')"
        title="ลบรายการนี้"
    >
        <i class="fa fa-trash"></i> ลบ
    </button>
    
';
        }

        return [
            'code' => $codeCell,
            'bank_raw' => $bank->name_th ?? '',
            'money' => (float) $model->value,
            'bank' => $bankHtml,
            'acc_no' => $bankAccount->acc_no ?? '',
            'date' => $model->bank_time?->format('d/m/y H:i:s') ?? '',
            'date_create' => $model->date_create?->format('d/m/y H:i:s') ?? '',
            'date_approve' => $model->date_approve?->format('d/m/y H:i:s') ?? '',
            'date_regis' => $model->member?->date_regis?->format('d/m/y') ?? '',
            'channel' => $channelHtml,
            'detail' => (string) ($model->detail ?? ''),
            'amount' => $amountHtml,

            // ★★ ค่าธรรมเนียมที่คำนวณแล้ว ★★
            'fees' => $feeHtml,
            'pro_id'           => $model->promotion?->name_th ?? '',
            'pro_amount'           => $model->pro_amount ?? '',
            'member_name' => $member->name ?? '',
            'user_name' => $member->user_name ?? '',
            'remark' => (string) ($model->remark_admin ?? ''),
            'emp_name' => $empName,
            'ip' => $model->emp_topup === 0 ? '127.0.0.1' : (string) ($model->ip_admin ?? ''),
            'action' => $actionBtn,
        ];
    }
}
