<?php

namespace Gametech\Lotto\Models;

use Gametech\Member\Models\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $table = 'wallet_transactions';

    protected $fillable = [
        'member_id',
        'scope',
        'game_user_id',
        'direction',
        'amount',
        'balance_before',
        'balance_after',
        'ref_type',
        'ref_id',
        'ref_code',
        'provider_txn_id',
        'provider_round_id',
        'group_code',
        'related_txn_id',
        'status',
        'description',
        'meta',
        'created_by_type',
        'created_by_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'meta' => 'json',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'code');
    }
}
