<?php

namespace Gametech\Lotto\Services\AutoResultV2\Browser;

final class BrowserRuntimePolicyService
{
    public const CAPABILITY_HTTP_ONLY = 'http_only';
    public const CAPABILITY_PREFER_BROWSER = 'prefer_browser_runtime';
    public const CAPABILITY_REQUIRE_BROWSER = 'require_browser_runtime';

    private const FALLBACK_ALLOWLIST = [
        'BROWSER_RUNTIME_UNAVAILABLE',
        'BROWSER_LAUNCH_FAILED',
        'BROWSER_EXECUTOR_TIMEOUT',
        'BROWSER_EXECUTOR_IO_ERROR',
    ];

    private const NON_FALLBACK_CODES = [
        'NO_NETWORK_MATCH',
        'DOM_SELECTOR_NOT_FOUND',
        'INVALID_CAPTURE_RULE',
        'INVALID_WAIT_CONFIG',
        'INVALID_PREDICATE_CONFIG',
        'CAPTURE_AMBIGUOUS_MATCH',
    ];

    public function normalizeCapability(mixed $value): string
    {
        $capability = strtolower(trim((string) $value));
        if (! in_array($capability, [
            self::CAPABILITY_HTTP_ONLY,
            self::CAPABILITY_PREFER_BROWSER,
            self::CAPABILITY_REQUIRE_BROWSER,
        ], true)) {
            return self::CAPABILITY_HTTP_ONLY;
        }

        return $capability;
    }

    public function canUseBrowserRuntime(string $capability, bool $runtimeEnabled, int $sourceId): bool
    {
        $capability = $this->normalizeCapability($capability);
        if ($capability === self::CAPABILITY_HTTP_ONLY) {
            return false;
        }

        if (! $runtimeEnabled) {
            return false;
        }

        $whitelist = (array) config('lotto_auto_result.browser_runtime.rollout.whitelist_source_ids', []);
        if ($whitelist === []) {
            return true;
        }

        return $sourceId > 0 && in_array($sourceId, array_map('intval', $whitelist), true);
    }

    public function shouldFallbackToHttp(string $capability, string $errorCode): bool
    {
        $capability = $this->normalizeCapability($capability);
        $errorCode = strtoupper(trim($errorCode));

        if ($capability !== self::CAPABILITY_PREFER_BROWSER) {
            return false;
        }

        if ($errorCode === '' || in_array($errorCode, self::NON_FALLBACK_CODES, true)) {
            return false;
        }

        return in_array($errorCode, self::FALLBACK_ALLOWLIST, true);
    }
}

