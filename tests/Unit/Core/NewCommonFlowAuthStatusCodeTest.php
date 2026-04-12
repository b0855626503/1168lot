<?php

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;

class NewCommonFlowAuthStatusCodeTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_member_lookup_status_code_distinguishes_user_not_found_and_invalid_token(): void
    {
        $controller = file_get_contents($this->rootPath.'/packages/Gametech/API/src/Http/Controllers/NewCommonFlowController.php');

        $this->assertNotFalse($controller);
        $this->assertStringContainsString("->where('user_name', \$username)", $controller);
        $this->assertStringContainsString("->where('enable', 'Y')", $controller);
        $this->assertStringContainsString('$this->memberLookupStatus = 10001;', $controller);
        $this->assertStringContainsString('$this->memberLookupStatus = 30001;', $controller);
        $this->assertStringContainsString('return $this->responseData($session[\'id\'], $session[\'username\'], $session[\'productId\'], $this->memberLookupStatusCode());', $controller);
    }

    public function test_callbacks_use_normalized_status_from_request_payload(): void
    {
        $controller = file_get_contents($this->rootPath.'/packages/Gametech/API/src/Http/Controllers/NewCommonFlowController.php');

        $this->assertNotFalse($controller);
        $this->assertStringContainsString("\$status = strtoupper((string) (\$txn['status'] ?? 'SETTLED'));", $controller);
        $this->assertStringContainsString("\$status = strtoupper((string) (\$txn['status'] ?? 'ROLLBACK'));", $controller);
        $this->assertStringContainsString("\$status = strtoupper((string) (\$txn['status'] ?? 'REFUND'));", $controller);
        $this->assertStringContainsString("->where('method', \$status)", $controller);
        $this->assertStringContainsString("'con_3' => \$status", $controller);
    }
}
