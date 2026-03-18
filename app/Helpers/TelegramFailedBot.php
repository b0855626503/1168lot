<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class TelegramFailedBot
{
    /**
     * ส่งข้อความไปยัง API บอทกลาง
     *
     * @param string $message ข้อความที่จะส่ง
     * @param string|null $chatId (optional) ระบุ chat_id ถ้าไม่ระบุจะใช้ค่า default จาก config
     * @return bool true ถ้าส่งสำเร็จ
     *
     * @throws \Exception
     */
    public static function Send(string $action, string $message): bool
    {
        $name = config('app.name');
        $domain = 'failed.com';
        $url = 'https://telegram.168csn.com/api/' . $action;

        // เตรียม payload
        $payload = [
            'domain' => $domain,
            'message' => $name . ' - ' . $message,
        ];

        // เรียก API บอทกลาง
        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->post($url, $payload);

        // คืนค่า true ถ้าส่งสำเร็จ (status code 2xx)
        return $response->successful();
    }
}
