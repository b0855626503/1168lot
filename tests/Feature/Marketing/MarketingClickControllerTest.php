<?php

namespace Tests\Feature\Marketing;

use Gametech\Marketing\Models\RegistrationLinkClick;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MarketingClickControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('registration_link_clicks');
        Schema::dropIfExists('registration_links');

        parent::tearDown();
    }

    private function apiPost(string $uri, array $payload = [])
    {
        return $this->withServerVariables(['HTTP_HOST' => 'api.localhost'])
            ->postJson('http://api.localhost/api/v1'.$uri, $payload);
    }

    public function test_track_returns_422_for_invalid_registration_code(): void
    {
        $response = $this->apiPost('/marketing/clicks', [
            'registration_code' => 'NONEXISTENT',
            'visitor_id' => 'v123',
        ]);

        $response->assertStatus(422);
    }

    public function test_track_creates_click_for_valid_registration_code(): void
    {
        $linkId = $this->createRegistrationLink('TESTCODE123');

        $response = $this->apiPost('/marketing/clicks', [
            'registration_code' => 'TESTCODE123',
            'visitor_id' => 'v-abc-001',
            'landing_url' => 'https://example.com/register',
            'utm_source' => 'facebook',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure(['success', 'click_id', 'classification_type', 'risk_score']);

        $this->assertDatabaseHas('registration_link_clicks', [
            'registration_link_id' => $linkId,
            'visitor_id' => 'v-abc-001',
        ]);

        $click = RegistrationLinkClick::find((int) $response->json('click_id'));
        $this->assertNotNull($click);
        $this->assertContains((string) $click->classification_type, ['human', 'bot', 'preview_bot', 'suspicious', 'unknown']);
    }

    public function test_track_ignores_client_provided_ip_and_user_agent_fields(): void
    {
        $linkId = $this->createRegistrationLink('IPUATEST123');

        $response = $this->withServerVariables([
            'HTTP_HOST' => 'api.localhost',
            'HTTP_USER_AGENT' => 'TrustedAgent/2.0',
            'REMOTE_ADDR' => '10.9.8.7',
        ])->postJson('http://api.localhost/api/v1/marketing/clicks', [
            'registration_code' => 'IPUATEST123',
            'visitor_id' => 'visitor-ip-ua',
            'ip' => '1.1.1.1',
            'user_agent' => 'SpoofedAgent/9.9',
        ]);

        $response->assertOk();

        $clickId = (int) $response->json('click_id');
        $click = RegistrationLinkClick::find($clickId);

        $this->assertNotNull($click);
        $this->assertSame($linkId, (int) $click->registration_link_id);
        $this->assertSame('10.9.8.7', (string) $click->ip);
        $this->assertSame('TrustedAgent/2.0', (string) $click->user_agent);
    }

    public function test_track_deduplicates_same_visitor_returning_existing_click(): void
    {
        $this->createRegistrationLink('DEDUPETEST');

        $response1 = $this->apiPost('/marketing/clicks', [
            'registration_code' => 'DEDUPETEST',
            'visitor_id' => 'dup-visitor',
        ]);
        $response1->assertOk();
        $clickId1 = $response1->json('click_id');

        $response2 = $this->apiPost('/marketing/clicks', [
            'registration_code' => 'DEDUPETEST',
            'visitor_id' => 'dup-visitor',
        ]);
        $response2->assertOk();
        $clickId2 = $response2->json('click_id');

        $this->assertSame($clickId1, $clickId2);
    }

    public function test_track_repeated_calls_do_not_create_duplicate_rows_or_fail(): void
    {
        $this->createRegistrationLink('RATELIMITSAFE');

        $first = $this->apiPost('/marketing/clicks', [
            'registration_code' => 'RATELIMITSAFE',
            'visitor_id' => 'safe-visitor',
        ]);
        $first->assertOk();
        $clickId = (int) $first->json('click_id');

        for ($i = 0; $i < 10; $i++) {
            $response = $this->apiPost('/marketing/clicks', [
                'registration_code' => 'RATELIMITSAFE',
                'visitor_id' => 'safe-visitor',
            ]);
            $response->assertOk();
            $this->assertSame($clickId, (int) $response->json('click_id'));
        }

        $this->assertSame(1, RegistrationLinkClick::query()->count());
    }

    public function test_confirm_returns_200_for_existing_click(): void
    {
        $click = $this->createClick();

        $response = $this->apiPost('/marketing/clicks/'.$click->id.'/confirm', [
            'visitor_id' => 'v-abc',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }

    public function test_confirm_returns_404_for_nonexistent_click(): void
    {
        $response = $this->apiPost('/marketing/clicks/99999/confirm');

        $response->assertStatus(404);
    }

    public function test_submitted_returns_200_for_existing_click(): void
    {
        $click = $this->createClick();

        $response = $this->apiPost('/marketing/clicks/'.$click->id.'/submitted');

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertNotNull(
            RegistrationLinkClick::find($click->id)?->submitted_at
        );
    }

    public function test_submitted_returns_404_for_nonexistent_click(): void
    {
        $response = $this->apiPost('/marketing/clicks/88888/submitted');

        $response->assertStatus(404);
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('registration_link_clicks');
        Schema::dropIfExists('registration_links');

        Schema::create('registration_links', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code', 40)->unique();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->timestamps();
        });

        Schema::create('registration_link_clicks', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('registration_link_id');
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referrer')->nullable();
            $table->string('classification_type', 32)->default('unknown');
            $table->string('classification_reason', 191)->nullable();
            $table->unsignedTinyInteger('risk_score')->default(0);
            $table->string('ip_hash', 64)->nullable();
            $table->string('visitor_id', 64)->nullable();
            $table->string('session_id', 128)->nullable();
            $table->string('method', 16)->nullable();
            $table->text('landing_url')->nullable();
            $table->string('referrer_domain', 191)->nullable();
            $table->string('utm_source', 191)->nullable();
            $table->string('utm_medium', 191)->nullable();
            $table->string('utm_campaign', 191)->nullable();
            $table->string('utm_content', 191)->nullable();
            $table->string('utm_term', 191)->nullable();
            $table->string('device_type', 64)->nullable();
            $table->string('browser_name', 64)->nullable();
            $table->string('browser_version', 64)->nullable();
            $table->string('os_name', 64)->nullable();
            $table->string('os_version', 64)->nullable();
            $table->boolean('is_bot')->default(false);
            $table->boolean('is_preview_bot')->default(false);
            $table->boolean('is_suspicious')->default(false);
            $table->timestamp('client_confirmed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('converted_member_id', 64)->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->string('register_type', 32)->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    private function createRegistrationLink(string $code): int
    {
        return (int) DB::table('registration_links')->insertGetId([
            'code' => $code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createClick(): RegistrationLinkClick
    {
        $linkId = $this->createRegistrationLink('CLICKTEST'.uniqid());

        return RegistrationLinkClick::create([
            'registration_link_id' => $linkId,
            'ip' => '127.0.0.1',
            'user_agent' => 'TestBrowser/1.0',
            'classification_type' => 'unknown',
            'risk_score' => 10,
            'created_at' => now(),
        ]);
    }
}
