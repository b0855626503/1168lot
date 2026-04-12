<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AdminSuperadminPrivilegeEscalationGuardTest extends TestCase
{
    private string $adminModelPath;

    private string $adminControllerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $basePath = dirname(__DIR__, 2);
        $this->adminModelPath = $basePath.'/packages/Gametech/Admin/src/Models/Admin.php';
        $this->adminControllerPath = $basePath.'/packages/Gametech/Admin/src/Http/Controllers/AdminController.php';
    }

    public function test_admin_model_is_not_mass_assignable_for_superadmin_flag(): void
    {
        $content = file_get_contents($this->adminModelPath);

        $this->assertStringNotContainsString("'superadmin',", $content);
    }

    public function test_admin_controller_strips_superadmin_from_create_and_update_payloads(): void
    {
        $content = file_get_contents($this->adminControllerPath);

        $this->assertSame(2, substr_count($content, "unset(\$data['superadmin']);"));
    }
}
