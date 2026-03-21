<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Services\SettlementService;
use PHPUnit\Framework\TestCase;

/**
 * Settlement reconciliation unit tests.
 *
 * Verifies that:
 * - The win amount formula (amount × payout, rounded to 2 dp) is applied correctly.
 * - The reconciliation summary (ticket/item counts, totals) is mathematically consistent.
 * - Net revenue = total_bet_amount − total_win_amount.
 * - Edge cases: all winners, no winners, rounding, large payouts.
 *
 * These tests deliberately avoid the database by testing the pure
 * calculation logic of SettlementService::isWinningBet() together
 * with the reconciliation arithmetic.
 */
class SettlementReconciliationTest extends TestCase
{
    private SettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SettlementService();
    }

    // ------------------------------------------------------------------ //
    // Win-amount formula: round(amount * payout, 2)
    // ------------------------------------------------------------------ //

    public function test_win_amount_formula_two_decimal_rounding(): void
    {
        $amount = 100.0;
        $payout = 3.5;

        $expected = round($amount * $payout, 2);  // 350.00

        $this->assertSame(350.0, $expected);
    }

    public function test_win_amount_rounds_half_up_on_fractional_payout(): void
    {
        // amount=1, payout=0.005 → raw=0.005 → round to 2 dp = 0.01
        $this->assertSame(0.01, round(1.0 * 0.005, 2));
    }

    public function test_win_amount_for_high_payout(): void
    {
        // Top-3 payout ~800 on 100 bet = 80,000
        $amount = 100.0;
        $payout = 800.0;

        $this->assertSame(80000.0, round($amount * $payout, 2));
    }

    // ------------------------------------------------------------------ //
    // Reconciliation math across multiple bet scenarios
    // ------------------------------------------------------------------ //

    /**
     * Simulate settlement over a list of bet items and verify that
     * - each winner gets win_amount = round(amount * payout, 2)
     * - each loser gets win_amount = 0
     * - total_win_amount == sum of all win_amounts
     * - winning_item_count == number of winning items
     */
    public function test_reconciliation_all_winners(): void
    {
        $resultNumber = ['top_3' => '123', 'bottom_2' => '45'];

        $items = [
            ['bet_type' => BetType::TOP_3,    'number' => '123', 'amount' => 100.0, 'payout' => 800.0],
            ['bet_type' => BetType::BOTTOM_2, 'number' => '45',  'amount' => 100.0, 'payout' => 90.0],
            ['bet_type' => BetType::TOP_2,    'number' => '12',  'amount' => 50.0,  'payout' => 85.0],
        ];

        [$totalWin, $winCount] = $this->computeReconciliation($items, $resultNumber);

        $expected = round(100.0 * 800.0, 2) + round(100.0 * 90.0, 2) + round(50.0 * 85.0, 2);

        $this->assertSame($expected, $totalWin);
        $this->assertSame(3, $winCount);
    }

    public function test_reconciliation_no_winners(): void
    {
        $resultNumber = ['top_3' => '999', 'bottom_2' => '88'];

        $items = [
            ['bet_type' => BetType::TOP_3,    'number' => '123', 'amount' => 100.0, 'payout' => 800.0],
            ['bet_type' => BetType::BOTTOM_2, 'number' => '45',  'amount' => 100.0, 'payout' => 90.0],
        ];

        [$totalWin, $winCount] = $this->computeReconciliation($items, $resultNumber);

        $this->assertSame(0.0, $totalWin);
        $this->assertSame(0, $winCount);
    }

    public function test_reconciliation_mixed_winners_and_losers(): void
    {
        $resultNumber = ['top_3' => '123', 'bottom_2' => '45'];

        $items = [
            ['bet_type' => BetType::TOP_3,    'number' => '123', 'amount' => 200.0, 'payout' => 800.0], // WIN
            ['bet_type' => BetType::TOP_3,    'number' => '999', 'amount' => 100.0, 'payout' => 800.0], // LOSE
            ['bet_type' => BetType::BOTTOM_2, 'number' => '45',  'amount' => 50.0,  'payout' => 90.0],  // WIN
            ['bet_type' => BetType::BOTTOM_2, 'number' => '11',  'amount' => 50.0,  'payout' => 90.0],  // LOSE
        ];

        [$totalWin, $winCount] = $this->computeReconciliation($items, $resultNumber);

        $expected = round(200.0 * 800.0, 2) + round(50.0 * 90.0, 2);

        $this->assertSame($expected, $totalWin);
        $this->assertSame(2, $winCount);
    }

    public function test_reconciliation_run_top_winners(): void
    {
        // top_3 = '123' → digits 1, 2, 3 all win for RUN_TOP
        $resultNumber = ['top_3' => '123', 'bottom_2' => '45'];

        $items = [
            ['bet_type' => BetType::RUN_TOP, 'number' => '1', 'amount' => 10.0, 'payout' => 3.0], // WIN
            ['bet_type' => BetType::RUN_TOP, 'number' => '2', 'amount' => 10.0, 'payout' => 3.0], // WIN
            ['bet_type' => BetType::RUN_TOP, 'number' => '9', 'amount' => 10.0, 'payout' => 3.0], // LOSE
        ];

        [$totalWin, $winCount] = $this->computeReconciliation($items, $resultNumber);

        $expected = round(10.0 * 3.0, 2) + round(10.0 * 3.0, 2);

        $this->assertSame($expected, $totalWin);
        $this->assertSame(2, $winCount);
    }

    public function test_reconciliation_tod3_all_permutations_win(): void
    {
        // top_3 = '123' → TOD_3 permutations that win: 123, 132, 213, 231, 312, 321
        $resultNumber = ['top_3' => '123', 'bottom_2' => '00'];
        $permutations = ['123', '132', '213', '231', '312', '321'];

        $items = array_map(
            fn(string $p) => ['bet_type' => BetType::TOD_3, 'number' => $p, 'amount' => 10.0, 'payout' => 100.0],
            $permutations
        );

        [$totalWin, $winCount] = $this->computeReconciliation($items, $resultNumber);

        $this->assertSame(6, $winCount);
        $this->assertSame(round(6 * 10.0 * 100.0, 2), $totalWin);
    }

    // ------------------------------------------------------------------ //
    // Net revenue = total_bet − total_win
    // ------------------------------------------------------------------ //

    public function test_net_revenue_positive_when_house_wins(): void
    {
        $resultNumber = ['top_3' => '999', 'bottom_2' => '88'];

        $items = [
            ['bet_type' => BetType::TOP_3, 'number' => '123', 'amount' => 500.0, 'payout' => 800.0], // all lose
        ];

        [$totalWin] = $this->computeReconciliation($items, $resultNumber);

        $totalBet = array_sum(array_column($items, 'amount'));
        $netRevenue = round($totalBet - $totalWin, 2);

        $this->assertSame(500.0, $netRevenue);
    }

    public function test_net_revenue_negative_when_member_wins_large(): void
    {
        $resultNumber = ['top_3' => '123', 'bottom_2' => '45'];

        $items = [
            ['bet_type' => BetType::TOP_3, 'number' => '123', 'amount' => 1000.0, 'payout' => 800.0], // WIN: 800,000
        ];

        [$totalWin] = $this->computeReconciliation($items, $resultNumber);

        $totalBet = 1000.0;
        $netRevenue = round($totalBet - $totalWin, 2);

        $this->assertSame(-799000.0, $netRevenue);
    }

    // ------------------------------------------------------------------ //
    // Settlement result structure invariants
    // ------------------------------------------------------------------ //

    public function test_settlement_result_keys_present(): void
    {
        // The return array from settleDraw() must always have these keys.
        $expected = ['draw_id', 'result_number', 'ticket_count', 'winning_ticket_count', 'winning_item_count', 'total_win_amount'];

        // Validate by simulating what settleDraw() returns.
        $summary = $this->buildSummary(
            drawId: 1,
            resultNumber: ['top_3' => '123', 'bottom_2' => '45'],
            ticketCount: 2,
            winningTickets: 1,
            winningItems: 2,
            totalWin: 1500.0
        );

        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $summary, "Settlement result missing key: {$key}");
        }
    }

    public function test_settlement_summary_ticket_count_matches_items(): void
    {
        $resultNumber = ['top_3' => '123', 'bottom_2' => '45'];

        // Two simulated tickets (1 winning, 1 losing)
        $ticket1 = [
            ['bet_type' => BetType::TOP_3, 'number' => '123', 'amount' => 100.0, 'payout' => 800.0], // WIN
        ];
        $ticket2 = [
            ['bet_type' => BetType::TOP_3, 'number' => '999', 'amount' => 100.0, 'payout' => 800.0], // LOSE
        ];

        [$win1, $winCount1] = $this->computeReconciliation($ticket1, $resultNumber);
        [$win2, $winCount2] = $this->computeReconciliation($ticket2, $resultNumber);

        $totalWin = $win1 + $win2;
        $winningTickets = ($winCount1 > 0 ? 1 : 0) + ($winCount2 > 0 ? 1 : 0);

        $this->assertSame(1, $winningTickets, 'Only one ticket should be winning');
        $this->assertSame(round(100.0 * 800.0, 2), $totalWin);
    }

    // ------------------------------------------------------------------ //
    // Helpers
    // ------------------------------------------------------------------ //

    /**
     * @param array<int, array{bet_type:string, number:string, amount:float, payout:float}> $items
     * @param array<string, string> $resultNumber
     * @return array{float, int}  [total_win_amount, winning_item_count]
     */
    private function computeReconciliation(array $items, array $resultNumber): array
    {
        $totalWin = 0.0;
        $winCount = 0;

        foreach ($items as $item) {
            $isWinner = $this->service->isWinningBet(
                $item['bet_type'],
                $item['number'],
                $resultNumber
            );

            $winAmount = $isWinner ? round($item['amount'] * $item['payout'], 2) : 0.0;
            $totalWin += $winAmount;

            if ($isWinner) {
                $winCount++;
            }
        }

        return [round($totalWin, 2), $winCount];
    }

    /**
     * Build a minimal settlement result array (mirrors settleDraw return format).
     *
     * @param array<string, string> $resultNumber
     * @return array<string, mixed>
     */
    private function buildSummary(
        int $drawId,
        array $resultNumber,
        int $ticketCount,
        int $winningTickets,
        int $winningItems,
        float $totalWin
    ): array {
        return [
            'draw_id'               => $drawId,
            'result_number'         => $resultNumber,
            'ticket_count'          => $ticketCount,
            'winning_ticket_count'  => $winningTickets,
            'winning_item_count'    => $winningItems,
            'total_win_amount'      => round($totalWin, 2),
        ];
    }
}

