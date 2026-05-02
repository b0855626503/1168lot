<?php

namespace Gametech\Marketing\Models;

use Gametech\Marketing\Contracts\RegistrationLinkClick as RegistrationLinkClickContract;
use Illuminate\Database\Eloquent\Model;

class RegistrationLinkClick extends Model implements RegistrationLinkClickContract
{
    public $timestamps = false;

    protected $fillable = [
        'registration_link_id',
        'ip',
        'user_agent',
        'referrer',
        'created_at',
        'classification_type',
        'classification_reason',
        'risk_score',
        'ip_hash',
        'visitor_id',
        'session_id',
        'method',
        'landing_url',
        'referrer_domain',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'device_type',
        'browser_name',
        'browser_version',
        'os_name',
        'os_version',
        'is_bot',
        'is_preview_bot',
        'is_suspicious',
        'client_confirmed_at',
        'submitted_at',
        'converted_member_id',
        'converted_at',
        'register_type',
        'metadata_json',
    ];

    protected $casts = [
        'risk_score' => 'integer',
        'is_bot' => 'boolean',
        'is_preview_bot' => 'boolean',
        'is_suspicious' => 'boolean',
        'client_confirmed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'converted_at' => 'datetime',
        'created_at' => 'datetime',
        'metadata_json' => 'array',
    ];
}
