<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

class LottoAclCoverageTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_acl_contains_recent_lotto_action_routes(): void
    {
        $content = file_get_contents($this->rootPath.'/packages/Gametech/Lotto/src/Config/acl.php');

        $this->assertNotFalse($content);
        // ACL must follow the current docs-backed CRUD split policy for Lotto admin actions.
        $this->assertStringNotContainsString('admin.lotto.member_permissions.index', $content);
        $this->assertStringNotContainsString('admin.lotto.member_permissions.create', $content);
        $this->assertStringNotContainsString('admin.lotto.member_permissions.update', $content);
        $this->assertStringContainsString('admin.lotto.groups.create', $content);
        $this->assertStringContainsString('admin.lotto.groups.update', $content);
        $this->assertStringContainsString('admin.lotto.markets.create', $content);
        $this->assertStringContainsString('admin.lotto.markets.update', $content);
        $this->assertStringContainsString("'key' => 'lotto_settings.draws.open'", $content);
        $this->assertStringContainsString("'route' => 'admin.lotto.draws.open'", $content);
        $this->assertStringContainsString("'key' => 'lotto_settings.draws.close'", $content);
        $this->assertStringContainsString("'route' => 'admin.lotto.draws.close'", $content);
        $this->assertStringContainsString("'key' => 'lotto_settings.draws.settle'", $content);
        $this->assertStringContainsString("'route' => 'admin.lotto.draws.settle'", $content);
        $this->assertStringContainsString("'key' => 'lotto_settings.draws.mark_no_result'", $content);
        $this->assertStringContainsString("'route' => 'admin.lotto.draws.mark_no_result'", $content);
        $this->assertStringContainsString("'key' => 'lotto_settings.draws.auto_result_manual_retry'", $content);
        $this->assertStringContainsString("'route' => 'admin.lotto.draws.auto_result_manual_retry'", $content);
        $this->assertStringContainsString("'key' => 'lotto_settings.draws.auto_result_metrics'", $content);
        $this->assertStringContainsString("'route' => 'admin.lotto.draws.auto_result_metrics'", $content);
        $this->assertStringContainsString("'route' => 'admin.lotto.draws.cancel_all_refund'", $content);
        $this->assertStringNotContainsString('admin.lotto.rate_plans.index', $content);
        $this->assertStringNotContainsString('admin.lotto.settings.bet_types', $content);
    }
}
