<?php

namespace Gametech\LineOA\Models;

use Gametech\LineOA\Contracts\LineRegisterSession as LineRegisterSessionContract;
use Illuminate\Database\Eloquent\Model;

class LineRegisterSession extends Model implements LineRegisterSessionContract
{
    protected $table = 'line_register_sessions';

    protected $fillable = [
        'line_contact_id',
        'line_conversation_id',

        'status',         // waiting / in_progress / completed / cancelled / failed / expired
        'current_step',   // phone / name / surname / bank / account

        'data',           // JSON: { phone, name, surname, bank_code, account_no }
        'member_id',      // members.code (หรือ id แล้วแต่ระบบ)
        'error_message',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // --------------------------------------------------------
    //  Scopes
    // --------------------------------------------------------

    /**
     * session ที่อยู่ระหว่างดำเนินการ (ตาม default migration)
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * session ที่ยังไม่จบของ contact นั้น ๆ
     */
    public function scopeForContact($query, $contactId)
    {
        return $query->where('line_contact_id', $contactId);
    }

    // --------------------------------------------------------
    //  Relations
    // --------------------------------------------------------

    public function contact()
    {
        return $this->belongsTo(LineContact::class, 'line_contact_id');
    }

    public function conversation()
    {
        return $this->belongsTo(LineConversation::class, 'line_conversation_id');
    }

    /**
     * สมาชิกจริงที่ถูกสร้าง (ถ้าสมัครสำเร็จ)
     * ปกติ Gametech ใช้ primary = code
     */
    public function member()
    {
        return $this->belongsTo(\Gametech\Member\Models\Member::class, 'member_id', 'code');
    }
}
