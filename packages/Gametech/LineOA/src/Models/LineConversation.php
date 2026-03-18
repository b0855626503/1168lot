<?php

namespace Gametech\LineOA\Models;

use Gametech\LineOA\Contracts\LineConversation as LineConversationContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LineConversation extends Model implements LineConversationContract
{
    protected $table = 'line_conversations';

    protected $fillable = [
        'line_account_id',
        'line_contact_id',
        'status',
        'last_message_preview',
        'last_message_at',
        'assigned_at',
        'unread_count',
        'assigned_employee_id',
        'assigned_employee_name',
        'locked_by_employee_id',
        'locked_by_employee_name',
        'locked_at',
        'closed_by_employee_id',
        'closed_by_employee_name',
        'closed_at',
        'outgoing_language',
        'incoming_language',
        'is_pinned',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'assigned_at' => 'datetime',
        'locked_at' => 'datetime',
        'closed_at' => 'datetime',
        'is_pinned' => 'boolean',
    ];

    protected $appends = [
        'is_registering',
    ];

    // ===============================
    //      RELATIONS
    // ===============================

    public function account(): BelongsTo
    {
        return $this->belongsTo(LineAccount::class, 'line_account_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(LineContact::class, 'line_contact_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(LineMessage::class, 'line_conversation_id');
    }

    /**
     * Sessions สมัครสมาชิกทั้งหมดที่ผูกกับห้องนี้
     */
    public function registerSessions(): HasMany
    {
        return $this->hasMany(LineRegisterSession::class, 'line_conversation_id');
    }

    /**
     * session สมัครที่ยัง active อยู่ (in_progress)
     */
    public function activeRegisterSession()
    {
        return $this->hasOne(LineRegisterSession::class, 'line_conversation_id')
            ->where('status', 'in_progress')
            ->latest('id');
    }

    // ===============================
    //      ACCESSORS
    // ===============================

    /**
     * กำลังสมัครกับบอทอยู่ไหม?
     */
    public function getIsRegisteringAttribute(): bool
    {
        return $this->registerSessions()
            ->where('status', 'in_progress')
            ->exists();
    }
}
