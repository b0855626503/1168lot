<?php

namespace Gametech\LineOA\Models;

use Gametech\LineOA\Contracts\LineContact as LineContactContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LineContact extends Model implements LineContactContract
{
    protected $table = 'line_contacts';

    protected $fillable = [
        'line_account_id',
        'line_user_id',
        'display_name',
        'picture_url',
        'status_message',
        'member_id',
        'member_username',
        'member_mobile',
        'tags',
        'last_seen_at',
        'blocked_at',
        'preferred_language',
        'last_detected_language',
    ];

    protected $casts = [
        'tags' => 'array',
        'last_seen_at' => 'datetime',
        'blocked_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(LineAccount::class, 'line_account_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(LineConversation::class, 'line_contact_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(LineMessage::class, 'line_contact_id');
    }

    // Gametech\LineOA\Models\LineContact.php

    public function member()
    {
        // สมมติ member_id อ้างไปยัง members.code
        return $this->belongsTo(\Gametech\Member\Models\Member::class, 'member_id', 'code');
    }



}
