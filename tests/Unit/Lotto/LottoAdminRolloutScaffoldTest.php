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

    public function test_admin_routes_do_not_include_rollout_and_search_member_endpoints(): void
    {
        $content = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Routes/admin.php');

        $this->assertNotFalse($content);
        $this->assertStringNotContainsString("groups/apply-rollout", $content);
        $this->assertStringNotContainsString("groups/search-members", $content);
        $this->assertStringNotContainsString("markets/apply-rollout", $content);
        $this->assertStringNotContainsString("markets/search-members", $content);
    }

    public function test_group_views_do_not_contain_batch_rollout_actions(): void
    {
        $createView = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/groups/create.blade.php');
        $addEditView = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/groups/addedit.blade.php');

        $this->assertNotFalse($createView);
        $this->assertNotFalse($addEditView);

        $this->assertStringNotContainsString("applyGroupBatchRollout('all')", $createView);
        $this->assertStringNotContainsString("applyGroupBatchRollout('selected')", $createView);
        $this->assertStringNotContainsString('batchRolloutFromTable', $addEditView);
        $this->assertStringNotContainsString('openRolloutSelector', $addEditView);
        $this->assertStringNotContainsString("admin.lotto.groups.search_members", $addEditView);
    }

    public function test_market_views_do_not_contain_batch_rollout_actions(): void
    {
        $createView = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/markets/create.blade.php');
        $addEditView = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/markets/addedit.blade.php');

        $this->assertNotFalse($createView);
        $this->assertNotFalse($addEditView);

        $this->assertStringNotContainsString("applyMarketBatchRollout('all')", $createView);
        $this->assertStringNotContainsString("applyMarketBatchRollout('selected')", $createView);
        $this->assertStringNotContainsString('batchRolloutFromTable', $addEditView);
        $this->assertStringNotContainsString('openRolloutSelector', $addEditView);
        $this->assertStringNotContainsString("admin.lotto.markets.search_members", $addEditView);
    }
}
