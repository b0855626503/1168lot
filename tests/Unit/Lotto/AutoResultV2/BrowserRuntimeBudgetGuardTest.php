<?php

namespace Tests\Unit\Lotto\AutoResultV2;

use Gametech\Lotto\Services\AutoResultV2\Browser\BrowserRuntimeBudgetGuard;
use Tests\TestCase;

class BrowserRuntimeBudgetGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('cache.default', 'array');
        config()->set('lotto_auto_result.browser_runtime.concurrency.global', 1);
        config()->set('lotto_auto_result.browser_runtime.concurrency.per_source', 1);
        config()->set('lotto_auto_result.browser_runtime.concurrency.per_domain', 1);
    }

    public function test_rejects_when_global_budget_exceeded(): void
    {
        $guard = new BrowserRuntimeBudgetGuard();
        $first = $guard->acquire(1, 'example.com', 30);
        $second = $guard->acquire(2, 'another.com', 30);

        $this->assertTrue((bool) $first['ok']);
        $this->assertFalse((bool) $second['ok']);
        $this->assertSame('BROWSER_BUDGET_GLOBAL_EXCEEDED', $second['error_code']);

        $guard->release((array) ($first['keys'] ?? []));
    }
}

