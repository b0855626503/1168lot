<?php

return [
    [
        'key'        => 'dashboard',
        'name'       => 'DashBoard',
        'route'      => 'admin.home.index',
        'sort'       => 1,
        'icon-class' => 'fa-tachometer-alt',
    ] , [
        'key'        => 'bank_in',
        'name'       => 'รายการ เงินเข้า',
        'route'      => 'admin.bank_in.index',
        'sort'       => 2,
        'icon-class' => 'sales-icon',
    ] , [
        'key'        => 'bank_out',
        'name'       => 'รายการ เงินออก',
        'route'      => 'admin.bank_out.index',
        'sort'       => 3,
        'icon-class' => 'sales-icon',
    ] , [
        'key'        => 'withdraw',
        'name'       => 'รายการ ถอนเงิน',
        'route'      => 'admin.withdraw.index',
        'sort'       => 4,
        'icon-class' => 'sales-icon',
    ] , [
        'key'        => 'withdraw_free',
        'name'       => 'รายการ ถอนเงิน [Free]',
        'route'      => 'admin.withdraw_free.index',
        'sort'       => 5,
        'icon-class' => 'sales-icon',
    ] , [
        'key'        => 'confirm_credit',
        'name'       => 'ยืนยันการโยกเงิน',
        'route'      => 'admin.confirm_credit.index',
        'sort'       => 6,
        'icon-class' => 'sales-icon',
    ] , [
        'key'        => 'payment',
        'name'       => 'ค่าใช้จ่าย',
        'route'      => 'admin.payment.index',
        'sort'       => 7,
        'icon-class' => 'sales-icon',
    ] , [
        'key'        => 'member',
        'name'       => 'สมาชิก',
        'route'      => 'admin.member.index',
        'sort'       => 8,
        'icon-class' => 'sales-icon',
    ] , [
        'key'        => 'cashback',
        'name'       => 'ฟรีเครดิต',
        'route'      => '',
        'sort'       => 9,
        'icon-class' => 'sales-icon',
    ] , [
        'key'        => 'cashback.member',
        'name'       => 'สมาชิก Credit Free',
        'route'      => 'admin.cashback.member.index',
        'sort'       => 1,
        'icon-class' => '',
    ]
];
