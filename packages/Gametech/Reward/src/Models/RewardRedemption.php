<?php

namespace Gametech\Reward\Models;

use Gametech\Admin\Models\Admin;
use Gametech\Member\Models\Member;
use Gametech\Reward\Contracts\RewardRedemption as RewardRedemptionContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardRedemption extends Model implements RewardRedemptionContract
{
    protected $table = 'reward_redemptions';

    protected $fillable = [
        'reward_id',
        'member_id',

        // Snapshot กัน reward เปลี่ยนทีหลังแล้วประวัติเละ
        'reward_code_snapshot',
        'reward_name_snapshot',
        'point_cost_snapshot',
        'reward_type_snapshot',
        'fulfillment_mode_snapshot',
        'credit_amount_snapshot',
        'gem_amount_snapshot',
        'payload_snapshot',

        // Status lifecycle
        'status',

        // Notes
        'note_user',
        'note_staff',

        // Contact info (external/manual)
        'contact_channel',
        'contact_value',

        // Fulfillment timestamps
        'fulfilled_at',
        'cancelled_at',
        'rejected_at',

        // Staff
        'handled_by',

        // Idempotency
        'idempotency_key',

        // ===== NEW: audit/debug =====
        'request_ip',
        'request_ua',
        'request_source',

        // ===== NEW: point accounting flags =====
        'point_debited',
        'refunded_at',
        'refunded_by',

        // ===== NEW: explicit redeemed time =====
        'redeemed_at',
    ];

    protected $casts = [
        'payload_snapshot' => 'array',

        'fulfilled_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'rejected_at' => 'datetime',

        'credit_amount_snapshot' => 'decimal:2',

        // NEW
        'point_debited' => 'boolean',
        'refunded_at' => 'datetime',
        'redeemed_at' => 'datetime',
    ];

    /* ===============================
     | Relations
     =============================== */

    public function reward(): BelongsTo
    {
        return $this->belongsTo(RewardList::class, 'reward_id');
    }

    public function member(): BelongsTo
    {
        // members.code
        return $this->belongsTo(Member::class, 'member_id', 'code');
    }

    public function handledBy(): BelongsTo
    {
        // employees.code (คุณแมพไว้กับ Admin model อยู่แล้วในระบบเดิม)
        return $this->belongsTo(Admin::class, 'handled_by', 'code');
    }

    public function refundedBy(): BelongsTo
    {
        // employees.code (แนวเดียวกับ handledBy)
        return $this->belongsTo(Admin::class, 'refunded_by', 'code');
    }

    /* ===============================
     | Status helpers
     =============================== */

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFulfilled(): bool
    {
        return $this->status === 'fulfilled';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isRefunded(): bool
    {
        return $this->refunded_at !== null;
    }

    /* ===============================
     | Business helpers
     =============================== */

    public function isAuto(): bool
    {
        return $this->fulfillment_mode_snapshot === 'auto';
    }

    public function isManual(): bool
    {
        return $this->fulfillment_mode_snapshot === 'manual';
    }

    public function canRefund(): bool
    {
        // คืนแต้มได้ก็ต่อเมื่อเคยตัดแต้มแล้ว และยังไม่เคยคืน
        return (bool) $this->point_debited && $this->refunded_at === null;
    }
}
