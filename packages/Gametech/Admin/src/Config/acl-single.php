<?php

return [
    [
        'key' => 'dashboard',
        'name' => 'DashBoard',
        'route' => 'admin.home.index',
        'sort' => 1,
    ], [
        'key' => 'dashboard.deposit',
        'name' => 'สิทธิ์ เห็นข้อมูลยอดฝาก',
        'route' => '',
        'sort' => 1,
    ], [
        'key' => 'dashboard.withdraw',
        'name' => 'สิทธิ์ เห็นข้อมูลยอดถอน',
        'route' => '',
        'sort' => 2,
    ], [
        'key' => 'dashboard.bonus',
        'name' => 'สิทธิ์ เห็นข้อมูลยอดโบนัส',
        'route' => '',
        'sort' => 3,
    ], [
        'key' => 'dashboard.balance',
        'name' => 'สิทธิ์ เห็นข้อมูลยอดคงเหลือ',
        'route' => '',
        'sort' => 4,
    ], [
        'key' => 'dashboard.deposit_wait',
        'name' => 'สิทธิ์ เห็นข้อมูลยอดฝาก (มีปัญหา)',
        'route' => '',
        'sort' => 5,
    ], [
        'key' => 'dashboard.setdeposit',
        'name' => 'สิทธิ์ เห็นข้อมูลทีมงานเพิ่ม ยอดเงิน',
        'route' => '',
        'sort' => 6,
    ], [
        'key' => 'dashboard.setwithdraw',
        'name' => 'สิทธิ์ เห็นข้อมูลทีมงานลด ยอดเงิน',
        'route' => '',
        'sort' => 7,
    ], [
        'key' => 'dashboard.income',
        'name' => 'สิทธิ์ เห็นข้อมูลรายได้',
        'route' => '',
        'sort' => 8,
    ], [
        'key' => 'dashboard.topup',
        'name' => 'สิทธิ์ เห็นข้อมูลเติมเงิน',
        'route' => '',
        'sort' => 9,
    ], [
        'key' => 'dashboard.regis',
        'name' => 'สิทธิ์ เห็นข้อมูลสมาชิกใหม่',
        'route' => '',
        'sort' => 10,
    ], [
        'key' => 'dashboard.bankin',
        'name' => 'สิทธิ์ เห็นข้อมูลบัญชีเงินเข้า',
        'route' => '',
        'sort' => 11,
    ], [
        'key' => 'dashboard.bankout',
        'name' => 'สิทธิ์ เห็นข้อมูลบัญชีเงินออก',
        'route' => '',
        'sort' => 12,
    ], [
        'key' => 'dashboard.register-today',
        'name' => 'สิทธิ์ เห็นสมัครใหม่วันนี้',
        'route' => '',
        'sort' => 13,
    ], [
        'key' => 'dashboard.register-deposit',
        'name' => 'สิทธิ์ เห็นสมัครฝากวันนี้',
        'route' => '',
        'sort' => 14,

    ], [
        'key' => 'dashboard.register-not-deposit',
        'name' => 'สิทธิ์ เห็นสมัคร ไม่ฝากวันนี้',
        'route' => '',
        'sort' => 15,
    ], [
        'key' => 'dashboard.register-all-deposit',
        'name' => 'สิทธิ์ เห็นสมัครก่่อนนี้  ฝาก',
        'route' => '',
        'sort' => 16,
    ], [
        'key' => 'dashboard.index',
        'name' => 'สิทธิ์ เข้าหน้าแดชบอด',
        'route' => '',
        'sort' => 17,
    ], [
        'key' => 'bank_in',
        'name' => 'รายการ เงินเข้า',
        'route' => 'admin.bank_in.index',
        'sort' => 2,
    ], [
        'key' => 'bank_in.update',
        'name' => 'สิทธิ์ เติมเงิน รายการ เงินเข้า',
        'route' => 'admin.bank_in.update',
        'sort' => 1,
    ], [
        'key' => 'bank_in.clear',
        'name' => 'สิทธิ์ ปฏิเสธ รายการ เงินเข้า',
        'route' => 'admin.bank_in.clear',
        'sort' => 2,
    ], [
        'key' => 'bank_in.delete',
        'name' => 'สิทธิ์ ลบ รายการ เงินเข้า',
        'route' => 'admin.bank_in.delete',
        'sort' => 3,
    ], [
        'key' => 'bank_in_old',
        'name' => 'รายการฝากค้าง',
        'route' => 'admin.bank_in_old.index',
        'sort' => 3,
    ], [
        'key' => 'bank_in_old.update',
        'name' => 'สิทธิ์ เติมเงิน รายการฝากค้าง',
        'route' => 'admin.bank_in_old.update',
        'sort' => 1,
    ], [
        'key' => 'bank_in_old.clear',
        'name' => 'สิทธิ์ ปฏิเสธ รายการฝากค้าง',
        'route' => 'admin.bank_in_old.clear',
        'sort' => 2,
    ], [
        'key' => 'bank_in_old.delete',
        'name' => 'สิทธิ์ ลบ รายการฝากค้าง',
        'route' => 'admin.bank_in_old.delete',
        'sort' => 3,
    ], [
        'key' => 'withdraw',
        'name' => 'รายการ ถอนเงิน',
        'route' => 'admin.withdraw.index',
        'sort' => 4,
    ], [
        'key' => 'withdraw.edit',
        'name' => 'สิทธิ์ อนุมัติรายการถอน',
        'route' => 'admin.withdraw.edit',
        'sort' => 1,
    ], [
        'key' => 'withdraw.clear',
        'name' => 'สิทธิ์ คืนยอดรายการถอน',
        'route' => 'admin.withdraw.clear',
        'sort' => 2,
    ], [
        'key' => 'withdraw.delete',
        'name' => 'สิทธิ์ ลบรายการถอน',
        'route' => 'admin.withdraw.delete',
        'sort' => 3,
    ], [
        'key' => 'withdraw_free',
        'name' => 'รายการ ถอนเงิน (ฟรีเครดิต)',
        'route' => 'admin.withdraw_free.index',
        'sort' => 5,
    ], [
        'key' => 'withdraw_free.edit',
        'name' => 'สิทธิ์ อนุมัติรายการถอน',
        'route' => 'admin.withdraw_free.edit',
        'sort' => 1,
    ], [
        'key' => 'withdraw_free.clear',
        'name' => 'สิทธิ์ คืนยอดรายการถอน',
        'route' => 'admin.withdraw_free.clear',
        'sort' => 2,
    ], [
        'key' => 'withdraw_free.delete',
        'name' => 'สิทธิ์ ลบรายการถอน',
        'route' => 'admin.withdraw_free.delete',
        'sort' => 3,
    ], [
        'key' => 'check_case',
        'name' => 'เช็คเลขเคส Payment',
        'route' => 'admin.check_case.index',
        'sort' => 6,
    ], [
        'key' => 'payment',
        'name' => 'ค่าใช้จ่าย',
        'route' => 'admin.payment.index',
        'sort' => 10,
    ], [
        'key' => 'payment.create',
        'name' => 'เพิ่ม ค่าใช้จ่าย',
        'route' => 'admin.payment.create',
        'sort' => 1,
    ], [
        'key' => 'payment.update',
        'name' => 'แก้ไข ค่าใช้จ่าย',
        'route' => 'admin.payment.update',
        'sort' => 2,
    ], [
        'key' => 'payment.delete',
        'name' => 'ลบ ค่าใช้จ่าย',
        'route' => 'admin.payment.delete',
        'sort' => 3,

    ], [
        'key' => 'wallet',
        'name' => 'Members',
        'route' => 'admin.member.index',
        'sort' => 20,
    ], [
        'key' => 'wallet.member',
        'name' => 'รายการสมาชิก',
        'route' => 'admin.member.index',
        'sort' => 1,

    ], [
        'key' => 'wallet.member.refill',
        'name' => 'สิทธิ์ เพิ่มรายการฝาก',
        'route' => 'admin.member.refill',
        'sort' => 1,
    ], [
        'key' => 'wallet.member.setwallet',
        'name' => 'สิทธิ์ เพิ่มลด Credit',
        'route' => 'admin.member.setwallet',
        'sort' => 2,

    ], [
        'key' => 'wallet.member.update',
        'name' => 'สิทธิ์ แก้ไขข้อมูล สมาชิก',
        'route' => 'admin.member.update',
        'sort' => 3,
    ], [
        'key' => 'wallet.member.delete',
        'name' => 'สิทธิ์ ลบข้อมูล สมาชิก',
        'route' => 'admin.member.delete',
        'sort' => 4,
    ], [
        'key' => 'wallet.member.index',
        'name' => 'สิทธิ์ เห็นข้อมูล',
        'route' => 'admin.member.index',
        'sort' => 5,
    ], [
        'key' => 'wallet.member.tel',
        'name' => 'สิทธิ์ เห็นเบอร์โทร',
        'route' => '',
        'sort' => 6,
    ], [
        'key' => 'wallet.member.password',
        'name' => 'สิทธิ์ เห็นรหัสผ่าน',
        'route' => '',
        'sort' => 7,
    ], [
        'key' => 'wallet.game_user',
        'name' => 'ยอดเทรินและอั้นถอน',
        'route' => 'admin.game_user.index',
        'sort' => 2,
    ], [
        'key' => 'wallet.game_user.update',
        'name' => 'สิทธิ์ แก้ไข ข้อมูล',
        'route' => 'admin.game_user.update',
        'sort' => 1,
    ], [
        'key' => 'wallet.game_user.delete',
        'name' => 'สิทธิ์ รีเซต ข้อมูล',
        'route' => 'admin.game_user.delete',
        'sort' => 2,
    ], [
        'key' => 'wallet.game_user.index',
        'name' => 'สิทธิ์ เห็นข้อมูล',
        'route' => 'admin.game_user.index',
        'sort' => 3,
    ], [
        'key' => 'wallet.rp_wallet',
        'name' => 'รายงาน เพิ่ม-ลด (Credit)',
        'route' => 'admin.rp_wallet.index',
        'sort' => 3,
    ], [
        'key' => 'wallet.rp_deposit',
        'name' => 'รายงาน ฝากเงิน (Credit)',
        'route' => 'admin.rp_deposit.index',
        'sort' => 4,
    ], [
        'key' => 'wallet.rp_withdraw',
        'name' => 'รายงาน ถอนเงิน (Credit)',
        'route' => 'admin.rp_withdraw.index',
        'sort' => 5,
    ], [
        'key' => 'wallet.rp_setpoint',
        'name' => 'รายงาน เพิ่ม-ลด (Point)',
        'route' => 'admin.rp_setpoint.index',
        'sort' => 6,
    ], [
        'key' => 'wallet.rp_setdiamond',
        'name' => 'รายงาน เพิ่ม-ลด (Diamond)',
        'route' => 'admin.rp_setdiamond.index',
        'sort' => 7,
    ], [
        'key' => 'wallet.wallet_txn',
        'name' => 'รายงาน (Wallet Transactions)',
        'route' => 'admin.wallet_txn.index',
        'sort' => 8,
    ], [
        'key' => 'credit',
        'name' => 'Members Free Credit',
        'route' => 'admin.member_free.index',
        'sort' => 30,
    ], [
        'key' => 'credit.member_free',
        'name' => 'สมาชิก (Free Credit)',
        'route' => 'admin.member_free.index',
        'sort' => 1,
    ], [
        'key' => 'credit.member_free.setwallet',
        'name' => 'สิทธิ์ เพิ่มลด Free Credit',
        'route' => 'admin.member_free.setwallet',
        'sort' => 1,
    ], [
        'key' => 'credit.game_user_free',
        'name' => 'ยอดเทรินและอั้นถอน',
        'route' => 'admin.game_user_free.index',
        'sort' => 2,
    ], [
        'key' => 'credit.game_user_free.update',
        'name' => 'สิทธิ์ แก้ไข ข้อมูล',
        'route' => 'admin.game_user_free.update',
        'sort' => 1,
    ], [
        'key' => 'credit.game_user_free.delete',
        'name' => 'สิทธิ์ รีเซต ข้อมูล',
        'route' => 'admin.game_user_free.delete',
        'sort' => 2,
    ], [
        'key' => 'credit.game_user_free.index',
        'name' => 'สิทธิ์ เห็นข้อมูล',
        'route' => 'admin.game_user_free.index',
        'sort' => 3,
    ], [
        'key' => 'credit.rp_credit',
        'name' => 'รายงาน เพิ่ม-ลด (Free Credit)',
        'route' => 'admin.rp_credit.index',
        'sort' => 3,
    ], [
        'key' => 'credit.rp_withdraw_seamless_free',
        'name' => 'รายงาน ถอนเงิน (Free Credit)',
        'route' => 'admin.rp_withdraw_seamless_free.index',
        'sort' => 4,
    ], [
        'key' => 'mop',
        'name' => 'รายงาน (กิจกรรม)',
        'route' => 'admin.rp_reward_point.index',
        'sort' => 40,
    ], [
        'key' => 'mop.rp_cashback',
        'name' => 'Cashback',
        'route' => 'admin.rp_cashback.index',
        'sort' => 1,
    ], [
        'key' => 'mop.rp_member_ic',
        'name' => 'Member IC',
        'route' => 'admin.rp_member_ic.index',
        'sort' => 2,
    ], [
        'key' => 'mop.rp_top_promotion',
        'name' => 'โปรยอดนิยม',
        'route' => 'admin.rp_top_promotion.index',
        'sort' => 3,
    ], [
        'key' => 'mep',
        'name' => 'รายงานสมาชิก',
        'route' => 'admin.rp_billturn.index',
        'sort' => 50,

    ], [
        'key' => 'mep.rp_billturn',
        'name' => 'ทำเทรินโยกออก',
        'route' => 'admin.rp_billturn.index',
        'sort' => 1,
    ], [
        'key' => 'mep.rp_spin',
        'name' => 'การหมุนวงล้อ',
        'route' => 'admin.rp_spin.index',
        'sort' => 2,
    ], [
        'key' => 'mep.rp_sponsor',
        'name' => 'แนะนำเพื่อน',
        'route' => 'admin.rp_sponsor.index',
        'sort' => 3,
    ], [
        'key' => 'mep.rp_member_ref',
        'name' => 'แหล่งที่มาการสมัคร',
        'route' => 'admin.rp_member_ref.index',
        'sort' => 4,
    ], [
        'key' => 'mep.rp_user_log',
        'name' => 'Activity Log',
        'route' => 'admin.rp_user_log.index',
        'sort' => 5,
    ], [
        'key' => 'mep.rp_member_edit',
        'name' => 'ประวัติแก้ไขข้อมูล',
        'route' => 'admin.rp_member_edit.index',
        'sort' => 6,
    ], [
        'key' => 'mep.rp_recommender',
        'name' => 'แนะนำเพื่อน หาลูกทีมทั้งหมด',
        'route' => 'admin.rp_recommender.index',
        'sort' => 7,
    ], [
        'key' => 'mep.member_log',
        'name' => 'ประวัติการ Login Fail',
        'route' => 'admin.member_log.index',
        'sort' => 8,
    ], [
        'key' => 'gamelog',
        'name' => 'Games Log',
        'route' => 'admin.gamelog.index',
        'sort' => 55,
    ], [
        'key' => 'gameloglocal',
        'name' => 'Games Log (ใหม่)',
        'route' => 'admin.gamelog.local',
        'sort' => 56,
    ], [
        'key' => 'mon',
        'name' => 'รายงานการเงิน',
        'route' => 'admin.rp_alllog.index',
        'sort' => 60,
    ], [
        'key' => 'mon.rp_alllog',
        'name' => 'All Log',
        'route' => 'admin.rp_alllog.index',
        'sort' => 1,
    ], [
        'key' => 'mon.rp_alllog_free',
        'name' => 'All Log Free',
        'route' => 'admin.rp_alllog_free.index',
        'sort' => 2,

    ], [
        'key' => 'mon.rp_sum_stat',
        'name' => 'สรุปยอดรายเดือน',
        'route' => 'admin.rp_sum_stat.index',
        'sort' => 3,

    ], [
        'key' => 'mon.rp_sum_payment',
        'name' => 'สรุปยอดค่าใช้จ่าย',
        'route' => 'admin.rp_sum_payment.index',
        'sort' => 4,
    ], [
        'key' => 'mon.rp_top_payment',
        'name' => 'ฝากถอน 100 อันดับ',
        'route' => 'admin.rp_top_payment.index',
        'sort' => 5,
    ], [
        'key' => 'mon.rp_no_refill',
        'name' => 'สมาชิกไม่เติมเงิน',
        'route' => 'admin.rp_no_refill.index',
        'sort' => 6,
    ], [
        'key' => 'mon.rp_summary',
        'name' => 'สรุปทั้งหมด',
        'route' => 'admin.rp_summary.index',
        'sort' => 7,
    ], [
        'key' => 'mon.rp_first_time',
        'name' => 'สมาชิกฝากแรก',
        'route' => 'admin.rp_first_time.index',
        'sort' => 8,
    ], [

        'key' => 'ats',
        'name' => 'ตั้งค่าบัญชี',
        'route' => 'admin.bank_account_in.index',
        'sort' => 70,
    ], [
        'key' => 'ats.bank_account_in',
        'name' => 'บัญชีรับเข้า',
        'route' => 'admin.bank_account_in.index',
        'sort' => 1,
    ], [
        'key' => 'ats.bank_account_in.create',
        'name' => 'เพิ่มบัญชีรับเข้า',
        'route' => 'admin.bank_account_in.create',
        'sort' => 1,
    ], [
        'key' => 'ats.bank_account_in.update',
        'name' => 'แก้ไขบัญชีรับเข้า',
        'route' => 'admin.bank_account_in.update',
        'sort' => 2,
    ], [
        'key' => 'ats.bank_account_in.delete',
        'name' => 'ลบบัญชีรับเข้า',
        'route' => 'admin.bank_account_in.delete',
        'sort' => 3,
    ], [
        'key' => 'ats.bank_account_in.index',
        'name' => 'สิทธิ์ เห็นข้อมูล',
        'route' => 'admin.bank_account_in.index',
        'sort' => 4,
    ], [
        'key' => 'ats.bank_account_in.tel',
        'name' => 'สิทธิ์ เห็น User Pass',
        'route' => '',
        'sort' => 5,
    ], [
        'key' => 'ats.bank_account_out',
        'name' => 'บัญชีถอนออก',
        'route' => 'admin.bank_account_out.index',
        'sort' => 2,
    ], [
        'key' => 'ats.bank_account_out.create',
        'name' => 'เพิ่มบัญชีถอนออก',
        'route' => 'admin.bank_account_out.create',
        'sort' => 1,
    ], [
        'key' => 'ats.bank_account_out.update',
        'name' => 'แก้ไขบัญชีถอนออก',
        'route' => 'admin.bank_account_out.update',
        'sort' => 2,
    ], [
        'key' => 'ats.bank_account_out.delete',
        'name' => 'ลบบัญชีถอนออก',
        'route' => 'admin.bank_account_out.delete',
        'sort' => 3,
    ], [
        'key' => 'ats.bank_account_out.index',
        'name' => 'สิทธิ์ เห็นข้อมูล',
        'route' => 'admin.bank_account_out.index',
        'sort' => 4,
    ], [
        'key' => 'ats.bank_account_out.tel',
        'name' => 'สิทธิ์ เห็น User Pass',
        'route' => '',
        'sort' => 5,
    ], [
        'key' => 'top',
        'name' => 'เกมส์ & โปรโมชั่น',
        'route' => 'admin.game.index',
        'sort' => 80,
    ], [
        'key' => 'top.game',
        'name' => 'เกมส์',
        'route' => 'admin.game.index',
        'sort' => 1,
    ], [
        'key' => 'top.game.update',
        'name' => 'แก้ไขเกมส์',
        'route' => 'admin.game.update',
        'sort' => 1,
    ], [
        'key' => 'top.game_single',
        'name' => 'ตั้งค่า ค่ายเกมที่ปิด',
        'route' => 'admin.game_single.index',
        'sort' => 2,
    ], [
        'key' => 'top.game_single.create',
        'name' => 'เพิ่ม ค่ายเกมที่ปิด',
        'route' => 'admin.game_single.create',
        'sort' => 1,
    ], [
        'key' => 'top.game_single.delete',
        'name' => 'ลบ ค่ายเกมที่ปิด',
        'route' => 'admin.game_single.delete',
        'sort' => 2,
    ], [
        'key' => 'top.game_seamless',
        'name' => 'ตั้งค่าค่ายเกมที่ให้บริการ',
        'route' => 'admin.game_seamless.index',
        'sort' => 3,
    ], [
        'key' => 'top.game_seamless.update',
        'name' => 'แก้ไข เกมที่ให้บริการ',
        'route' => 'admin.game_seamless.update',
        'sort' => 1,
    ], [
        'key' => 'top.game_list',
        'name' => 'ตั้งค่าเกมที่ให้บริการ',
        'route' => 'admin.game_list.index',
        'sort' => 4,
    ], [
        'key' => 'top.promotion',
        'name' => 'โปรโมชั่น (ระบบ)',
        'route' => 'admin.promotion.index',
        'sort' => 5,
    ], [
        'key' => 'top.promotion.update',
        'name' => 'แก้ไข โปรโมชั่น (ระบบ)',
        'route' => 'admin.promotion.update',
        'sort' => 1,
    ], [
        'key' => 'top.pro_content',
        'name' => 'โปรโมชั่น (เพิ่มเติม)',
        'route' => 'admin.pro_content.index',
        'sort' => 6,
    ], [
        'key' => 'top.pro_content.create',
        'name' => 'เพิ่ม โปรโมชั่น (เพิ่มเติม)',
        'route' => 'admin.pro_content.create',
        'sort' => 1,
    ], [
        'key' => 'top.pro_content.update',
        'name' => 'แก้ไข โปรโมชั่น (เพิ่มเติม)',
        'route' => 'admin.pro_content.update',
        'sort' => 2,
    ], [
        'key' => 'top.pro_content.delete',
        'name' => 'ลบ โปรโมชั่น (เพิ่มเติม)',
        'route' => 'admin.pro_content.delete',
        'sort' => 3,
    ], [
        'key' => 'st',
        'name' => 'ตั้งค่า ระบบ',
        'route' => 'admin.setting.index',
        'sort' => 90,
    ], [
        'key' => 'st.setting',
        'name' => 'ค่าพื้นฐานเว็บไซต์',
        'route' => 'admin.setting.index',
        'sort' => 1,
    ], [
        'key' => 'st.setting.update',
        'name' => 'แก้ไข ค่าพื้นฐานเว็บไซต์',
        'route' => 'admin.setting.update',
        'sort' => 1,
    ], [
        'key' => 'st.faq',
        'name' => 'คู่มือ',
        'route' => 'admin.faq.index',
        'sort' => 2,
    ], [
        'key' => 'st.faq.create',
        'name' => 'เพิ่ม คู่มือ',
        'route' => 'admin.faq.create',
        'sort' => 1,
    ], [
        'key' => 'st.faq.update',
        'name' => 'แก้ไข คู่มือ',
        'route' => 'admin.faq.update',
        'sort' => 2,
    ], [
        'key' => 'st.faq.delete',
        'name' => 'ลบ คู่มือ',
        'route' => 'admin.faq.delete',
        'sort' => 3,
    ], [
        'key' => 'st.refer',
        'name' => 'แหล่งที่มาการสมัคร',
        'route' => 'admin.refer.index',
        'sort' => 3,
    ], [
        'key' => 'st.refer.update',
        'name' => 'แก้ไข แหล่งที่มาการสมัคร',
        'route' => 'admin.refer.update',
        'sort' => 1,
    ], [
        'key' => 'st.bank',
        'name' => 'ธนาคาร',
        'route' => 'admin.bank.index',
        'sort' => 4,
    ], [
        'key' => 'st.bank.update',
        'name' => 'แก้ไข ธนาคาร',
        'route' => 'admin.bank.update',
        'sort' => 1,
    ], [
        'key' => 'st.bank_rule',
        'name' => 'การมองเห็นธนาคาร',
        'route' => 'admin.bank_rule.index',
        'sort' => 5,
    ], [
        'key' => 'st.bank_rule.create',
        'name' => 'เพิ่ม การมองเห็นธนาคาร',
        'route' => 'admin.bank_rule.create',
        'sort' => 1,
    ], [
        'key' => 'st.bank_rule.update',
        'name' => 'แก้ไข การมองเห็นธนาคาร',
        'route' => 'admin.bank_rule.update',
        'sort' => 2,
    ], [
        'key' => 'st.bank_rule.delete',
        'name' => 'ลบ การมองเห็นธนาคาร',
        'route' => 'admin.bank_rule.delete',
        'sort' => 3,
    ], [
        'key' => 'st.spin',
        'name' => 'วงล้อมหาสนุก',
        'route' => 'admin.spin.index',
        'sort' => 6,
    ], [
        'key' => 'st.spin.update',
        'name' => 'แก้ไข วงล้อมหาสนุก',
        'route' => 'admin.spin.update',
        'sort' => 1,
    ], [
        'key' => 'st.notice',
        'name' => 'ตั้งค่า ข้อความวิ่ง',
        'route' => 'admin.notice.index',
        'sort' => 7,
    ], [
        'key' => 'st.notice.create',
        'name' => 'เพิ่ม ข้อความวิ่ง',
        'route' => 'admin.notice.create',
        'sort' => 1,
    ], [
        'key' => 'st.notice.update',
        'name' => 'แก้ไข ข้อความวิ่ง',
        'route' => 'admin.notice.update',
        'sort' => 2,
    ], [
        'key' => 'st.notice.delete',
        'name' => 'ลบ ข้อความวิ่ง',
        'route' => 'admin.notice.delete',
        'sort' => 3,
    ], [
        'key' => 'st.notice_new',
        'name' => 'ตั้งค่าประกาศ',
        'route' => 'admin.notice_new.index',
        'sort' => 8,
    ], [
        'key' => 'st.notice_new.create',
        'name' => 'เพิ่ม ประกาศ',
        'route' => 'admin.notice_new.create',
        'sort' => 1,
    ], [
        'key' => 'st.notice_new.update',
        'name' => 'แก้ไข ประกาศ',
        'route' => 'admin.notice_new.update',
        'sort' => 2,
    ], [
        'key' => 'st.notice_new.delete',
        'name' => 'ลบ ประกาศ',
        'route' => 'admin.notice_new.delete',
        'sort' => 3,
    ], [
        'key' => 'st.slide',
        'name' => 'ตั้งค่า Slide',
        'route' => 'admin.slide.index',
        'sort' => 9,
    ], [
        'key' => 'st.slide.create',
        'name' => 'เพิ่ม Slide',
        'route' => 'admin.slide.create',
        'sort' => 1,
    ], [
        'key' => 'st.slide.update',
        'name' => 'แก้ไข Slide',
        'route' => 'admin.slide.update',
        'sort' => 2,
    ], [
        'key' => 'st.slide.delete',
        'name' => 'ลบ Slide',
        'route' => 'admin.slide.delete',
        'sort' => 3,
    ], [
        'key' => 'st.coupon',
        'name' => 'ตั้งค่า คูปอง',
        'route' => 'admin.coupon.index',
        'sort' => 10,
    ], [
        'key' => 'st.coupon.create',
        'name' => 'เพิ่ม coupon',
        'route' => 'admin.coupon.create',
        'sort' => 1,
    ], [
        'key' => 'st.coupon.update',
        'name' => 'แก้ไข coupon',
        'route' => 'admin.coupon.update',
        'sort' => 2,
    ], [
        'key' => 'st.contact_channel',
        'name' => 'ตั้งค่า ช่องทางติดต่อ',
        'route' => 'admin.contact_channel.index',
        'sort' => 11,
    ], [
        'key' => 'st.contact_channel.create',
        'name' => 'เพิ่ม ช่องทางติดต่อ',
        'route' => 'admin.contact_channel.create',
        'sort' => 1,
    ], [
        'key' => 'st.contact_channel.update',
        'name' => 'แก้ไข ช่องทางติดต่อ',
        'route' => 'admin.contact_channel.update',
        'sort' => 2,
    ], [
        'key' => 'st.contact_channel.delete',
        'name' => 'ลบ ช่องทางติดต่อ',
        'route' => 'admin.contact_channel.delete',
        'sort' => 3,
    ], [
        'key' => 'dev',
        'name' => 'Admin Zone',
        'route' => 'admin.employees.index',
        'sort' => 100,
    ], [
        'key' => 'dev.employees',
        'name' => 'ผู้ใช้งานระบบ',
        'route' => 'admin.employees.index',
        'sort' => 1,
    ], [
        'key' => 'dev.employees.create',
        'name' => 'เพิ่ม ผู้ใช้งานระบบ',
        'route' => 'admin.employees.create',
        'sort' => 1,
    ], [
        'key' => 'dev.employees.update',
        'name' => 'แก้ไข ผู้ใช้งานระบบ',
        'route' => 'admin.employees.update',
        'sort' => 2,
    ], [
        'key' => 'dev.employees.delete',
        'name' => 'ลบ ผู้ใช้งานระบบ',
        'route' => 'admin.employees.delete',
        'sort' => 3,
    ], [
        'key' => 'dev.roles',
        'name' => 'สิทธิ์ ใช้งานระบบ',
        'route' => 'admin.roles.index',
        'sort' => 2,
    ], [
        'key' => 'dev.roles.create',
        'name' => 'เพิ่ม สิทธิ์ ใช้งานระบบ',
        'route' => 'admin.roles.create',
        'sort' => 1,
    ], [
        'key' => 'dev.roles.update',
        'name' => 'แก้ไข สิทธิ์ ใช้งานระบบ',
        'route' => 'admin.roles.update',
        'sort' => 2,
    ], [
        'key' => 'dev.roles.delete',
        'name' => 'ลบ สิทธิ์ ใช้งานระบบ',
        'route' => 'admin.roles.delete',
        'sort' => 3,
    ], [
        'key' => 'dev.rp_staff_log',
        'name' => 'Staff Activity Log',
        'route' => 'admin.rp_staff_log.index',
        'sort' => 3,
    ], [
        'key' => 'dev.rp_log',
        'name' => 'Log',
        'route' => 'admin.r_log.index',
        'sort' => 4,
    ],
];
