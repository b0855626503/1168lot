<?php

namespace Gametech\FrontendApi\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Gametech\Member\Models\Member;
use Gametech\Member\Models\MemberProxy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FrontendTokenService
{
    public function issue(Member $member): array
    {
        $now = now();
        $ttlMinutes = (int) config('session.lifetime', 120);
        $expiresAt = $now->copy()->addMinutes(max(1, $ttlMinutes));

        $payload = [
            'iss' => (string) config('app.url', 'gametech'),
            'sub' => (int) $member->code,
            'iat' => $now->timestamp,
            'exp' => $expiresAt->timestamp,
            'jti' => (string) Str::uuid(),
        ];

        $token = JWT::encode($payload, $this->jwtSecret(), 'HS256');

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toDateTimeString(),
            'expires_in' => $expiresAt->timestamp - $now->timestamp,
            'payload' => $payload,
        ];
    }

    public function decode(string $token): ?array
    {
        try {
            $decoded = (array) JWT::decode($token, new Key($this->jwtSecret(), 'HS256'));
        } catch (\Throwable $e) {
            return null;
        }

        if (! isset($decoded['jti']) || $this->isBlacklisted((string) $decoded['jti'])) {
            return null;
        }

        return $decoded;
    }

    public function resolveMember(string $token): ?Member
    {
        $payload = $this->decode($token);
        if (! $payload || ! isset($payload['sub'])) {
            return null;
        }

        $member = MemberProxy::query()->where('code', (int) $payload['sub'])->first();
        if (! $member || (string) $member->enable !== 'Y') {
            return null;
        }

        return $member;
    }

    public function blacklist(array $payload): void
    {
        if (! isset($payload['jti'])) {
            return;
        }

        $exp = isset($payload['exp']) ? (int) $payload['exp'] : now()->addMinutes(120)->timestamp;
        $seconds = max(60, $exp - now()->timestamp);

        Cache::put($this->blacklistKey((string) $payload['jti']), true, now()->addSeconds($seconds));
    }

    private function isBlacklisted(string $jti): bool
    {
        return Cache::has($this->blacklistKey($jti));
    }

    private function blacklistKey(string $jti): string
    {
        return 'frontend_api:blacklist:' . $jti;
    }

    private function jwtSecret(): string
    {
        $key = (string) config('app.key');

        if (Str::startsWith($key, 'base64:')) {
            return base64_decode(substr($key, 7)) ?: substr($key, 7);
        }

        return $key;
    }
}
