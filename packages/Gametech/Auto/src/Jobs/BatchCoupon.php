<?php

namespace Gametech\Auto\Jobs;

use Gametech\Core\Models\CouponList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BatchCoupon implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * timeout ของ job (วินาที)
     *
     * หมายเหตุ: ถ้าในอนาคตจำนวนข้อมูลต่อ job มากขึ้น
     * และถึงขั้น chunk ละหลายพัน / หลายหมื่น แนะนำเพิ่มค่านี้ให้เหมาะสม
     */
    public $timeout = 120;

    /**
     * จำนวนครั้งที่ลองใหม่เมื่อ fail
     */
    public $tries = 1;

    /**
     * จำนวน exception สูงสุดก่อนจะถือว่า fail จริง
     */
    public $maxExceptions = 3;

    /**
     * หน่วงก่อน retry (วินาที)
     */
    public $retryAfter = 3;

    /**
     * ข้อมูล coupon ที่จะ insert
     *
     * @var array<int, array<string, mixed>>
     */
    protected $items;

    /**
     * ขนาด chunk ต่อการ insert 1 ครั้ง
     *
     * สามารถปรับได้ตามสภาพจริงของ DB:
     * - ถ้า table เบา index น้อย → 1000 ก็พอไหว
     * - ถ้า table หนัก / index เยอะ → 200–500 จะเซฟกว่า
     *
     * ตอนนี้ตั้งเป็น 500 เป็นค่า default กลาง ๆ
     *
     * @var int
     */
    protected int $chunkSize = 1000;

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function __construct($items)
    {
        // เก็บเป็น array ธรรมดาไว้เลย เพื่อลดการแปลงซ้ำตอน handle()
        $this->items = is_array($items) ? $items : (array) $items;
    }

    /**
     * Execute the job.
     */
    public function handle(): bool
    {
        // กันเคส job วิ่งมาแล้วไม่มีอะไรให้ insert
        if (empty($this->items)) {
            return true;
        }

        $items = $this->items;

        // ตัดเป็นก้อนย่อยแล้วค่อย insert ทีละก้อน
        foreach (array_chunk($items, $this->chunkSize) as $chunk) {
            // กันเคส chunk ว่างแบบเผื่อ ๆ
            if (empty($chunk)) {
                continue;
            }

            CouponList::insert($chunk);
        }

        return true;
    }
}
