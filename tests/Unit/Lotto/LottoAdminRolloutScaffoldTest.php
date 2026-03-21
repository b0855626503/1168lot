<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

class LottoAdminRolloutScaffoldTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_admin_routes_include_rollout_and_search_member_endpoints(): void
    {
        $content = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Routes/admin.php');

        $this->assertNotFalse($content);
        $this->assertStringContainsString("groups/apply-rollout", $content);
        $this->assertStringContainsString("groups/search-members", $content);
        $this->assertStringContainsString("markets/apply-rollout", $content);
        $this->assertStringContainsString("markets/search-members", $content);
    }

    public function test_group_views_wire_batch_rollout_actions(): void
    {
        $createView = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/groups/create.blade.php');
        $addEditView = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/groups/addedit.blade.php');

        $this->assertNotFalse($createView);
        $this->assertNotFalse($addEditView);

        $this->assertStringContainsString("applyGroupBatchRollout('all')", $createView);
        $this->assertStringContainsString("applyGroupBatchRollout('selected')", $createView);
        $this->assertStringContainsString('batchRolloutFromTable', $addEditView);
        $this->assertStringContainsString('openRolloutSelector', $addEditView);
        $this->assertStringContainsString("admin.lotto.groups.search_members", $addEditView);
    }

    public function test_market_views_wire_batch_rollout_actions(): void
    {
        $createView = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/markets/create.blade.php');
        $addEditView = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/markets/addedit.blade.php');

        $this->assertNotFalse($createView);
        $this->assertNotFalse($addEditView);

        $this->assertStringContainsString("applyMarketBatchRollout('all')", $createView);
        $this->assertStringContainsString("applyMarketBatchRollout('selected')", $createView);
        $this->assertStringContainsString('batchRolloutFromTable', $addEditView);
        $this->assertStringContainsString('openRolloutSelector', $addEditView);
        $this->assertStringContainsString("admin.lotto.markets.search_members", $addEditView);
    }
}

