<?php

namespace Gametech\Sms\Support;

class PhoneNormalizer
{
    /**
     * Normalize Thai phone to E.164 (+66XXXXXXXXX)
     * - รองรับ input แบบ: 081-234-5678, 66812345678, +66812345678
     * - คืนค่า null ถ้า invalid
     */
    public static function toE164(?string $raw, string $defaultCountryCode = '66'): ?string
    {
        if (! $raw) {
            return null;
        }

        $s = trim($raw);

        // เอาเฉพาะตัวเลขกับ +
        $s = preg_replace('/[^\d\+]/', '', $s) ?: '';

        if ($s === '') {
            return null;
        }

        // แปลง 00 เป็น +
        if (str_starts_with($s, '00')) {
            $s = '+' . substr($s, 2);
        }

        // ถ้าเป็น + แล้ว
        if (str_starts_with($s, '+')) {
            $digits = preg_replace('/\D/', '', $s) ?: '';
            if ($digits === '') {
                return null;
            }
            return '+' . $digits;
        }

        // ถ้าเริ่มด้วย country code (เช่น 66...)
        if (str_starts_with($s, $defaultCountryCode)) {
            $digits = preg_replace('/\D/', '', $s) ?: '';
            return '+' . $digits;
        }

        // ถ้าเป็นเบอร์ไทยขึ้นต้น 0
        if (str_starts_with($s, '0')) {
            $digits = preg_replace('/\D/', '', $s) ?: '';
            $digits = ltrim($digits, '0'); // ตัด 0 นำหน้า
            if ($digits === '') {
                return null;
            }
            return '+' . $defaultCountryCode . $digits;
        }

        // fallback: treat as national without 0
        $digits = preg_replace('/\D/', '', $s) ?: '';
        if ($digits === '') {
            return null;
        }

        return '+' . $defaultCountryCode . $digits;
    }
}
