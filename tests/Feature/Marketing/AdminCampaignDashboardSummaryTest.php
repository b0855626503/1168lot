<?php

namespace Tests\Feature\Marketing;

use Gametech\Marketing\Http\Controllers\Admin\MarketingCampaignController;
use Gametech\Marketing\Repositories\MarketingCampaignRepository;
use Gametech\Marketing\Repositories\MarketingTeamRepository;
use Gametech\Marketing\Repositories\RegistrationLinkRepository;
use Gametech\Marketing\Services\CampaignDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class AdminCampaignDashboardSummaryTest extends TestCase
{
    public function test_dashboard_summary_returns_error_for_nonexistent_campaign(): void
    {
        $campaignRepo = Mockery::mock(MarketingCampaignRepository::class);
        $campaignRepo->shouldReceive('find')->with(999)->andReturn(null);

        $controller = new MarketingCampaignController(
            $campaignRepo,
            Mockery::mock(MarketingTeamRepository::class),
            Mockery::mock(RegistrationLinkRepository::class),
            Mockery::mock(CampaignDashboardService::class),
        );

        $request = Request::create('/marketing_campaign/999/dashboard/summary', 'GET');
        $response = $controller->dashboardSummary($request, 999);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode((string) $response->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function test_dashboard_summary_returns_data_for_existing_campaign(): void
    {
        $fakeCampaign = (object) ['id' => 42, 'name' => 'Test Campaign'];
        $today = now()->toDateString();

        $dashboardData = [
            'campaign_id' => 42,
            'date_start' => '2026-05-01',
            'date_end' => '2026-05-02',
            'generated_at' => now()->toIso8601String(),
            'cache_ttl' => 40,
            'financial' => ['deposit_amount' => 0.0, 'bonus_amount' => 0.0],
            'lotto_cash' => ['sales_amount' => 0.0],
            'lotto_product' => ['ticket_count' => 0],
            'register' => ['registered_in_range' => 0],
            'clicks' => ['click_total' => 0],
            'recent_lotto_bets' => [],
            'latest_registers' => [],
            'recent_clicks' => [],
        ];

        $campaignRepo = Mockery::mock(MarketingCampaignRepository::class);
        $campaignRepo->shouldReceive('find')->with(42)->andReturn($fakeCampaign);

        $dashboardService = Mockery::mock(CampaignDashboardService::class);
        $dashboardService->shouldReceive('getDashboard')
            ->once()
            ->withArgs(function (int $id, string $start, string $end): bool {
                return $id === 42 && $start <= $end;
            })
            ->andReturn($dashboardData);

        $controller = new MarketingCampaignController(
            $campaignRepo,
            Mockery::mock(MarketingTeamRepository::class),
            Mockery::mock(RegistrationLinkRepository::class),
            $dashboardService,
        );

        $request = Request::create('/marketing_campaign/42/dashboard/summary', 'GET', [
            'date_start' => '2026-05-01',
            'date_end' => '2026-05-02',
        ]);

        $response = $controller->dashboardSummary($request, 42);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode((string) $response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('campaign_id', $data);
        $this->assertSame(42, $data['campaign_id']);
        $this->assertArrayHasKey('financial', $data);
        $this->assertArrayHasKey('bonus_amount', $data['financial']);
        foreach ([
            'lotto_cash',
            'lotto_product',
            'register',
            'clicks',
            'recent_lotto_bets',
            'latest_registers',
            'recent_clicks',
        ] as $section) {
            $this->assertArrayHasKey($section, $data);
        }
    }

    public function test_dashboard_summary_defaults_to_today_when_no_dates_given(): void
    {
        $fakeCampaign = (object) ['id' => 7, 'name' => 'Campaign 7'];
        $today = now()->toDateString();

        $campaignRepo = Mockery::mock(MarketingCampaignRepository::class);
        $campaignRepo->shouldReceive('find')->with(7)->andReturn($fakeCampaign);

        $dashboardService = Mockery::mock(CampaignDashboardService::class);
        $dashboardService->shouldReceive('getDashboard')
            ->once()
            ->with(7, $today, $today)
            ->andReturn(['campaign_id' => 7, 'date_start' => $today, 'date_end' => $today]);

        $controller = new MarketingCampaignController(
            $campaignRepo,
            Mockery::mock(MarketingTeamRepository::class),
            Mockery::mock(RegistrationLinkRepository::class),
            $dashboardService,
        );

        $request = Request::create('/marketing_campaign/7/dashboard/summary', 'GET');
        $response = $controller->dashboardSummary($request, 7);

        $data = json_decode((string) $response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function test_dashboard_summary_returns_422_for_invalid_date_range(): void
    {
        $fakeCampaign = (object) ['id' => 7, 'name' => 'Campaign 7'];

        $campaignRepo = Mockery::mock(MarketingCampaignRepository::class);
        $campaignRepo->shouldReceive('find')->with(7)->andReturn($fakeCampaign);

        $dashboardService = Mockery::mock(CampaignDashboardService::class);
        $dashboardService->shouldNotReceive('getDashboard');

        $controller = new MarketingCampaignController(
            $campaignRepo,
            Mockery::mock(MarketingTeamRepository::class),
            Mockery::mock(RegistrationLinkRepository::class),
            $dashboardService,
        );

        $request = Request::create('/marketing_campaign/7/dashboard/summary', 'GET', [
            'date_start' => 'not-a-date',
            'date_end' => '2026-05-02',
        ]);

        $response = $controller->dashboardSummary($request, 7);

        $this->assertSame(422, $response->status());
        $data = json_decode((string) $response->getContent(), true);
        $this->assertFalse($data['success']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
