<?php

namespace Gametech\Lotto\Enums;

/**
 * Fixed Bet Types - ห้ามแก้ได้แค่เพิ่ม
 * ไม่ให้ admin สร้างใหม่ได้
 */
class BetType
{
    const TOP_3 = 'top_3';           // 3 ตัวบน
    const TOD_3 = 'tod_3';           // 3 ตัวโต๊ด
    const TOP_2 = 'top_2';           // 2 ตัวบน
    const BOTTOM_2 = 'bottom_2';     // 2 ตัวล่าง
    const RUN_TOP = 'run_top';       // วิ่งบน
    const RUN_BOTTOM = 'run_bottom'; // วิ่งล่าง

    /**
     * ทุกประเภท
     */
    public static function all(): array
    {
        return [
            self::TOP_3,
            self::TOD_3,
            self::TOP_2,
            self::BOTTOM_2,
            self::RUN_TOP,
            self::RUN_BOTTOM,
        ];
    }

    /**
     * ชื่อแสดงผล
     */
    public static function label(string $type): string
    {
        return match ($type) {
            self::TOP_3 => '3 ตัวบน',
            self::TOD_3 => '3 ตัวโต๊ด',
            self::TOP_2 => '2 ตัวบน',
            self::BOTTOM_2 => '2 ตัวล่าง',
            self::RUN_TOP => 'วิ่งบน',
            self::RUN_BOTTOM => 'วิ่งล่าง',
            default => 'Unknown',
        };
    }
}

