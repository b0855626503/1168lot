<?php

return [
    'web_unblocker' => [

        'log_enabled' => true,
        'sleep_enabled' => true,
        'outbound_enabled' => false,
        'row_per_page' => 10,

        'enabled' => false,
        'host' => 'dc.oxylabs.io',

        // พอร์ตหลัก (default ถ้าไม่ได้กำหนด array)
        'port' => 8001,

        // พอร์ตสำรองหลายตัว (จะเอาไปสุ่ม / หรือวนใช้ตาม strategy)
        'ports' => [8001, 8002, 8003, 8004, 8005],

        // วิธีเลือกพอร์ต: random หรือ round_robin
        'strategy' => 'round_robin',

        // ใส่ user/pass ของ Oxylabs ไว้ตรงนี้ได้เลยถ้าไม่อยากดึงจาก .env
        'user' => 'boatjr_KWkLH',
        'pass' => '8tgPFjS=TaTnFys',
        'ip_rotation' => [
            'enabled' => env('PROXY_IPROTATE_ENABLED', false),
            'url' => env('PROXY_IPROTATE_URL', null),  // เช่น http://66.42.50.240:7001/apix/reset_ip_secure?hash=cdc48d8c6910
            'cooldown_sec' => env('PROXY_IPROTATE_COOLDOWN', 120), // หน่วงระหว่างการหมุน (กัน spam)
        ],
    ],
];
