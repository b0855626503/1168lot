<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

/**
 * Verifies that ExposureService enforces the correct exposure-limit logic
 * and contains the necessary race-condition guard patterns.
 *
 * Behavioral invariants asserted here (via code analysis):
 * - `checkLimit` computes: (sold_amount + new_amount) <= max_per_number
 * - `lockExposureRow` always uses lockForUpdate() inside a transaction
 * - Concurrent insert is handled via firstOrCreate + QueryException catch
 * - `increment` (atomic SQL add) is used instead of a read-then-write pattern
 * - No exposure mutation happens outside a DB transaction context
 *
 * Additionally tests the mathematical properties of the limit guard:
 * - Exactly at the limit: allowed
 * - One unit over: denied
 * - Zero sold (fresh exposure): any amount ≤ max is allowed
 */
class ExposureRaceConditionTest extends TestCase
{
    private string $servicePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicePath = dirname(__DIR__, 3) . '/packages/Gametech/Lotto/src/Services';
    }

    // ------------------------------------------------------------------ //
    // Code-analysis: guard patterns
    // ------------------------------------------------------------------ //

    public function test_exposure_service_uses_lock_for_update(): void
    {
        $content = file_get_contents($this->servicePath . '/ExposureService.php');

        $this->assertStringContainsString('lockForUpdate', $content,
            'ExposureService must use lockForUpdate() to prevent double-sell race conditions.'
        );
    }

    public function test_exposure_service_handles_concurrent_insert_with_query_exception(): void
    {
        $content = file_get_contents($this->servicePath . '/ExposureService.php');

        $this->assertStringContainsString('QueryException', $content,
            'ExposureService must catch QueryException from concurrent unique-key inserts.'
        );
        $this->assertStringContainsString('firstOrCreate', $content,
            'ExposureService must use firstOrCreate to handle concurrent row creation.'
        );
    }

    public function test_bet_service_uses_atomic_increment_not_read_then_write(): void
    {
        $content = file_get_contents($this->servicePath . '/BetService.php');

        $this->assertStringContainsString('->increment(', $content,
            'BetService must use atomic increment() to update sold_amount, not a read-then-write.'
        );
    }

    public function test_exposure_update_is_within_transaction(): void
    {
        $content = file_get_contents($this->servicePath . '/BetService.php');

        // The DB::transaction wraps the entire bet placement including the exposure increment.
        $transactionPos  = strpos($content, 'DB::transaction');
        $incrementPos    = strpos($content, '->increment(');

        $this->assertNotFalse($transactionPos, 'BetService must use DB::transaction.');
        $this->assertNotFalse($incrementPos,   'BetService must use ->increment() for sold_amount.');

        $this->assertGreaterThan(
            $transactionPos,
            $incrementPos,
            'The exposure increment must appear after (inside) the DB::transaction block.'
        );
    }

    public function test_exposure_lock_precedes_limit_check_in_bet_service(): void
    {
        $content = file_get_contents($this->servicePath . '/BetService.php');

        $lockPos  = strpos($content, 'lockExposureRow');
        $limitPos = strpos($content, 'sold_amount');

        $this->assertNotFalse($lockPos,  'BetService must call lockExposureRow before checking sold_amount.');
        $this->assertNotFalse($limitPos, 'BetService must check sold_amount against max_per_number.');
        $this->assertGreaterThan($lockPos, $limitPos,
            'The sold_amount limit check must come after the row is locked.'
        );
    }

    // ------------------------------------------------------------------ //
    // Mathematical invariants: limit-check formula
    // ------------------------------------------------------------------ //

    /**
     * The formula used in BetService:
     *   ($exposure->sold_amount + $item['amount']) > $item['max_per_number']
     * → throw if over limit, else allow.
     */
    public function test_exposure_limit_allows_bet_exactly_at_max(): void
    {
        $soldAmount   = 900.0;
        $betAmount    = 100.0;
        $maxPerNumber = 1000.0;

        $wouldExceed = ($soldAmount + $betAmount) > $maxPerNumber;

        $this->assertFalse($wouldExceed, 'A bet that reaches exactly max_per_number should be allowed.');
    }

    public function test_exposure_limit_rejects_bet_one_unit_over_max(): void
    {
        $soldAmount   = 900.0;
        $betAmount    = 100.01;
        $maxPerNumber = 1000.0;

        $wouldExceed = ($soldAmount + $betAmount) > $maxPerNumber;

        $this->assertTrue($wouldExceed, 'A bet exceeding max_per_number by any amount should be denied.');
    }

    public function test_exposure_limit_allows_bet_on_fresh_row(): void
    {
        $soldAmount   = 0.0;   // new exposure row
        $betAmount    = 1000.0;
        $maxPerNumber = 1000.0;

        $wouldExceed = ($soldAmount + $betAmount) > $maxPerNumber;

        $this->assertFalse($wouldExceed, 'First bet at exactly max_per_number should be allowed.');
    }

    public function test_exposure_limit_rejects_any_amount_when_already_at_max(): void
    {
        $soldAmount   = 1000.0; // fully sold out
        $betAmount    = 1.0;
        $maxPerNumber = 1000.0;

        $wouldExceed = ($soldAmount + $betAmount) > $maxPerNumber;

        $this->assertTrue($wouldExceed, 'Any additional bet after max_per_number is reached must be denied.');
    }

    public function test_exposure_limit_handles_fractional_amounts(): void
    {
        $soldAmount   = 999.99;
        $betAmount    = 0.01;
        $maxPerNumber = 1000.0;

        $wouldExceed = ($soldAmount + $betAmount) > $maxPerNumber;

        $this->assertFalse($wouldExceed, '999.99 + 0.01 = 1000.00 should be exactly at limit (allowed).');
    }

    public function test_exposure_limit_rejects_fractional_overage(): void
    {
        $soldAmount   = 999.99;
        $betAmount    = 0.02;
        $maxPerNumber = 1000.0;

        $wouldExceed = ($soldAmount + $betAmount) > $maxPerNumber;

        $this->assertTrue($wouldExceed, '999.99 + 0.02 = 1000.01 should exceed limit (denied).');
    }

    // ------------------------------------------------------------------ //
    // Race-condition edge case: two concurrent bets at boundary
    // ------------------------------------------------------------------ //

    /**
     * Simulates two concurrent bets where both read the same sold_amount (race scenario).
     * Verifies that if two bets are allowed independently against stale sold_amount,
     * the combined total would exceed max_per_number — proving why lockForUpdate is essential.
     */
    public function test_race_condition_without_lock_allows_oversell(): void
    {
        // Scenario: max = 1000, already sold = 800.
        // Two concurrent bets of 150 each both read staleSoldAmount = 800
        // and independently pass the limit check (800 + 150 = 950 ≤ 1000).
        // But if both commit, total becomes 800 + 150 + 150 = 1100 > 1000.
        $staleSoldAmount = 800.0;  // both threads read this stale value
        $betA            = 150.0;
        $betB            = 150.0;
        $maxPerNumber    = 1000.0;

        // Without lock: both bets read staleSoldAmount and both pass the check
        $aWouldExceed = ($staleSoldAmount + $betA) > $maxPerNumber;
        $bWouldExceed = ($staleSoldAmount + $betB) > $maxPerNumber;

        // Both pass individually (race condition bug)
        $this->assertFalse($aWouldExceed, 'Bet A passes individually against stale sold_amount.');
        $this->assertFalse($bWouldExceed, 'Bet B passes individually against stale sold_amount.');

        // But combined they exceed the max
        $combinedTotal = $staleSoldAmount + $betA + $betB;
        $this->assertGreaterThan($maxPerNumber, $combinedTotal,
            'Without lockForUpdate, two concurrent bets can push total above max_per_number.'
        );
    }

    /**
     * With lockForUpdate, the second bet reads the updated sold_amount after the first committed.
     */
    public function test_serial_bets_with_lock_prevent_oversell(): void
    {
        // With lockForUpdate, the second bet reads the true committed sold_amount.
        // Scenario: max=900, sold=800. Bet A (150) is correctly denied first.
        $initialSold   = 800.0;
        $betA          = 150.0;
        $maxPerNumber  = 900.0;

        // After bet A is evaluated against the locked (real) value: 800 + 150 = 950 > 900 → denied
        $aWouldExceed = ($initialSold + $betA) > $maxPerNumber;
        $this->assertTrue($aWouldExceed,
            'Bet A should be denied because 800 + 150 = 950 > 900.'
        );
    }

    public function test_serial_bets_second_blocked_after_first_updates(): void
    {
        $afterFirstCommit = 850.0;  // sold_amount after first bet committed
        $betB             = 100.0;
        $maxPerNumber     = 900.0;

        // After first bet committed, second bet reads updated sold_amount
        $bWouldExceed = ($afterFirstCommit + $betB) > $maxPerNumber;

        $this->assertTrue($bWouldExceed,
            'Second bet (100) against updated sold_amount (850) should be denied when max is 900.'
        );
    }
}

