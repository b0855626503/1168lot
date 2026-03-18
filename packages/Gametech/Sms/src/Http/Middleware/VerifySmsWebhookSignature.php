<?php

namespace Gametech\Sms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class VerifySmsWebhookSignature
{
    public function handle(Request $request, Closure $next)
    {
        $provider = $this->resolveProvider($request);

        // 1) Shared token (ถ้าตั้งไว้) => ต้องผ่านก่อนเสมอ
        if ($this->tokenConfigured($provider)) {
            if (! $this->verifySharedToken($request, $provider)) {
                return response()->json(['ok' => false, 'error' => 'UNAUTHORIZED_WEBHOOK_TOKEN'], 401);
            }

            return $next($request);
        }

        // 2) Signature (ถ้าเปิดไว้) => verify
        if ($this->signatureEnabled($provider)) {
            if (! $this->verifySignature($request, $provider)) {
                return response()->json([
                    'ok' => false,
                    'error' => 'INVALID_SIGNATURE',
                ], 401);
            }
        }

        // 3) ถ้าไม่ได้ตั้ง token และไม่ได้เปิด signature => ผ่าน (แต่คุณควรตั้งอย่างใดอย่างหนึ่งใน production)
        return $next($request);
    }

    private function resolveProvider(Request $request): string
    {
        $provider = (string) $request->route('provider');

        return $provider !== ''
            ? strtolower(trim($provider))
            : strtolower(config('sms.default', 'vonage'));
    }

    private function tokenConfigured(string $provider): bool
    {
        return (string) config("sms.providers.$provider.webhooks.dlr.token", '') !== '';
    }

    private function verifySharedToken(Request $request, string $provider): bool
    {
        $expected = (string) config("sms.providers.$provider.webhooks.dlr.token", '');
        if ($expected === '') {
            return false;
        }

        $provided =
            (string) $request->header('X-Webhook-Token', '') ?:
                (string) $request->query('token', '') ?:
                    (string) $request->input('token', '');

        return $provided !== '' && hash_equals($expected, $provided);
    }

    private function signatureEnabled(string $provider): bool
    {
        return (bool) config(
            "sms.providers.$provider.webhooks.dlr.signature.enabled",
            false
        );
    }

    private function verifySignature(Request $request, string $provider): bool
    {
        switch ($provider) {
            case 'vonage':
                return $this->verifyVonageSignature($request, $provider);

            default:
                return false;
        }
    }

    /**
     * Vonage SMS API signature verification
     */
    private function verifyVonageSignature(Request $request, string $provider): bool
    {
        $secret = (string) config("sms.providers.$provider.webhooks.dlr.signature.secret", '');
        if ($secret === '') {
            return false;
        }

        $method = strtolower(
            (string) config("sms.providers.$provider.webhooks.dlr.signature.method", 'md5hash')
        );

        $tolerance = (int) config(
            "sms.providers.$provider.webhooks.dlr.signature.timestamp_tolerance",
            300
        );

        // Vonage ส่ง query string เป็นหลัก
        $params = $request->query();

        $sig = (string) Arr::get($params, 'sig', '');
        $timestamp = (int) Arr::get($params, 'timestamp', 0);

        // ถ้า provider ไม่ส่ง sig/timestamp มา => verify ไม่ได้
        if ($sig === '' || $timestamp <= 0) {
            return false;
        }

        // replay protection
        if (abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        unset($params['sig']);

        $base = $this->buildVonageBaseString($params);
        $generated = $this->generateVonageSignature($base, $secret, $method);

        return $generated !== '' && hash_equals(strtolower($sig), strtolower($generated));
    }

    private function buildVonageBaseString(array $params): string
    {
        ksort($params);

        $parts = [];

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif ($value === null) {
                $value = '';
            } else {
                $value = (string) $value;
            }

            $value = str_replace(['&', '='], '_', $value);

            $parts[] = $key . '=' . $value;
        }

        return '&' . implode('&', $parts);
    }

    private function generateVonageSignature(string $base, string $secret, string $method): string
    {
        if ($method === 'md5hash' || $method === 'md5') {
            return md5($base . $secret);
        }

        if (in_array($method, ['sha1', 'sha256', 'sha512'], true)) {
            return hash_hmac($method, $base, $secret);
        }

        return '';
    }
}
