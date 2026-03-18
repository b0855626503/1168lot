<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ถ้าระบบคุณใช้ "email" เป็นตัวล็อกอิน ให้เปลี่ยนเป็น:
            // 'email' => ['required', 'string', 'email', 'max:191'],
            'user_name' => ['required', 'string', 'max:191'],

            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Normalize/clean ค่าก่อน validate:
     * - map login/email -> username (กรณีฟอร์มใช้ชื่อฟิลด์ต่างกัน)
     * - ตัดช่องว่างหัวท้าย + ลบอักขระมองไม่เห็น (ZWSP, NBSP, BOM)
     * - แปลง remember เป็น boolean
     */
    protected function prepareForValidation(): void
    {
        // รองรับหลายชื่อฟิลด์ฝั่งฟอร์ม: username / email / login
        $rawUsername = $this->input('user_name',
            $this->input('email',
                $this->input('login')));

        $cleanUsername = $this->cleanInvisibleAndTrim($rawUsername);

        // NOTE: ถ้าไม่ต้องการ trim password ให้คอมเมนต์บรรทัดนี้ออก
        $cleanPassword = $this->cleanInvisibleAndTrim($this->input('password'));

        // remember อาจเป็น "on"/"1"/true
        $remember = filter_var($this->input('remember'), FILTER_VALIDATE_BOOLEAN);

        $payload = [
            'user_name' => $cleanUsername,
            'password' => $cleanPassword,
            'remember' => $remember,
        ];

        // ถ้าฟอร์มส่งชื่ออื่นมาด้วย ให้ sync ค่าคืนกลับไป
        if ($this->has('email')) {
            $payload['email'] = $cleanUsername;
        }
        if ($this->has('login')) {
            $payload['login'] = $cleanUsername;
        }

        $this->merge($payload);
    }

    /**
     * ตัดช่องว่างหัวท้าย + ลบอักขระมองไม่เห็นยอดฮิต:
     * - \u200B-\u200D (ZWSP/ZWJ/ZWNJ), \uFEFF (BOM), \u00A0 (NBSP)
     */
    protected function cleanInvisibleAndTrim($value): ?string
    {
        if (!is_string($value)) {
            return $value;
        }

        // ลบอักขระมองไม่เห็น + NBSP
        $value = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00A0}]/u', '', $value);

        // แทนที่ whitespace แปลก ๆ ภายในด้วยช่องว่างปกติ (กัน copy/paste พิสดาร)
        $value = str_replace(["\r", "\n", "\t"], ' ', $value);

        // ตัดหัว-ท้าย
        return trim($value);
    }

    public function attributes(): array
    {
        return [
            'user_name' => 'ชื่อผู้ใช้',
            'password' => 'รหัสผ่าน',
        ];
    }

    public function messages(): array
    {
        return [
            'user_name.required' => 'กรุณากรอกชื่อผู้ใช้',
            'email.required'    => 'กรุณากรอกอีเมล',
            'email.email'       => 'รูปแบบอีเมลไม่ถูกต้อง',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
        ];
    }
}
