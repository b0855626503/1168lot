<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

class LottoGroupPackageContractGuardTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_package_resolver_enforces_no_fallback_and_group_guard(): void
    {
        $content = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Services/LottoPackageResolver.php');

        $this->assertNotFalse($content);
        $this->assertStringContainsString('packageNotInGroup', $content);
        $this->assertStringContainsString('packageInactive', $content);
        $this->assertStringContainsString('betTypeNotConfigured', $content);
        $this->assertStringNotContainsString('resolveMarketSetting', $content);
        $this->assertStringNotContainsString('resolvePayout(', $content);
    }

    public function test_bet_service_requires_package_id_and_snapshots_package_fields(): void
    {
        $content = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Services/BetService.php');

        $this->assertNotFalse($content);
        $this->assertStringContainsString('packageRequired', $content);
        $this->assertStringContainsString('package_id_at_time', $content);
        $this->assertStringContainsString('package_name_at_time', $content);
        $this->assertStringContainsString('calculated_values_at_bet_time', $content);
    }

    public function test_bet_controller_has_package_error_code_mapping(): void
    {
        $content = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Http/Controllers/Api/BetController.php');

        $this->assertNotFalse($content);
        $this->assertStringContainsString("'package_id' => ['required', 'integer', 'exists:lotto_group_packages,id']", $content);
        $this->assertStringContainsString('error_code', $content);
        $this->assertStringContainsString('LottoPackageException', $content);
    }
}

