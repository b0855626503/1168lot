<?php

namespace Gametech\Marketing\Services;

use Gametech\Marketing\Models\RegistrationLinkClick;
use Gametech\Marketing\Models\RegistrationLinkProxy;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MarketingClickTrackingService
{
    public function __construct(
        private readonly MarketingClickClassifier $classifier
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function trackClick(Request $request, array $payload): array
    {
        $registrationCode = (string) ($payload['registration_code'] ?? '');
        $link = RegistrationLinkProxy::modelClass()::where('code', $registrationCode)->first();

        if (! $link) {
            return ['success' => false, 'message' => 'Invalid registration code'];
        }

        $ip = $request->ip() ?? '';
        $userAgent = $request->userAgent() ?? '';
        $method = $request->method();
        $ipHash = $ip !== '' ? hash('sha256', $ip) : null;

        if ($this->isDuplicate((int) $link->id, $ipHash, (string) ($payload['visitor_id'] ?? ''))) {
            $existing = RegistrationLinkClick::query()
                ->where('registration_link_id', $link->id)
                ->where('ip_hash', $ipHash)
                ->latest('created_at')
                ->first();

            if ($existing) {
                return [
                    'success' => true,
                    'data' => [
                        'click_id' => $existing->id,
                        'visitor_id' => $existing->visitor_id,
                        'classification_type' => $existing->classification_type,
                        'risk_score' => $existing->risk_score,
                    ],
                ];
            }
        }

        $classification = $this->classifier->classify($userAgent, (string) $ipHash, $method);

        $landingUrl = (string) ($payload['landing_url'] ?? '');
        $referrer = (string) ($payload['referrer'] ?? '');
        $referrerDomain = $this->extractDomain($referrer);

        $click = RegistrationLinkClick::create([
            'registration_link_id' => $link->id,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'referrer' => $referrer,
            'created_at' => Carbon::now(),
            'classification_type' => $classification['classification_type'],
            'classification_reason' => $classification['classification_reason'],
            'risk_score' => $classification['risk_score'],
            'ip_hash' => $ipHash,
            'visitor_id' => ($payload['visitor_id'] ?? null) ?: null,
            'session_id' => ($payload['session_id'] ?? null) ?: null,
            'method' => $method,
            'landing_url' => $landingUrl ?: null,
            'referrer_domain' => $referrerDomain ?: null,
            'utm_source' => ($payload['utm_source'] ?? null) ?: null,
            'utm_medium' => ($payload['utm_medium'] ?? null) ?: null,
            'utm_campaign' => ($payload['utm_campaign'] ?? null) ?: null,
            'utm_content' => ($payload['utm_content'] ?? null) ?: null,
            'utm_term' => ($payload['utm_term'] ?? null) ?: null,
            'is_bot' => $classification['is_bot'],
            'is_preview_bot' => $classification['is_preview_bot'],
            'is_suspicious' => $classification['is_suspicious'],
        ]);

        $this->markDuplicate((int) $link->id, $ipHash, (string) ($payload['visitor_id'] ?? ''));

        return [
            'success' => true,
            'data' => [
                'click_id' => $click->id,
                'visitor_id' => $click->visitor_id,
                'classification_type' => $click->classification_type,
                'risk_score' => $click->risk_score,
            ],
        ];
    }

    public function confirmClick(int|string $clickId, ?string $visitorId): bool
    {
        $click = RegistrationLinkClick::find($clickId);

        if (! $click) {
            return false;
        }

        if ($click->client_confirmed_at !== null) {
            return true;
        }

        $newType = $this->classifier->confirmAsHuman((string) $click->classification_type);

        $click->client_confirmed_at = Carbon::now();
        $click->classification_type = $newType;
        $click->save();

        return true;
    }

    public function markSubmitted(int|string $clickId, ?string $visitorId): bool
    {
        $click = RegistrationLinkClick::find($clickId);

        if (! $click) {
            return false;
        }

        if ($click->submitted_at !== null) {
            return true;
        }

        $click->submitted_at = Carbon::now();
        $click->save();

        return true;
    }

    public function markConverted(
        int|string $clickId,
        int|string $memberCode,
        string $registerType,
        ?string $visitorId = null
    ): bool {
        try {
            $click = RegistrationLinkClick::find($clickId);

            if (! $click) {
                return false;
            }

            if ($click->converted_at !== null) {
                return true;
            }

            $click->converted_member_id = (string) $memberCode;
            $click->converted_at = Carbon::now();
            $click->register_type = $registerType;
            $click->save();

            return true;
        } catch (\Throwable $e) {
            Log::warning('MarketingClickTrackingService: markConverted failed', [
                'click_id' => $clickId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function isDuplicate(int $linkId, ?string $ipHash, string $visitorId): bool
    {
        $key = $this->dedupeKey($linkId, $ipHash, $visitorId);

        return Cache::has($key);
    }

    private function markDuplicate(int $linkId, ?string $ipHash, string $visitorId): void
    {
        $key = $this->dedupeKey($linkId, $ipHash, $visitorId);
        Cache::put($key, 1, now()->addMinutes(30));
    }

    private function dedupeKey(int $linkId, ?string $ipHash, string $visitorId): string
    {
        if ($visitorId !== '') {
            return "marketing_click:{$linkId}:{$ipHash}:{$visitorId}";
        }

        return "marketing_click:{$linkId}:{$ipHash}";
    }

    private function extractDomain(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : '';
    }
}
