<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Support\ResultHash;
use PHPUnit\Framework\TestCase;

class ManualResultRelayReadinessContractTest extends TestCase
{
    public function test_result_hash_matches_expected_sha256_from_sorted_payload(): void
    {
        $payload = [
            'last_2_digits' => '78',
            'first_prize' => '123456',
            'top_2' => '56',
            'top_3' => '456',
            'bottom_2' => '78',
        ];

        $expectedPayload = $payload;
        ksort($expectedPayload);
        $expectedHash = hash('sha256', json_encode($expectedPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->assertSame($expectedHash, ResultHash::fromPayload($payload));
    }

    public function test_settlement_service_writes_relay_ready_fields_for_manual_settle_path(): void
    {
        $rootPath = dirname(__DIR__, 3);
        $content = file_get_contents($rootPath.'/packages/Gametech/Lotto/src/Services/SettlementService.php');
        $this->assertIsString($content);

        $this->assertStringContainsString("'result_fetch_status' => 'APPLIED'", $content);
        $this->assertStringContainsString("'result_hash' => ResultHash::fromPayload(\$normalizedResult)", $content);
        $this->assertStringContainsString("'result_applied_at' => now()", $content);
        $this->assertStringContainsString("'result_fetched_at' => now()", $content);
    }

    public function test_lotto_draw_controller_writes_result_hash_for_manual_no_result_paths(): void
    {
        $rootPath = dirname(__DIR__, 3);
        $content = file_get_contents($rootPath.'/packages/Gametech/Lotto/src/Http/Controllers/Admin/LottoDrawController.php');
        $this->assertIsString($content);

        $this->assertStringContainsString("'result_hash' => ResultHash::fromPayload(\$resultNumber)", $content);
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($content, "'result_hash' => ResultHash::fromPayload(\$resultNumber)"),
            'Expected both markNoResult and cancelAllRefund paths to write result_hash'
        );
    }
}
