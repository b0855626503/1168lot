<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

class YeekeeHardeningRegressionContractTest extends TestCase
{
    public function test_acl_uses_rounds_route_for_yeekee_audit_permissions(): void
    {
        $rootPath = dirname(__DIR__, 3);
        $content = file_get_contents($rootPath.'/packages/Gametech/Lotto/src/Config/acl.php');
        $this->assertIsString($content);

        $this->assertStringContainsString("'key' => 'lotto.yeekee.audit.view'", $content);
        $this->assertStringContainsString("'key' => 'lotto.yeekee.audit.view_sensitive'", $content);
        $this->assertStringContainsString("'route' => 'admin.lotto.yeekee.audit.rounds'", $content);
        $this->assertStringNotContainsString("'route' => 'admin.lotto.yeekee.audit.show'", $content);
    }

    public function test_audit_modal_uses_safe_round_id_binding_and_template_url_builder(): void
    {
        $rootPath = dirname(__DIR__, 3);
        $content = file_get_contents($rootPath.'/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/draws/yeekee_audit_modal.blade.php');
        $this->assertIsString($content);

        $this->assertStringContainsString("var _showUrlTemplate = @json(route('admin.lotto.yeekee.audit.show', ['roundId' => '__ROUND_ID__']));", $content);
        $this->assertStringContainsString('var safeRoundId = toRoundId(r.id);', $content);
        $this->assertStringContainsString("data-round-id=\"' + safeRoundId + '\"", $content);
        $this->assertStringContainsString('var safeRoundId = toRoundId(roundId);', $content);
        $this->assertStringContainsString("var url = _showUrlTemplate.replace('__ROUND_ID__', String(safeRoundId));", $content);
        $this->assertStringNotContainsString('onclick="loadYeekeeAuditDetail(', $content);
        $this->assertStringNotContainsString(".replace(/\\/0\\/audit$/, '')", $content);
    }

    public function test_yeekee_shoot_service_has_rejection_and_acceptance_logging_contract(): void
    {
        $rootPath = dirname(__DIR__, 3);
        $content = file_get_contents($rootPath.'/packages/Gametech/Lotto/src/Services/YeekeeShootService.php');
        $this->assertIsString($content);

        $this->assertStringContainsString("Log::warning('yeekee.shoot.rejected'", $content);
        $this->assertStringContainsString("'reason' => 'COOLDOWN_ACTIVE'", $content);
        $this->assertStringContainsString("'reason' => 'IP_RATE_LIMIT_EXCEEDED'", $content);
        $this->assertStringContainsString("Log::info('yeekee.shoot.accepted'", $content);
    }
}
