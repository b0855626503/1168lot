<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LottoBetPerformanceGuardTest extends TestCase
{
    private string $servicePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicePath = dirname(__DIR__, 2).'/packages/Gametech/Lotto/src/Services';
    }

    public function test_bet_service_batches_ticket_item_inserts_and_exposure_locks(): void
    {
        $content = file_get_contents($this->servicePath.'/BetService.php');

        $this->assertStringContainsString('lockExposureRows', $content);
        $this->assertStringContainsString('LottoTicketItem::query()->insert(', $content);
        $this->assertStringContainsString('packagePayloadCache', $content);
        $this->assertStringContainsString('blockModeCache', $content);
    }

    public function test_exposure_service_uses_insert_or_ignore_for_bulk_row_bootstrap(): void
    {
        $content = file_get_contents($this->servicePath.'/ExposureService.php');

        $this->assertStringContainsString('insertOrIgnore', $content);
        $this->assertStringContainsString('lockExposureRows', $content);
    }
}
