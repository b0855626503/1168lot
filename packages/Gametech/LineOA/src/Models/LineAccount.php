<?php

namespace Gametech\LineOA\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Gametech\LineOA\Contracts\LineAccount as LineAccountContract;
use Illuminate\Support\Str;

class LineAccount extends Model implements LineAccountContract
{
    protected $table = 'line_accounts';

    protected $fillable = [
        'name',
        'channel_id',
        'channel_secret',
        'access_token',
        'webhook_token',
        'status',
        'remark',
    ];

    protected $casts = [
        'remark' => 'string',
    ];

    /* ==========================
     |  Boot
     |==========================*/
    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            // auto-generate webhook token ถ้าไม่ได้กำหนด
            if (empty($model->webhook_token)) {
                $model->webhook_token = Str::random(32);
            }
        });
    }

    /* ==========================
     |  Relationships
     |==========================*/

    // บัญชี OA มี contact หลายคน
    public function contacts()
    {
        return $this->hasMany(LineContact::class, 'line_account_id');
    }

    // บัญชี OA มี conversations หลายห้อง
    public function conversations()
    {
        return $this->hasMany(LineConversation::class, 'line_account_id');
    }

    // บัญชี OA มี messages ทั้งหมด
    public function messages()
    {
        return $this->hasMany(LineMessage::class, 'line_account_id');
    }

    /* ==========================
     |  Scopes
     |==========================*/

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /* ==========================
     |  Helpers
     |==========================*/

    // ใช้เช็คว่าพร้อมใช้งานหรือไม่
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // เพื่อความสะดวกเวลา debug
    public function shortToken(): string
    {
        if (! $this->access_token) {
            return '-';
        }
        return Str::limit($this->access_token, 10, '...'); // โชว์บางส่วน
    }
}