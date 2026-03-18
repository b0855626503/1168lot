<?php

namespace Gametech\Member\Models;

use DateTimeInterface;
use Gametech\Member\Contracts\MemberDepositStat as MemberDepositStatContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class MemberDepositStat extends Model implements MemberDepositStatContract
{
    use LadaCacheTrait;

    protected $table = 'member_deposit_stats';

    protected $fillable = [
        'member_code',
        'deposit_success_count',
        'deposit_success_sum',
        'legacy_at',
    ];

    protected $casts = [
        'member_code'            => 'integer',
        'deposit_success_count'  => 'integer',
        'deposit_success_sum'    => 'decimal:2',
        'legacy_at'              => 'datetime:Y-m-d H:i:s',
        'created_at'             => 'datetime:Y-m-d H:i:s',
        'updated_at'             => 'datetime:Y-m-d H:i:s',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * เงื่อนไขลูกค้าเก่า: ผ่าน 2 เงื่อนไขพร้อมกัน
     * - บิลฝากสำเร็จ >= 10
     * - ยอดฝากสะสม (สำเร็จ) > 10000
     */
    public function isLegacy(): bool
    {
        return
            (int) $this->deposit_success_count >= 10
            && (float) $this->deposit_success_sum > 10000;
    }

    /**
     * mark legacy_at ครั้งแรกที่ผ่านเงื่อนไข (กันสถานะแกว่ง)
     * คืนค่า true ถ้ามีการเปลี่ยนแปลง legacy_at
     */
    public function markLegacyIfPassed(): bool
    {
        if (! empty($this->legacy_at)) {
            return false;
        }

        if ($this->isLegacy()) {
            $this->legacy_at = Carbon::now();
            return true;
        }

        return false;
    }

    /**
     * เพิ่มสถิติเมื่อมี "บิลฝากสำเร็จ" ใหม่
     * หมายเหตุ: ควรเรียกภายใน DB::transaction() และ lockForUpdate เพื่อกันซ้อน
     */
    public function addSuccessDeposit(float $amount): void
    {
        $this->deposit_success_count = (int) $this->deposit_success_count + 1;
        $this->deposit_success_sum   = (float) $this->deposit_success_sum + (float) $amount;

        $this->markLegacyIfPassed();
    }

    /**
     * Relationship -> Member (members.code)
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_code', 'code');
    }

    /**
     * Scope: ลูกค้าเก่า (ล็อกด้วย legacy_at)
     */
    public function scopeLegacy($query)
    {
        return $query->whereNotNull('legacy_at');
    }

    /**
     * Scope: ลูกค้าใหม่
     */
    public function scopeNewCustomer($query)
    {
        return $query->whereNull('legacy_at');
    }

    /**
     * helper: สร้าง/ดึง stat ของสมาชิก (กัน null ในจุดเรียกใช้)
     */
    public static function findOrCreateForMember(int $memberCode): self
    {
        return static::firstOrCreate(
            ['member_code' => $memberCode],
            [
                'deposit_success_count' => 0,
                'deposit_success_sum'   => 0,
                'legacy_at'             => null,
            ]
        );
    }
}
