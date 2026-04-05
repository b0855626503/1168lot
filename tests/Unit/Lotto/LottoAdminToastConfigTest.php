<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

class LottoAdminToastConfigTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_admin_layout_lotto_toasts_use_info_class_and_alert_avatar(): void
    {
        $layout = file_get_contents($this->rootPath . '/packages/Gametech/Admin/src/Resources/views/layouts/master.blade.php');

        $this->assertNotFalse($layout);
        $this->assertSame(2, substr_count($layout, "className: 'rt-toast rt-info gt-toast gt-toast-info'"));
        $this->assertSame(2, substr_count($layout, "avatar: '/assets/admin/icons/alert.webp?v=1'"));
        $this->assertStringContainsString(".listen('.lotto.draw.status.changed'", $layout);
        $this->assertStringContainsString(".listen('.lotto.ticket.list.changed'", $layout);
    }
}
