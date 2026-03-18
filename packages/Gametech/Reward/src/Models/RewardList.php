<?php

namespace Gametech\Reward\Models;

use Gametech\Reward\Contracts\RewardList as RewardListContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RewardList extends Model implements RewardListContract
{
    use SoftDeletes;

    protected $table = 'rewards_list';

    protected $fillable = [
        'code',
        'name',
        'slug',
        'description',
        'image',
        'images',

        'reward_type',
        'fulfillment_mode',
        'auto_claim',
        'require_staff_contact',

        'point_cost',

        // legacy limits (ยังคงไว้)
        'limit_per_user',
        'limit_total',
        'cooldown_minutes',

        // ===== NEW: rule-based limits =====
        'limit_type',        // unlimited | per_reward | per_period
        'limit_period',      // day | week | month | event
        'limit_per_period',  // int
        'strict_limit',      // boolean

        'start_at',
        'end_at',
        'timezone',

        'stock_unlimited',
        'stock',
        'reserved_stock',
        'auto_disable_when_out_of_stock',

        'credit_amount',
        'gem_amount',

        'payload',

        'status',
        'priority',
        'is_featured',
        'is_hidden',

        'campaign_id',
        'event_id',
        'tags',

        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'images' => 'array',
        'payload' => 'array',
        'tags' => 'array',

        'auto_claim' => 'boolean',
        'require_staff_contact' => 'boolean',
        'stock_unlimited' => 'boolean',
        'auto_disable_when_out_of_stock' => 'boolean',
        'is_featured' => 'boolean',
        'is_hidden' => 'boolean',

        'strict_limit' => 'boolean',

        'start_at' => 'datetime',
        'end_at' => 'datetime',

        'credit_amount' => 'decimal:2',
    ];

    /* ===============================
     | Relations
     =============================== */

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class, 'reward_id');
    }

    /* ===============================
     | Scopes (ใช้จริงในระบบ)
     =============================== */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAvailableNow($query)
    {
        // NOTE:
        // scope เป็น static query context → ห้ามใช้ $this->timezone (ไม่มี instance)
        // ใช้ Asia/Bangkok เป็น baseline ที่นิ่งสุดในระบบคุณ
        $now = now('Asia/Bangkok');

        return $query
            ->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            });
    }

    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    /* ===============================
     | Helper methods (อ่านโค้ดง่าย)
     =============================== */

    public function isAuto(): bool
    {
        return $this->fulfillment_mode === 'auto';
    }

    public function isManual(): bool
    {
        return $this->fulfillment_mode === 'manual';
    }

    public function isExternal(): bool
    {
        return $this->reward_type === 'external';
    }

    public function hasStock(): bool
    {
        if ($this->stock_unlimited) {
            return true;
        }

        if ($this->stock === null) {
            return false;
        }

        return ((int) $this->stock - (int) $this->reserved_stock) > 0;
    }
}
