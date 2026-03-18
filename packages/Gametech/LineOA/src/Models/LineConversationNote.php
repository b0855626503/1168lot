<?php

namespace Gametech\LineOA\Models;

use Gametech\LineOA\Contracts\LineConversationNote as LineConversationNoteContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineConversationNote extends Model implements LineConversationNoteContract
{
    protected $table = 'line_conversation_notes';

    protected $fillable = [
        'line_conversation_id',
        'line_account_id',
        'line_contact_id',
        'employee_id',
        'employee_name',
        'body',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(LineConversation::class, 'line_conversation_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(LineContact::class, 'line_contact_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(LineAccount::class, 'line_account_id');
    }
}
