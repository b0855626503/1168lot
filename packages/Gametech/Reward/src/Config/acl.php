<?php

return [
    [
        'key' => 'reward',
        'name' => 'Rewards',
        'route' => 'admin.reward_list.index',
        'sort' => 86,
    ],
    [
        'key' => 'reward.reward_list',
        'name' => 'Rewards List',
        'route' => 'admin.reward_list.index',
        'sort' => 1,
    ],
    [
        'key' => 'reward.reward_list.index',
        'name' => 'เห็นเมนู Rewards List',
        'route' => 'admin.reward_list.index',
        'sort' => 1,
    ],
    [
        'key' => 'reward.reward_list.create',
        'name' => 'สิทธิ์ เพิ่มข้อมูล Rewards List',
        'route' => 'admin.reward_list.create',
        'sort' => 2,
    ],
    [
        'key' => 'reward.reward_list.update',
        'name' => 'สิทธิ์ แก้ไขข้อมูล Rewards List',
        'route' => 'admin.reward_list.update',
        'sort' => 3,
    ],
    [
        'key' => 'reward.reward_list.delete',
        'name' => 'สิทธิ์ ลบข้อมูล Rewards List',
        'route' => 'admin.reward_list.delete',
        'sort' => 4,
    ],
    [
        'key' => 'reward.reward_redemption',
        'name' => 'การแลก Rewards',
        'route' => 'admin.reward_redemption.index',
        'sort' => 1,
    ],
    [
        'key' => 'reward.reward_redemption.index',
        'name' => 'เห็นเมนู การแลก Rewards',
        'route' => 'admin.reward_redemption.index',
        'sort' => 1,
    ],
//    [
//        'key' => 'reward.reward_redemption.create',
//        'name' => 'สิทธิ์ เพิ่มข้อมูล การแลก Rewards',
//        'route' => 'admin.reward_redemption.create',
//        'sort' => 2,
//    ],
    [
        'key' => 'reward.reward_redemption.process',
        'name' => 'สิทธิ์ ดำเนินการ การแลก Rewards',
        'route' => 'admin.reward_redemption.process',
        'sort' => 2,
    ],
//    [
//        'key' => 'reward.reward_redemption.delete',
//        'name' => 'สิทธิ์ ลบข้อมูล การแลก Rewards',
//        'route' => 'admin.reward_redemption.delete',
//        'sort' => 4,
//    ],
];
