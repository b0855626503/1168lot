<?php

namespace Tests\Unit\Marketing;

use Gametech\Marketing\Models\RegistrationLinkClick;
use PHPUnit\Framework\TestCase;

class RegistrationLinkClickAnalyticsSchemaTest extends TestCase
{
    public function test_model_has_analytics_fields_in_fillable(): void
    {
        $model = new RegistrationLinkClick;
        $fillable = $model->getFillable();

        $expected = [
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

        foreach ($expected as $field) {
            $this->assertContains($field, $fillable, "Field [{$field}] missing from fillable");
        }
    }

    public function test_model_casts_boolean_flags_correctly(): void
    {
        $model = new RegistrationLinkClick;
        $casts = $model->getCasts();

        $this->assertSame('boolean', $casts['is_bot'] ?? null);
        $this->assertSame('boolean', $casts['is_preview_bot'] ?? null);
        $this->assertSame('boolean', $casts['is_suspicious'] ?? null);
    }

    public function test_model_casts_timestamps_correctly(): void
    {
        $model = new RegistrationLinkClick;
        $casts = $model->getCasts();

        $this->assertSame('datetime', $casts['client_confirmed_at'] ?? null);
        $this->assertSame('datetime', $casts['submitted_at'] ?? null);
        $this->assertSame('datetime', $casts['converted_at'] ?? null);
    }

    public function test_model_casts_metadata_json_as_array(): void
    {
        $model = new RegistrationLinkClick;
        $casts = $model->getCasts();

        $this->assertSame('array', $casts['metadata_json'] ?? null);
    }

    public function test_model_casts_risk_score_as_integer(): void
    {
        $model = new RegistrationLinkClick;
        $casts = $model->getCasts();

        $this->assertSame('integer', $casts['risk_score'] ?? null);
    }

    public function test_model_preserves_original_fillable_fields(): void
    {
        $model = new RegistrationLinkClick;
        $fillable = $model->getFillable();

        foreach (['registration_link_id', 'ip', 'user_agent', 'referrer', 'created_at'] as $field) {
            $this->assertContains($field, $fillable, "Original field [{$field}] missing from fillable");
        }
    }
}
