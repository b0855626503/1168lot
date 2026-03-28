<?php

namespace Tests\Unit\Lotto\AutoResultV2;

use Gametech\Lotto\Services\AutoResultV2\Browser\BrowserRuntimePolicyService;
use PHPUnit\Framework\TestCase;

class BrowserRuntimePolicyServiceTest extends TestCase
{
    public function test_normalize_capability_defaults_to_http_only(): void
    {
        $service = new BrowserRuntimePolicyService();

        $this->assertSame('http_only', $service->normalizeCapability(''));
        $this->assertSame('http_only', $service->normalizeCapability('unknown'));
        $this->assertSame('prefer_browser_runtime', $service->normalizeCapability('prefer_browser_runtime'));
    }

    public function test_prefer_policy_fallback_only_for_allowlist(): void
    {
        $service = new BrowserRuntimePolicyService();

        $this->assertTrue($service->shouldFallbackToHttp('prefer_browser_runtime', 'BROWSER_RUNTIME_UNAVAILABLE'));
        $this->assertTrue($service->shouldFallbackToHttp('prefer_browser_runtime', 'BROWSER_EXECUTOR_TIMEOUT'));
        $this->assertFalse($service->shouldFallbackToHttp('prefer_browser_runtime', 'NO_NETWORK_MATCH'));
        $this->assertFalse($service->shouldFallbackToHttp('require_browser_runtime', 'BROWSER_RUNTIME_UNAVAILABLE'));
    }
}

