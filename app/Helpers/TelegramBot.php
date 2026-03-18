<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\Http;

class TelegramBot
{
    /**
     * ส่งข้อความไปยัง API บอทกลาง
     *
     * @param string $message ข้อความที่จะส่ง
     * @param string|null $chatId (optional) ระบุ chat_id ถ้าไม่ระบุจะใช้ค่า default จาก config
     * @return bool true ถ้าส่งสำเร็จ
     *
     * @throws Exception
     */

    public static function Send(string $action, string $message, array $options = []): bool
    {
        $domain = config('app.user_domain_url');
        $url = 'https://telegram.168csn.com/api/' . $action;

        // เตรียม payload
        $payload = array_merge([
            'domain' => $domain,
            'message' => $message,
        ], $options);

        // เรียก API บอทกลาง
        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->post($url, $payload);

        return $response->successful();
    }
}
