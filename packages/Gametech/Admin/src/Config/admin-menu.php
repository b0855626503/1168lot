<?php

return [
    [
        'key' => 'dashboard',
        'name' => 'DashBoard',
        'route' => 'admin.home.index',
        'sort' => 1,
        'icon-class' => 'fa-tachometer-alt',
        'badge' => 0,
        'badge-color' => 'badge-info',
        'status' => 1
    ], [
        'key' => 'bank_in',
        'name' => 'รายการ เงินเข้า',
        'route' => 'admin.bank_in.index',
        'sort' => 2,
        'icon-class' => 'fa-arrow-circle-left',
        'badge' => 1,
        'badge-color' => 'badge-warning',
        'status' => 1
    ], [
        'key' => 'bank_out',
        'name' => 'รายการ เงินออก',
        'route' => 'admin.bank_out.index',
        'sort' => 3,
        'icon-class' => 'fa-arrow-circle-right',
        'badge' => 1,
        'badge-color' => 'badge-warning',
        'status' => 1
    ], [
        'key' => 'withdraw',
        'name' => 'รายการ ถอนเงิน',
        'route' => 'admin.withdraw.index',
        'sort' => 4,
        'icon-class' => 'fa-wallet',
        'badge' => 1,
        'badge-color' => 'badge-warning',
        'status' => 1
    ], [
        'key' => 'withdraw_free',
        'name' => 'รายการ ถอนเงิน [Free]',
        'route' => 'admin.withdraw_free.index',
        'sort' => 5,
        'icon-class' => 'fa-credit-card',
        'badge' => 1,
        'badge-color' => 'badge-warning',
        'status' => 1
    ], [
        'key' => 'confirm_wallet',
        'name' => 'รออนุมัติการโยกเงิน',
        'route' => 'admin.confirm_wallet.index',
        'sort' => 6,
        'icon-class' => 'fa-clock',
        'badge' => 1,
        'badge-color' => 'badge-warning',
        'status' => 1
    ], [
        'key' => 'fix',
        'name' => 'การแก้ไขเบื้องต้น',
        'route' => 'admin.fix.index',
        'sort' => 8,
        'icon-class' => 'fa-tachometer-alt',
        'badge' => 0,
        'badge-color' => 'badge-purple',
        'status' => 0
    ], [
        'key' => 'payment',
        'name' => 'ค่าใช้จ่าย',
        'route' => 'admin.payment.index',
        'sort' => 10,
        'icon-class' => 'fa-cubes',
        'badge' => 0,
        'badge-color' => 'badge-purple',
        'status' => 1
    ], [
        'key' => 'member_confirm',
        'name' => 'สมาชิกรอยืนยัน',
        'route' => 'admin.member_confirm.index',
        'sort' => 15,
        'icon-class' => 'fa-user',
        'badge' => 1,
        'badge-color' => 'badge-warning',
        'status' => 1
    ], [
        'key' => 'wallet',
        'name' => 'Members',
        'route' => 'admin.member.index',
        'sort' => 20,
        'icon-class' => 'fa-users',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'wallet.member',
        'name' => 'สมาชิก (Wallet)',
        'route' => 'admin.member.index',
        'sort' => 1,
        'icon-class' => 'fa-users',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'wallet.rp_wallet',
        'name' => 'รายงาน เพิ่ม-ลด (Wallet)',
        'route' => 'admin.rp_wallet.index',
        'sort' => 2,
        'icon-class' => 'fa-users',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'wallet.rp_bill',
        'name' => 'รายงาน โยกเงิน (Wallet)',
        'route' => 'admin.rp_bill.index',
        'sort' => 3,
        'icon-class' => 'fa-users',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'wallet.rp_deposit',
        'name' => 'รายงาน ฝากเงิน (Wallet)',
        'route' => 'admin.rp_deposit.index',
        'sort' => 4,
        'icon-class' => 'fa-users',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'wallet.rp_withdraw',
        'name' => 'รายงาน ถอนเงิน (Wallet)',
        'route' => 'admin.rp_withdraw.index',
        'sort' => 5,
        'icon-class' => 'fa-users',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'wallet.rp_setpoint',
        'name' => 'รายงาน เพิ่ม-ลด (Point)',
        'route' => 'admin.rp_setpoint.index',
        'sort' => 6,
        'icon-class' => 'fa-users',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'wallet.rp_setdiamond',
        'name' => 'รายงาน เพิ่ม-ลด (Diamond)',
        'route' => 'admin.rp_setdiamond.index',
        'sort' => 7,
        'icon-class' => 'fa-users',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'credit',
        'name' => 'Members Cashback',
        'route' => 'admin.member_free.index',
        'sort' => 30,
        'icon-class' => 'fa-dollar-sign',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'credit.member_free',
        'name' => 'สมาชิก (Credit)',
        'route' => 'admin.member_free.index',
        'sort' => 1,
        'icon-class' => 'fa-users',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'credit.rp_credit',
        'name' => 'รายงาน เพิ่ม-ลด (Credit)',
        'route' => 'admin.rp_credit.index',
        'sort' => 2,
        'icon-class' => 'fa-users',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'credit.rp_bill_free',
        'name' => 'รายงาน โยกเงิน (Credit)',
        'route' => 'admin.rp_bill_free.index',
        'sort' => 3,
        'icon-class' => 'fa-users',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'credit.rp_withdraw_free',
        'name' => 'รายงาน ถอนเงิน (Credit)',
        'route' => 'admin.rp_withdraw_free.index',
        'sort' => 4,
        'icon-class' => 'fa-users',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'credit.rp_log_cashback',
        'name' => 'รายงาน เครดิตเงินคืน',
        'route' => 'admin.rp_log_cashback.index',
        'sort' => 5,
        'icon-class' => 'fa-users',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'credit.rp_log_ic',
        'name' => 'รายงาน หุ้นส่วน IC',
        'route' => 'admin.rp_log_ic.index',
        'sort' => 6,
        'icon-class' => 'fa-users',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mop',
        'name' => 'รายงาน (กิจกรรม)',
        'route' => 'admin.rp_reward_point.index',
        'sort' => 40,
        'icon-class' => 'fa-flag-checkered',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mop.rp_reward_point',
        'name' => 'Point Reward',
        'route' => 'admin.rp_reward_point.index',
        'sort' => 1,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mop.rp_cashback',
        'name' => 'Cashback',
        'route' => 'admin.rp_cashback.index',
        'sort' => 2,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mop.rp_member_ic',
        'name' => 'Member IC',
        'route' => 'admin.rp_member_ic.index',
        'sort' => 3,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mop.rp_top_promotion',
        'name' => 'โปรยอดนิยม',
        'route' => 'admin.rp_top_promotion.index',
        'sort' => 4,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mep',
        'name' => 'รายงานสมาชิก',
        'route' => 'admin.rp_billturn.index',
        'sort' => 50,
        'icon-class' => 'fa-address-book',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mep.rp_billturn',
        'name' => 'รายการเทรินโปร',
        'route' => 'admin.rp_billturn.index',
        'sort' => 1,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mep.rp_spin',
        'name' => 'การหมุนวงล้อ',
        'route' => 'admin.rp_spin.index',
        'sort' => 2,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mep.rp_sponsor',
        'name' => 'แนะนำเพื่อน',
        'route' => 'admin.rp_sponsor.index',
        'sort' => 3,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mep.rp_member_ref',
        'name' => 'ที่มาการสมัคร',
        'route' => 'admin.rp_member_ref.index',
        'sort' => 4,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mep.rp_online_behavior',
        'name' => 'Online Behavior',
        'route' => 'admin.rp_online_behavior.index',
        'sort' => 5,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mep.rp_user_log',
        'name' => 'Activity Log',
        'route' => 'admin.rp_user_log.index',
        'sort' => 6,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mon',
        'name' => 'รายงานการเงิน',
        'route' => 'admin.rp_alllog.index',
        'sort' => 60,
        'icon-class' => 'fa-chart-line',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mon.rp_alllog',
        'name' => 'All Log',
        'route' => 'admin.rp_alllog.index',
        'sort' => 1,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mon.rp_sum_game',
        'name' => 'สรุปยอดแต่ละเกมส์',
        'route' => 'admin.rp_sum_game.index',
        'sort' => 2,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mon.rp_sum_stat',
        'name' => 'สรุปยอดรายเดือน',
        'route' => 'admin.rp_sum_stat.index',
        'sort' => 3,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mon.rp_sum_payment',
        'name' => 'สรุปยอดค่าใช้จ่าย',
        'route' => 'admin.rp_sum_payment.index',
        'sort' => 4,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mon.rp_top_payment',
        'name' => 'ฝากถอน 100 อันดับ',
        'route' => 'admin.rp_top_payment.index',
        'sort' => 5,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mon.rp_no_refill',
        'name' => 'สมาชิกไม่เติมเงิน',
        'route' => 'admin.rp_no_refill.index',
        'sort' => 6,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'mon.rp_summary',
        'name' => 'สรุปทั้งหมด',
        'route' => 'admin.rp_summary.index',
        'sort' => 7,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'ats',
        'name' => 'ตั้งค่าบัญชี',
        'route' => 'admin.bank_account_in.index',
        'sort' => 70,
        'icon-class' => 'fa-university',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'ats.bank_account_in',
        'name' => 'บัญชีรับเข้า',
        'route' => 'admin.bank_account_in.index',
        'sort' => 1,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'ats.bank_account_out',
        'name' => 'บัญชีถอนออก',
        'route' => 'admin.bank_account_out.index',
        'sort' => 2,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'top',
        'name' => 'เกมส์ & โปรโมชั่น',
        'route' => 'admin.game.index',
        'sort' => 80,
        'icon-class' => 'fa-gamepad',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'top.game',
        'name' => 'เกมส์',
        'route' => 'admin.game.index',
        'sort' => 1,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'top.batch_user',
        'name' => 'Batch User',
        'route' => 'admin.batch_user.index',
        'sort' => 2,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'top.promotion',
        'name' => 'โปรโมชั่น (ระบบ)',
        'route' => 'admin.promotion.index',
        'sort' => 3,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'top.pro_content',
        'name' => 'โปรโมชั่น (เพิ่มเติม)',
        'route' => 'admin.pro_content.index',
        'sort' => 4,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'st',
        'name' => 'ตั้งค่า ระบบ',
        'route' => 'admin.setting.index',
        'sort' => 90,
        'icon-class' => 'fa-cog',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'st.setting',
        'name' => 'ค่าพื้นฐานเว็บไซต์',
        'route' => 'admin.setting.index',
        'sort' => 1,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'st.faq',
        'name' => 'คู่มือ',
        'route' => 'admin.faq.index',
        'sort' => 2,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'st.refer',
        'name' => 'แหล่งที่มาการสมัคร',
        'route' => 'admin.refer.index',
        'sort' => 3,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'st.bank',
        'name' => 'ธนาคาร',
        'route' => 'admin.bank.index',
        'sort' => 4,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'st.spin',
        'name' => 'วงล้อมหาสนุก',
        'route' => 'admin.spin.index',
        'sort' => 5,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'st.reward',
        'name' => 'ตั้งค่าของรางวัล',
        'route' => 'admin.reward.index',
        'sort' => 6,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'st.notice',
        'name' => 'ตั้งค่าข้อความวิ่ง',
        'route' => 'admin.notice.index',
        'sort' => 7,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'st.notice_new',
        'name' => 'ตั้งค่าประกาศ',
        'route' => 'admin.notice_new.index',
        'sort' => 8,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'st.coupon',
        'name' => 'ตั้งค่าคูปอง',
        'route' => 'admin.coupon.index',
        'sort' => 9,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'st.slide',
        'name' => 'ตั้งค่า สไลด์',
        'route' => 'admin.slide.index',
        'sort' => 9,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'dev',
        'name' => 'Admin Zone',
        'route' => 'admin.employees.index',
        'sort' => 100,
        'icon-class' => 'fa-cog',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'dev.employees',
        'name' => 'ผู้ใช้งานระบบ',
        'route' => 'admin.employees.index',
        'sort' => 1,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'dev.roles',
        'name' => 'สิทธิ์ใช้งานระบบ',
        'route' => 'admin.roles.index',
        'sort' => 2,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'dev.rp_staff_log',
        'name' => 'Staff Activity Log',
        'route' => 'admin.rp_staff_log.index',
        'sort' => 3,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ], [
        'key' => 'dev.rp_log',
        'name' => 'Log',
        'route' => 'admin.rp_log.index',
        'sort' => 4,
        'icon-class' => '',
        'badge' => 0,
        'badge-color' => 'badge-primary',
        'status' => 1
    ]
];
