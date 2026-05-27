<?php

namespace Tests\Feature\Marketing;

use Gametech\Marketing\Services\CampaignDashboardService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CampaignDashboardServiceRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        foreach ([
            'registration_link_clicks',
            'registration_links',
            'wallet_transactions',
            'lotto_tickets',
            'lotto_draws',
            'lotto_markets',
            'withdraws',
            'bills',
            'payments_promotion',
            'bank_payment',
            'members',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_dashboard_summary_scopes_campaign_data_and_calculates_kpis_correctly(): void
    {
        $this->seedCampaignFixture();

        $service = new CampaignDashboardService;
        $summary = $service->getDashboard(1, '2026-05-01', '2026-05-02');

        $this->assertSame(150.0, (float) $summary['financial']['deposit_amount']);
        $this->assertSame(40.0, (float) $summary['financial']['withdraw_amount']);
        $this->assertSame(35.0, (float) $summary['financial']['bonus_amount']);
        $this->assertSame(110.0, (float) $summary['financial']['net_amount']);

        $this->assertSame(55.0, (float) $summary['lotto_product']['sales_amount']);
        $this->assertSame(10.0, (float) $summary['lotto_product']['win_amount']);
        $this->assertSame(45.0, (float) $summary['lotto_product']['profit_amount']);
        $this->assertSame(3, (int) $summary['lotto_product']['ticket_count']);

        $this->assertSame(3, (int) $summary['clicks']['click_total']);
        $this->assertSame(1, (int) $summary['clicks']['click_human']);
        $this->assertSame(1, (int) $summary['clicks']['click_bot']);
        $this->assertSame(1, (int) $summary['clicks']['converted_count']);
        $this->assertSame(100.0, (float) $summary['clicks']['conversion_rate']);

        $this->assertCount(3, $summary['recent_lotto_bets']);
        $this->assertCount(2, $summary['latest_registers']);
        $this->assertCount(3, $summary['recent_clicks']);
    }

    public function test_dashboard_cache_and_date_range_filter_work(): void
    {
        $this->seedCampaignFixture();
        $service = new CampaignDashboardService;

        $first = $service->getDashboard(1, '2026-05-01', '2026-05-02');
        DB::table('bank_payment')->insert([
            'member_topup' => 'A1001',
            'value' => 777,
            'status' => 1,
            'enable' => 'Y',
            'date_create' => '2026-05-02 11:00:00',
        ]);
        $cached = $service->getDashboard(1, '2026-05-01', '2026-05-02');

        $this->assertSame((float) $first['financial']['deposit_amount'], (float) $cached['financial']['deposit_amount']);
        $this->assertSame((string) $first['generated_at'], (string) $cached['generated_at']);

        $singleDay = $service->getDashboard(1, '2026-05-01', '2026-05-01');
        $this->assertSame(100.0, (float) $singleDay['financial']['deposit_amount']);
    }

    private function prepareSchema(): void
    {
        Schema::create('members', function (Blueprint $table): void {
            $table->string('code')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('tel')->nullable();
            $table->dateTime('date_regis')->nullable();
        });

        Schema::create('bank_payment', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('member_topup')->nullable();
            $table->decimal('value', 15, 2)->default(0);
            $table->integer('status')->nullable();
            $table->string('enable', 1)->nullable();
            $table->dateTime('date_create')->nullable();
        });

        Schema::create('payments_promotion', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('member_code')->nullable();
            $table->decimal('credit_bonus', 15, 2)->default(0);
            $table->integer('pro_code')->nullable();
            $table->string('enable', 1)->nullable();
            $table->dateTime('date_create')->nullable();
        });

        Schema::create('bills', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('member_code')->nullable();
            $table->decimal('credit_bonus', 15, 2)->default(0);
            $table->integer('pro_code')->nullable();
            $table->integer('transfer_type')->nullable();
            $table->string('enable', 1)->nullable();
            $table->dateTime('date_create')->nullable();
        });

        Schema::create('withdraws', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('member_code')->nullable();
            $table->string('status', 32)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->dateTime('date_approve')->nullable();
        });

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
        });

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('member_id')->nullable();
            $table->unsignedBigInteger('draw_id')->nullable();
            $table->decimal('total_bet_amount', 15, 2)->default(0);
            $table->decimal('total_win_amount', 15, 2)->default(0);
            $table->string('status', 32)->nullable();
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('status', 32)->nullable();
            $table->string('direction', 16)->nullable();
            $table->string('ref_type', 64)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('registration_links', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('campaign_id')->nullable();
        });

        Schema::create('registration_link_clicks', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('registration_link_id');
            $table->string('classification_type', 32)->default('unknown');
            $table->string('classification_reason')->nullable();
            $table->unsignedTinyInteger('risk_score')->default(0);
            $table->string('referrer_domain')->nullable();
            $table->string('device_type')->nullable();
            $table->string('browser_name')->nullable();
            $table->string('os_name')->nullable();
            $table->string('visitor_id')->nullable();
            $table->string('converted_member_id')->nullable();
            $table->dateTime('created_at')->nullable();
        });
    }

    private function seedCampaignFixture(): void
    {
        DB::table('members')->insert([
            ['code' => 'A1001', 'campaign_id' => 1, 'user_name' => 'a1', 'tel' => '0811111111', 'date_regis' => '2026-05-01 10:00:00'],
            ['code' => 'A1002', 'campaign_id' => 1, 'user_name' => 'a2', 'tel' => '0822222222', 'date_regis' => '2026-05-02 10:00:00'],
            ['code' => 'B2001', 'campaign_id' => 2, 'user_name' => 'b1', 'tel' => '0833333333', 'date_regis' => '2026-05-02 10:00:00'],
        ]);

        DB::table('bank_payment')->insert([
            ['member_topup' => 'A1001', 'value' => 100, 'status' => 1, 'enable' => 'Y', 'date_create' => '2026-05-01 11:00:00'],
            ['member_topup' => 'A1002', 'value' => 50, 'status' => 1, 'enable' => 'Y', 'date_create' => '2026-05-02 11:00:00'],
            ['member_topup' => 'A1001', 'value' => 999, 'status' => 1, 'enable' => 'Y', 'date_create' => '2026-04-30 11:00:00'],
            ['member_topup' => 'B2001', 'value' => 500, 'status' => 1, 'enable' => 'Y', 'date_create' => '2026-05-02 11:00:00'],
        ]);

        DB::table('payments_promotion')->insert([
            ['member_code' => 'A1001', 'credit_bonus' => 10, 'pro_code' => 6, 'enable' => 'Y', 'date_create' => '2026-05-01 12:00:00'],
            ['member_code' => 'B2001', 'credit_bonus' => 99, 'pro_code' => 6, 'enable' => 'Y', 'date_create' => '2026-05-01 12:00:00'],
        ]);

        DB::table('bills')->insert([
            ['member_code' => 'A1002', 'credit_bonus' => 20, 'pro_code' => 2, 'transfer_type' => 1, 'enable' => 'Y', 'date_create' => '2026-05-02 12:00:00'],
            ['member_code' => 'A1002', 'credit_bonus' => 5, 'pro_code' => 2, 'transfer_type' => 2, 'enable' => 'Y', 'date_create' => '2026-05-02 13:00:00'],
            ['member_code' => 'B2001', 'credit_bonus' => 88, 'pro_code' => 2, 'transfer_type' => 1, 'enable' => 'Y', 'date_create' => '2026-05-02 12:00:00'],
        ]);

        DB::table('withdraws')->insert([
            ['member_code' => 'A1001', 'status' => 'complete', 'amount' => 40, 'date_approve' => '2026-05-02 14:00:00'],
            ['member_code' => 'B2001', 'status' => 'complete', 'amount' => 400, 'date_approve' => '2026-05-02 14:00:00'],
        ]);

        DB::table('lotto_markets')->insert([['id' => 1, 'name' => 'Thai']]);
        DB::table('lotto_draws')->insert([['id' => 1, 'market_id' => 1, 'draw_date' => '2026-05-02']]);
        DB::table('lotto_tickets')->insert([
            ['member_id' => 'A1001', 'draw_id' => 1, 'total_bet_amount' => 30, 'total_win_amount' => 10, 'status' => 'resulted', 'created_at' => '2026-05-02 10:00:00'],
            ['member_id' => 'A1002', 'draw_id' => 1, 'total_bet_amount' => 20, 'total_win_amount' => 0, 'status' => 'resulted', 'created_at' => '2026-05-02 10:30:00'],
            ['member_id' => 'A1001', 'draw_id' => 1, 'total_bet_amount' => 5, 'total_win_amount' => 0, 'status' => 'active', 'created_at' => '2026-05-02 11:00:00'],
            ['member_id' => 'B2001', 'draw_id' => 1, 'total_bet_amount' => 500, 'total_win_amount' => 0, 'status' => 'resulted', 'created_at' => '2026-05-02 11:00:00'],
        ]);

        DB::table('registration_links')->insert([
            ['id' => 1, 'campaign_id' => 1],
            ['id' => 2, 'campaign_id' => 2],
        ]);

        DB::table('registration_link_clicks')->insert([
            ['registration_link_id' => 1, 'classification_type' => 'human', 'visitor_id' => 'v1', 'converted_member_id' => 'A1001', 'created_at' => '2026-05-02 15:00:00'],
            ['registration_link_id' => 1, 'classification_type' => 'bot', 'visitor_id' => 'v2', 'converted_member_id' => null, 'created_at' => '2026-05-02 15:01:00'],
            ['registration_link_id' => 1, 'classification_type' => 'unknown', 'visitor_id' => null, 'converted_member_id' => null, 'created_at' => '2026-05-02 15:02:00'],
            ['registration_link_id' => 2, 'classification_type' => 'human', 'visitor_id' => 'v9', 'converted_member_id' => 'B2001', 'created_at' => '2026-05-02 15:03:00'],
        ]);
    }
}
