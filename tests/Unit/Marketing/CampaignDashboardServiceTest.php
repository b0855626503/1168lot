<?php

namespace Tests\Unit\Marketing;

use Gametech\Marketing\Services\CampaignDashboardService;
use PHPUnit\Framework\TestCase;

class CampaignDashboardServiceTest extends TestCase
{
    public function test_service_can_be_instantiated(): void
    {
        $service = new CampaignDashboardService;

        $this->assertInstanceOf(CampaignDashboardService::class, $service);
    }

    public function test_get_financial_summary_returns_empty_for_no_member_codes(): void
    {
        $service = new CampaignDashboardService;
        $result = $service->getFinancialSummary([], '2026-05-01', '2026-05-02');

        $this->assertSame(0.0, $result['deposit_amount']);
        $this->assertSame(0, $result['deposit_count']);
        $this->assertSame(0, $result['deposit_members']);
        $this->assertSame(0.0, $result['withdraw_amount']);
        $this->assertSame(0.0, $result['net_amount']);
        $this->assertSame(0, $result['first_deposit_members']);
    }

    public function test_get_lotto_cash_returns_empty_for_no_member_codes(): void
    {
        $service = new CampaignDashboardService;
        $result = $service->getLottoCash([], '2026-05-01', '2026-05-02');

        $this->assertSame(0.0, $result['sales_amount']);
        $this->assertSame(0.0, $result['payout_amount']);
        $this->assertSame(0.0, $result['refund_amount']);
        $this->assertSame(0.0, $result['net_amount']);
    }

    public function test_get_lotto_product_returns_empty_for_no_member_codes(): void
    {
        $service = new CampaignDashboardService;
        $result = $service->getLottoProduct([], '2026-05-01', '2026-05-02');

        $this->assertSame(0, $result['ticket_count']);
        $this->assertSame(0, $result['player_count']);
        $this->assertSame(0.0, $result['sales_amount']);
        $this->assertSame(0.0, $result['win_amount']);
        $this->assertSame(0.0, $result['profit_amount']);
    }

    public function test_financial_summary_has_required_keys(): void
    {
        $service = new CampaignDashboardService;
        $result = $service->getFinancialSummary([], '2026-05-01', '2026-05-02');

        foreach (['deposit_amount', 'deposit_count', 'deposit_members', 'withdraw_amount', 'net_amount', 'first_deposit_members'] as $key) {
            $this->assertArrayHasKey($key, $result, "Key [{$key}] missing from financial summary");
        }
    }

    public function test_lotto_cash_has_required_keys(): void
    {
        $service = new CampaignDashboardService;
        $result = $service->getLottoCash([], '2026-05-01', '2026-05-02');

        foreach (['sales_amount', 'payout_amount', 'refund_amount', 'net_amount'] as $key) {
            $this->assertArrayHasKey($key, $result, "Key [{$key}] missing from lotto cash");
        }
    }

    public function test_lotto_product_has_required_keys(): void
    {
        $service = new CampaignDashboardService;
        $result = $service->getLottoProduct([], '2026-05-01', '2026-05-02');

        $expected = [
            'ticket_count', 'player_count', 'sales_amount', 'win_amount',
            'profit_amount', 'win_ticket_count', 'lose_ticket_count',
            'pending_ticket_count', 'settled_ticket_count',
        ];

        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $result, "Key [{$key}] missing from lotto product");
        }
    }

    public function test_profit_amount_equals_sales_minus_win(): void
    {
        $service = new CampaignDashboardService;
        $result = $service->getLottoProduct([], '2026-05-01', '2026-05-02');

        $this->assertSame(
            round($result['sales_amount'] - $result['win_amount'], 2),
            $result['profit_amount']
        );
    }
}
