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
        $content = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Config/acl.php');

        $this->assertNotFalse($content);
        $this->assertStringContainsString("admin.lotto.groups.apply_rollout", $content);
        $this->assertStringContainsString("admin.lotto.groups.search_members", $content);
        $this->assertStringContainsString("admin.lotto.markets.apply_rollout", $content);
        $this->assertStringContainsString("admin.lotto.markets.search_members", $content);
        $this->assertStringContainsString("admin.lotto.member_permissions.create", $content);
        $this->assertStringContainsString("admin.lotto.member_permissions.update", $content);
        $this->assertStringContainsString("admin.lotto.default_settings.create", $content);
        $this->assertStringContainsString("admin.lotto.default_settings.update", $content);
        $this->assertStringContainsString("admin.lotto.member_rate_plans.create", $content);
        $this->assertStringContainsString("admin.lotto.member_rate_plans.update", $content);
        $this->assertStringContainsString("admin.lotto.number_blocks.create", $content);
        $this->assertStringContainsString("admin.lotto.number_blocks.update", $content);
    }
}

