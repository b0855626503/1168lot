<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

class LottoDrawDatatableCorrectResultActionContractTest extends TestCase
{
    public function test_datatable_action_template_contains_correct_result_visibility_contract(): void
    {
        $content = file_get_contents(
            dirname(__DIR__, 3).'/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/draws/datatables_actions.blade.php'
        );

        $this->assertIsString($content);
        $this->assertStringContainsString("bouncer()->hasPermission('lotto_settings.draws.correct_result')", $content);
        $this->assertStringContainsString("@if(\$status === 'resulted' && \$canCorrectResult)", $content);
        $this->assertStringContainsString('ออกผลใหม่', $content);
        $this->assertStringContainsString('openCorrectResultModal', $content);
    }
}
