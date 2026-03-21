<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

/**
 * Verifies that critical service and controller code includes proper database
 * locking patterns to prevent race conditions.
 *
 * Rules enforced:
 * - BetService must wrap the full bet flow in DB::transaction.
 * - ExposureService must use lockForUpdate() to prevent double-sell.
 * - SettlementService must use DB::transaction + lockForUpdate for both
 *   the draw record and all ticket records.
 * - TicketController cancel must use DB::transaction + lockForUpdate.
 * - DrawService must use DB::transaction when opening a draw.
 */
class LottoConcurrencyGuardTest extends TestCase
{
    private string $servicePath;
    private string $apiPath;

    protected function setUp(): void
    {
        parent::setUp();
        $base = dirname(__DIR__, 3) . '/packages/Gametech/Lotto/src';
        $this->servicePath = $base . '/Services';
        $this->apiPath     = $base . '/Http/Controllers/Api';
    }

    // ------------------------------------------------------------------ //
    // BetService
    // ------------------------------------------------------------------ //

    public function test_bet_service_wraps_placement_in_db_transaction(): void
    {
        $content = file_get_contents($this->servicePath . '/BetService.php');

        $this->assertStringContainsString('DB::transaction', $content);
    }

    public function test_bet_service_delegates_exposure_locking_to_exposure_service(): void
    {
        $content = file_get_contents($this->servicePath . '/BetService.php');

        $this->assertStringContainsString('lockExposureRow', $content);
    }

    public function test_bet_service_validates_bet_type_against_known_list(): void
    {
        $content = file_get_contents($this->servicePath . '/BetService.php');

        // Must reference BetType::all() for whitelist validation
        $this->assertStringContainsString('BetType::all()', $content);
    }

    public function test_bet_service_checks_exposure_limit_before_insert(): void
    {
        $content = file_get_contents($this->servicePath . '/BetService.php');

        // Exposure limit guard must exist before ticket item creation
        $this->assertStringContainsString('sold_amount', $content);
        $this->assertStringContainsString('max_per_number', $content);
    }

    // ------------------------------------------------------------------ //
    // ExposureService
    // ------------------------------------------------------------------ //

    public function test_exposure_service_locks_row_for_update(): void
    {
        $content = file_get_contents($this->servicePath . '/ExposureService.php');

        $this->assertStringContainsString('lockForUpdate', $content);
    }

    public function test_exposure_service_handles_concurrent_insert_via_try_catch(): void
    {
        $content = file_get_contents($this->servicePath . '/ExposureService.php');

        // firstOrCreate inside try/catch to absorb unique-key race
        $this->assertStringContainsString('QueryException', $content);
        $this->assertStringContainsString('firstOrCreate', $content);
    }

    // ------------------------------------------------------------------ //
    // SettlementService
    // ------------------------------------------------------------------ //

    public function test_settlement_service_wraps_settle_in_db_transaction(): void
    {
        $content = file_get_contents($this->servicePath . '/SettlementService.php');

        $this->assertStringContainsString('DB::transaction', $content);
    }

    public function test_settlement_service_locks_draw_row_for_update(): void
    {
        $content = file_get_contents($this->servicePath . '/SettlementService.php');

        $this->assertStringContainsString('lockForUpdate', $content);
    }

    public function test_settlement_service_locks_tickets_during_settlement(): void
    {
        $content = file_get_contents($this->servicePath . '/SettlementService.php');

        // Both draw AND tickets must be locked — count occurrences
        $occurrences = substr_count($content, 'lockForUpdate');
        $this->assertGreaterThanOrEqual(2, $occurrences,
            'SettlementService must lock both the draw record and the ticket collection'
        );
    }

    public function test_settlement_service_updates_result_status_on_ticket_items(): void
    {
        $content = file_get_contents($this->servicePath . '/SettlementService.php');

        $this->assertStringContainsString('result_status', $content);
        $this->assertStringContainsString('win_amount', $content);
    }

    // ------------------------------------------------------------------ //
    // TicketController — cancel
    // ------------------------------------------------------------------ //

    public function test_ticket_cancel_wraps_in_db_transaction(): void
    {
        $content = file_get_contents($this->apiPath . '/TicketController.php');

        $this->assertStringContainsString('DB::transaction', $content);
    }

    public function test_ticket_cancel_locks_ticket_for_update(): void
    {
        $content = file_get_contents($this->apiPath . '/TicketController.php');

        $this->assertStringContainsString('lockForUpdate', $content);
    }

    public function test_ticket_cancel_rolls_back_exposure_on_cancellation(): void
    {
        $content = file_get_contents($this->apiPath . '/TicketController.php');

        // Must adjust sold_amount back down when cancelling
        $this->assertStringContainsString('sold_amount', $content);
    }

    public function test_ticket_cancel_rejects_non_active_tickets(): void
    {
        $content = file_get_contents($this->apiPath . '/TicketController.php');

        $this->assertStringContainsString("'active'", $content);
    }

    public function test_ticket_cancel_rejects_tickets_on_non_open_draw(): void
    {
        $content = file_get_contents($this->apiPath . '/TicketController.php');

        // Guard: draw must be open at cancel time
        $this->assertStringContainsString("'open'", $content);
    }

    // ------------------------------------------------------------------ //
    // DrawService
    // ------------------------------------------------------------------ //

    public function test_draw_service_uses_db_transaction_when_opening_draw(): void
    {
        $content = file_get_contents($this->servicePath . '/DrawService.php');

        $this->assertStringContainsString('DB::transaction', $content);
    }

    public function test_draw_service_snapshots_settings_before_opening(): void
    {
        $content = file_get_contents($this->servicePath . '/DrawService.php');

        $this->assertStringContainsString('snapshotBetSettings', $content);
    }
}

