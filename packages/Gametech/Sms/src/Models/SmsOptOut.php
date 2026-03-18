<?php

namespace Gametech\Sms\Models;

use Gametech\Sms\Contracts\SmsOptOut as SmsOptOutContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsOptOut extends Model implements SmsOptOutContract
{
    use SoftDeletes;

    protected $table = 'sms_opt_outs';

    protected $fillable = [
        'team_id',
        'phone_e164',
        'phone_raw',

        'source',
        'reason',
        'note',

        'created_by',
        'opted_out_at',
        'meta',
    ];

    protected $casts = [
        'opted_out_at' => 'datetime',
        'meta' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function isOptedOut(string $phoneE164): bool
    {
        return static::where('phone_e164', $phoneE164)->exists();
    }
}
