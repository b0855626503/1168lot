<?php

return [
    [
        'key' => 'sms',
        'name' => 'Sms',
        'route' => 'admin.sms_campaign.index',
        'sort' => 85,
    ],
    [
        'key' => 'sms.sms_campaign',
        'name' => 'แคมเปญ SMS',
        'route' => 'admin.sms_campaign.index',
        'sort' => 1,
    ],
    [
        'key' => 'sms.sms_campaign.index',
        'name' => 'เห็นเมนู แคมเปญ SMS',
        'route' => 'admin.sms_campaign.index',
        'sort' => 1,
    ],
    [
        'key' => 'sms.sms_campaign.create',
        'name' => 'สิทธิ์ เพิ่มข้อมูล แคมเปญ SMS',
        'route' => 'admin.sms_campaign.create',
        'sort' => 2,
    ],
    [
        'key' => 'sms.sms_campaign.update',
        'name' => 'สิทธิ์ แก้ไขข้อมูล แคมเปญ SMS',
        'route' => 'admin.sms_campaign.update',
        'sort' => 3,
    ],
    [
        'key' => 'sms.sms_campaign.delete',
        'name' => 'สิทธิ์ ลบข้อมูล แคมเปญ SMS',
        'route' => 'admin.sms_campaign.delete',
        'sort' => 4,
    ],
    [
        'key' => 'sms.sms_logs',
        'name' => 'ประวัติการส่ง SMS',
        'route' => 'admin.sms_logs.index',
        'sort' => 2,
    ],
    [
        'key' => 'sms.sms_logs.index',
        'name' => 'เห็นเมนู ประวัติการส่ง SMS',
        'route' => 'admin.sms_logs.index',
        'sort' => 1,
    ],
];
