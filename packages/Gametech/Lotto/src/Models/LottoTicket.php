<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoTicket as LottoTicketContract;
use Illuminate\Database\Eloquent\Model;

/**
 * LottoTicket - โพย (บิล)
 * ประกอบด้วยหลาย items (บรรทัด)
 */
class LottoTicket extends Model implements LottoTicketContract
{
    protected $table = 'lotto_tickets';

    protected $fillable = [
        'member_id',
        'draw_id',
        'total_amount',
        'status',        // active | cancelled | resulted
        'cancelled_at',
        'cancelled_by',  // user_id (admin / member)
        'refund_amount', // ถ้า cancelled
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    protected $dates = ['cancelled_at'];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // Relationships
    public function member()
    {
        return $this->belongsTo(\Gametech\Member\Models\Member::class, 'member_id', 'code');
    }

    public function draw()
    {
        return $this->belongsTo(LottoDraw::class, 'draw_id');
    }

    public function items()
    {
        return $this->hasMany(LottoTicketItem::class, 'ticket_id');
    }
}

