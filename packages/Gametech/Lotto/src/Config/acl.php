<?php

return [
    [
        'key' => 'lotto_tickets',
        'name' => 'รายการโพย',
        'route' => 'admin.lotto.tickets.index',
        'sort' => 35,
    ],
    [
        'key' => 'lotto_settings',
        'name' => 'ตั้งค่าหวย',
        'route' => 'admin.lotto.switches.index',
        'sort' => 88,
    ],
    [
        'key' => 'lotto_settings.draws',
        'name' => 'งวดหวย',
        'route' => 'admin.lotto.draws.index',
        'sort' => 1,
    ],
    [
        'key' => 'lotto_draws.edit',
        'name' => 'แก้ไขงวดหวย',
        'route' => 'admin.lotto.draws.index',
        'sort' => 11,
    ],
    [
        'key' => 'lotto_draws.open',
        'name' => 'เปิดรับงวดหวย',
        'route' => 'admin.lotto.draws.index',
        'sort' => 12,
    ],
    [
        'key' => 'lotto_draws.force_open',
        'name' => 'เปิดรับก่อนเวลา',
        'route' => 'admin.lotto.draws.index',
        'sort' => 13,
    ],
    [
        'key' => 'lotto_draws.close',
        'name' => 'ปิดรับงวดหวย',
        'route' => 'admin.lotto.draws.index',
        'sort' => 14,
    ],
    [
        'key' => 'lotto_draws.settle',
        'name' => 'ประกาศผลงวดหวย',
        'route' => 'admin.lotto.draws.index',
        'sort' => 15,
    ],
    [
        'key' => 'lotto_settings.switches',
        'name' => 'เปิด-ปิด หวย',
        'route' => 'admin.lotto.switches.index',
        'sort' => 2,
    ],
    [
        'key' => 'lotto_settings.number_blocks',
        'name' => 'เลขอั้น',
        'route' => 'admin.lotto.number_blocks.index',
        'sort' => 3,
    ],
    [
        'key' => 'lotto_settings.groups',
        'name' => 'กลุ่มหวย',
        'route' => 'admin.lotto.groups.index',
        'sort' => 4,
    ],
    [
        'key' => 'lotto_settings.markets',
        'name' => 'รายการหวย',
        'route' => 'admin.lotto.markets.index',
        'sort' => 5,
    ],
    [
        'key' => 'lotto_settings.payout_settings',
        'name' => 'อัตราจ่าย',
        'route' => 'admin.lotto.rate_plans.index',
        'sort' => 6,
    ],
    [
        'key' => 'lotto_settings.bet_limit_settings',
        'name' => 'ขั้นต่ำ/สูงสุด/สูงสุดต่อเลข',
        'route' => 'admin.lotto.bet_limits.index',
        'sort' => 7,
    ],
    [
        'key' => 'lotto_reports',
        'name' => 'รายงาน Lotto',
        'route' => 'admin.lotto.reports.pending_bets',
        'sort' => 89,
    ],
    [
        'key' => 'lotto_reports.pending_bets',
        'name' => 'รอผลเดิมพัน',
        'route' => 'admin.lotto.reports.pending_bets',
        'sort' => 1,
    ],
    [
        'key' => 'lotto_reports.profit_loss_forecast',
        'name' => 'ดูของรวม/คาดคะเน ได้-เสีย',
        'route' => 'admin.lotto.reports.profit_loss_forecast',
        'sort' => 2,
    ],
    [
        'key' => 'lotto_reports.member_bet_types',
        'name' => 'ดูของสมาชิก/ประเภท',
        'route' => 'admin.lotto.reports.member_bet_types',
        'sort' => 3,
    ],
    [
        'key' => 'lotto_reports.tickets_cancel',
        'name' => 'รายการโพย/ยกเลิกโพย',
        'route' => 'admin.lotto.reports.tickets_cancel',
        'sort' => 4,
    ],
    [
        'key' => 'lotto_reports.blocked_numbers',
        'name' => 'เลขปิดรับ/เลขอั้น',
        'route' => 'admin.lotto.reports.blocked_numbers',
        'sort' => 5,
    ],
];
