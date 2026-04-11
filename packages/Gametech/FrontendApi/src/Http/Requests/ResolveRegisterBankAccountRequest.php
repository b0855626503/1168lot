<?php

namespace Gametech\FrontendApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class ResolveRegisterBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bank' => ['bail', 'required', 'integer'],
            'acc_no' => [
                'bail',
                'required',
                'digits_between:1,14',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $bankCode = (int) $this->input('bank');
                    $accountNumber = (string) $value;

                    if (
                        DB::getSchemaBuilder()->hasTable('members')
                        && DB::getSchemaBuilder()->hasColumn('members', 'acc_no')
                        && DB::getSchemaBuilder()->hasColumn('members', 'bank_code')
                    ) {
                        $exists = DB::table('members')
                            ->where('acc_no', $accountNumber)
                            ->where('bank_code', $bankCode)
                            ->exists();

                        if ($exists) {
                            $fail('เลขที่บัญชีนี้ถูกใช้งานแล้วในระบบสมาชิก');

                            return;
                        }
                    }

                    if (
                        DB::getSchemaBuilder()->hasTable('banks_account')
                        && DB::getSchemaBuilder()->hasColumn('banks_account', 'acc_no')
                        && DB::getSchemaBuilder()->hasColumn('banks_account', 'banks')
                    ) {
                        $exists = DB::table('banks_account')
                            ->where('acc_no', $accountNumber)
                            ->where('banks', $bankCode)
                            ->exists();

                        if ($exists) {
                            $fail('เลขที่บัญชีนี้ถูกใช้งานแล้วในระบบบัญชีธนาคารภายใน');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bank.required' => 'กรุณาระบุธนาคาร',
            'bank.integer' => 'รหัสธนาคารไม่ถูกต้อง',
            'acc_no.required' => 'กรุณาระบุเลขบัญชี',
            'acc_no.digits_between' => 'เลขบัญชีต้องเป็นตัวเลขความยาว 1-14 หลัก',
        ];
    }
}
