<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default SMS Provider
    |--------------------------------------------------------------------------
    |
    | ใช้เป็น provider หลักในการส่ง SMS
    | (DLR จะผูกกับ provider ที่ใช้ตอนส่ง)
    |
    */

    'default' => env('SMS_PROVIDER', 'vonage'),

    /*
    |--------------------------------------------------------------------------
    | SMS Providers
    |--------------------------------------------------------------------------
    |
    | กำหนด provider แต่ละเจ้า
    | - driver      : key สำหรับ logic / normalizer
    | - credentials : ใช้ตอนส่ง
    | - webhooks    : ใช้รับ DLR / inbound
    |
    */

    'providers' => [

        'vonage' => [
            'driver' => 'vonage',

            'credentials' => [
                'api_key'    => env('VONAGE_API_KEY'),
                'api_secret' => env('VONAGE_API_SECRET'),
                'from'       => env('VONAGE_SMS_FROM'),
            ],

            'webhooks' => [
                'dlr' => [
                    // ตั้งให้ตรงกับ route จริง: /api/sms/webhook/vonage/dlr
                    // แนะนำให้ใส่ token ใน URL ด้วย เช่น .../dlr?token=xxxx
                    'url'    => env('VONAGE_DLR_URL'),

                    // แนะนำ POST-JSON เพื่อความเสถียร (Vonage UI มีให้เลือก)
                    // ถ้าคุณยังไม่ได้เปลี่ยนใน UI ให้คง GET ไว้ก็ได้
                    'method' => env('VONAGE_DLR_METHOD', 'POST-JSON'),

                    // Shared token (ชัวร์สุด): middleware จะตรวจจาก ?token=... หรือ header X-Webhook-Token
                    'token'  => env('SMS_WEBHOOK_TOKEN'),

                    // Signature: ปิดไว้ก่อนจนกว่าจะเห็น Vonage ส่ง sig/timestamp มาจริง
                    'signature' => [
                        'enabled' => env('VONAGE_SIGNATURE_ENABLED', false),
                        'secret'  => env('VONAGE_SIGNATURE_SECRET'),
                        'method'  => env('VONAGE_SIGNATURE_METHOD', 'md5hash'),
                        'timestamp_tolerance' => (int) env('VONAGE_SIGNATURE_TOLERANCE', 300),
                    ],
                ],

                'inbound' => [
                    'enabled' => true,
                ],
            ],

            'options' => [
                'supports_dlr' => true,
                'supports_unicode' => true,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Example: Twilio (ยังไม่ implement ก็ใส่โครงไว้ได้)
        |--------------------------------------------------------------------------
        */
        'twilio' => [
            'driver' => 'twilio',

            'credentials' => [
                'account_sid' => env('TWILIO_ACCOUNT_SID'),
                'auth_token'  => env('TWILIO_AUTH_TOKEN'),
                'from'        => env('TWILIO_SMS_FROM'),
            ],

            'webhooks' => [
                'dlr' => [
                    'method' => 'POST',
                    // future:
                    // 'token' => env('SMS_WEBHOOK_TOKEN'),
                    // 'signature' => [...]
                ],
            ],

            'options' => [
                'supports_dlr' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery Receipt (DLR) Mapping
    |--------------------------------------------------------------------------
    |
    | mapping สถานะจาก provider → status กลางของระบบ
    | ใช้โดย SmsRecipient::applyDeliveryReceipt()
    |
    */

    'dlr_status_map' => [

        'vonage' => [
            'delivered'   => 'delivered',
            'accepted'    => 'sent',
            'buffered'    => 'sent',
            'failed'      => 'failed',
            'rejected'    => 'failed',
            'expired'     => 'failed',
            'undelivered' => 'failed',
        ],

        'twilio' => [
            'delivered'   => 'delivered',
            'sent'        => 'sent',
            'failed'      => 'failed',
            'undelivered' => 'failed',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Campaign Auto Finalize
    |--------------------------------------------------------------------------
    */
    'campaign' => [
        'expire_after_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Import settings
    |--------------------------------------------------------------------------
    */
    'import' => [
        'max_preview_rows' => env('SMS_IMPORT_PREVIEW_ROWS', 20),
        'phone_column_candidates' => ['phone', 'tel', 'mobile', 'msisdn', 'เบอร์', 'เบอร์โทร', 'โทรศัพท์'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Members source settings
    |--------------------------------------------------------------------------
    */
    'members' => [
        'table' => env('SMS_MEMBERS_TABLE', 'members'),
        'pk' => env('SMS_MEMBERS_PK', 'code'),
        'tel_column' => env('SMS_MEMBERS_TEL_COLUMN', 'tel'),
        'consent_column' => env('SMS_MEMBERS_CONSENT_COLUMN', 'marketing_sms_consent'),
        'consent_yes_value' => env('SMS_MEMBERS_CONSENT_YES', 'Y'),
    ],

];
