<?php

return [
    'min_deposit' => env('SMKPAY_MIN_DEPOSIT', 100),
    'min_withdraw' => env('SMKPAY_MIN_WITHDRAW', 200),

    'api_url' => env('SMKPAY_API_URL', 'https://example.com'),
    'api_key' => env('SMKPAY_API_KEY', null),
    'secret_key' => env('SMKPAY_SECRET_KEY', null),

    // log (SmkPay library ใช้)
    'debug_log' => env('SMKPAY_DEBUG_LOG', true),
    'log_channel' => env('SMKPAY_LOG_CHANNEL', 'smkpay_api'),

    // ธนาคาร “ของเว็บเรา” ที่ใช้บันทึกลง check_case.bank_code (เหมือน onpay ใช้ 310)
    'system_bank_code' => env('SMKPAY_SYSTEM_BANK_CODE', 313),

    // view หน้าแสดง QR (ถ้ามีของเดิมอยู่แล้ว ใช้อันเดิมได้เลย)
    'deposit_view' => env('SMKPAY_DEPOSIT_VIEW', 'topup.box.onpay_new'),
];
