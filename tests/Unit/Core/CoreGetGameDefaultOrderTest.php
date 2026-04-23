<?php

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;

class CoreGetGameDefaultOrderTest extends TestCase
{
    public function test_get_game_default_selection_orders_by_code_ascending(): void
    {
        $coreFile = file_get_contents(dirname(__DIR__, 3).'/packages/Gametech/Core/src/Core.php');

        $this->assertNotFalse($coreFile);
        $this->assertStringContainsString("->orderBy('code', 'asc')", $coreFile);
        $this->assertStringContainsString("->findOneWhere(['enable' => 'Y', 'status_open' => 'Y'])", $coreFile);
    }
}
