<?php

namespace Tests\Feature\Marketing;

use Gametech\Marketing\Services\CampaignDashboardService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CampaignDashboardServiceBonusAmountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('bills');
        Schema::dropIfExists('payments_promotion');
        Schema::dropIfExists('bank_payment');
        Schema::dropIfExists('members');
        parent::tearDown();
    }

    public function test_financial_summary_includes_bonus_amount_with_campaign_member_scope(): void
    {
        DB::table('members')->insert([
            ['id' => 1, 'code' => '1001', 'campaign_id' => 77],
            ['id' => 2, 'code' => '1002', 'campaign_id' => 77],
            ['id' => 3, 'code' => '2001', 'campaign_id' => 88],
        ]);

        DB::table('payments_promotion')->insert([
            [
                'member_code' => '1001',
                'credit_bonus' => 100,
                'pro_code' => 6,
                'enable' => 'Y',
                'date_create' => '2026-05-02 10:00:00',
            ],
            [
                'member_code' => '2001',
                'credit_bonus' => 999,
                'pro_code' => 6,
                'enable' => 'Y',
                'date_create' => '2026-05-02 10:00:00',
            ],
        ]);

        DB::table('bills')->insert([
            [
                'member_code' => '1002',
                'credit_bonus' => 40,
                'pro_code' => 3,
                'transfer_type' => 1,
                'enable' => 'Y',
                'date_create' => '2026-05-02 14:00:00',
            ],
            [
                'member_code' => '1002',
                'credit_bonus' => 20,
                'pro_code' => 3,
                'transfer_type' => 2,
                'enable' => 'Y',
                'date_create' => '2026-05-02 16:00:00',
            ],
            [
                'member_code' => '2001',
                'credit_bonus' => 500,
                'pro_code' => 3,
                'transfer_type' => 1,
                'enable' => 'Y',
                'date_create' => '2026-05-02 12:00:00',
            ],
        ]);

        $service = new CampaignDashboardService;
        $summary = $service->getFinancialSummary(['1001', '1002'], '2026-05-02', '2026-05-02');

        $this->assertArrayHasKey('bonus_amount', $summary);
        $this->assertSame(160.0, (float) $summary['bonus_amount']);
    }

    private function prepareSchema(): void
    {
        Schema::create('members', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code');
            $table->unsignedBigInteger('campaign_id')->nullable();
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

        Schema::create('bank_payment', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('member_topup')->nullable();
            $table->decimal('value', 15, 2)->default(0);
            $table->integer('status')->nullable();
            $table->string('enable', 1)->nullable();
            $table->dateTime('date_create')->nullable();
        });
    }
}
