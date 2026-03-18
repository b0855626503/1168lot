<?php

namespace Gametech\LineOA\Support;

use Illuminate\Support\Str;

class UrlHelper
{
    /**
     * คืน URL ทางเข้าเล่นของระบบ
     * - ลอง config('line_oa.play_url')
     * - ถ้าไม่มี → config('app.play_url')
     * - ถ้าไม่มี → config('app.url')
     * - auto append ?openExternalBrowser=1
     */
    public static function playUrl(): string
    {
        $url = config('line_oa.play_url')
            ?? config('app.play_url')
            ?? config('app.url');

        return static::addOpenExternal($url);
    }

    /**
     * คืน URL login ให้สมาชิก (ใช้ในปุ่ม “สมัคร”, “เข้าเล่น”, ฯลฯ)
     * - ใช้ route('customer.session.index') ถ้ามี
     * - ถ้า route ไม่มี → fallback = /login
     * - auto append ?openExternalBrowser=1
     */
    public static function loginUrl(): string
    {
        // พยายามใช้ route ชื่อ customer.session.index
        if (function_exists('route')) {
            try {
                $url = route('customer.session.index');
                return static::addOpenExternal($url);
            } catch (\Throwable $e) {
                // ถ้า route ไม่มี ก็ไป fallback
            }
        }

        // fallback
        $url = url('/login');
        return static::addOpenExternal($url);
    }

    /**
     * บังคับเปิด browser ภายนอกใน LINE
     *
     * ตัวอย่าง:
     *   /login → /login?openExternalBrowser=1
     *   /login?ref=abc → /login?ref=abc&openExternalBrowser=1
     */
    public static function addOpenExternal(string $url): string
    {
        return $url . (Str::contains($url, '?') ? '&' : '?') . 'openExternalBrowser=1';
    }

    /**
     * สร้างลิงก์พร้อมพารามิเตอร์ (option เสริม)
     */
    public static function withParams(string $url, array $params = []): string
    {
        $query = http_build_query($params);
        return $url . (Str::contains($url, '?') ? '&' : '?') . $query;
    }
}
