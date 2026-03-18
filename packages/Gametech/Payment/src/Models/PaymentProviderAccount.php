<?php

namespace Gametech\Payment\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProviderAccount extends Model
{
    protected $table = 'payment_provider_accounts';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $fillable = [
        'provider',
        'member_code',

        'customer_id',
        'customer_account_id',

        'account_identifier',
        'account_platform',
        'currency_code',

        'name',
        'phone_number',

        'bank_code',
        'bank_account_number',
        'bank_account_name',

        'sync_hash',
        'last_synced_at',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'last_synced_at' => 'datetime',
    ];
}
