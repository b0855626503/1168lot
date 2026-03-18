<?php

namespace Gametech\LineOA\Models;

use Gametech\LineOA\Contracts\LineMessage as LineMessageContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineMessage extends Model implements LineMessageContract
{
    protected $table = 'line_messages';

    protected $fillable = [
        'line_conversation_id',
        'line_account_id',
        'line_contact_id',
        'direction',
        'source',
        'type',
        'line_message_id',
        'text',
        'payload',
        'meta',
        'sender_employee_id',
        'sender_bot_key',
        'sent_at',
        'is_pinned',
    ];

    protected $casts = [
        'payload' => 'array',
        'meta' => 'array',
        'sent_at' => 'datetime',
        'is_pinned' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(LineAccount::class, 'line_account_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(LineContact::class, 'line_contact_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(LineConversation::class, 'line_conversation_id');
    }
}
