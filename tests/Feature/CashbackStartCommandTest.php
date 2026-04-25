<?php

namespace Tests\Feature;

use App\Jobs\CashbackCalculate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashbackStartCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->prepareSchema();
        $this->seedPromotion();
        $this->mockCashbackLogger();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_daily_mode_dispatches_cashback_jobs_for_selected_business_date(): void
    {
        Bus::fake();
        $this->seedMemberWithDeposit('2026-04-25 10:30:00');

        $exitCode = Artisan::call('cashback:start', [
            '--mode' => 'daily',
            '--date' => '2026-04-25',
            '--target' => 'cashback',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('selected=1 dispatched=1', Artisan::output());

        Bus::assertDispatched(CashbackCalculate::class, function (CashbackCalculate $job): bool {
            return $job->getDateStart() === '2026-04-25 00:00:00'
                && $job->getDateEnd() === '2026-04-25 23:59:59'
                && $job->getCashbackDate() === '2026-04-25'
                && $job->getTarget() === 'cashback'
                && $job->uniqueId() === 'cashback:2026-04-25:2026-04-25 00:00:00:2026-04-25 23:59:59:cashback:1001';
        });
    }

    public function test_range_mode_dispatches_wallet_jobs_for_previous_week_window(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-04-27 10:00:00');
        $this->seedMemberWithDeposit('2026-04-20 10:30:00');

        $exitCode = Artisan::call('cashback:start', [
            '--mode' => 'range',
            '--target' => 'wallet',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('selected=1 dispatched=1', Artisan::output());

        Bus::assertDispatched(CashbackCalculate::class, function (CashbackCalculate $job): bool {
            return $job->getDateStart() === '2026-04-18 00:00:00'
                && $job->getDateEnd() === '2026-04-24 23:59:59'
                && $job->getCashbackDate() === '2026-04-18'
                && $job->getTarget() === 'wallet'
                && $job->uniqueId() === 'cashback:2026-04-18:2026-04-18 00:00:00:2026-04-24 23:59:59:wallet:1001';
        });
    }

    public function test_exclude_member_policy_skips_member_when_topup_promotion_bill_exists(): void
    {
        Bus::fake();
        config()->set('gametech.cashback.start.promo_policy', 'exclude_member');

        $this->seedMemberWithDeposit('2026-04-25 10:30:00');
        $this->seedPromoTopupBill('2026-04-25 10:35:00');

        $exitCode = Artisan::call('cashback:start', [
            '--mode' => 'daily',
            '--date' => '2026-04-25',
            '--target' => 'cashback',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('selected=0 dispatched=0', Artisan::output());
        Bus::assertNothingDispatched();
        $this->assertDatabaseHas('members_credit_log', [
            'member_code' => 1001,
            'refer_table' => 'cashback',
            'kind' => 'CASHBACK',
            'auto' => 'Y',
        ]);

        $remark = (string) DB::table('members_credit_log')->where('member_code', 1001)->value('remark');
        $this->assertStringContainsString('ตัดสิทธิ์ Cashback รอบ 2026-04-25', $remark);
        $this->assertStringContainsString('เพราะรับโปรจากการฝาก 1 รายการ', $remark);
    }

    public function test_exclude_deposit_policy_deducts_only_promo_deposit_amount(): void
    {
        Bus::fake();
        config()->set('gametech.cashback.start.promo_policy', 'exclude_deposit');

        $this->seedMemberWithDeposit('2026-04-25 10:30:00', 300, 0, 9001);
        $this->seedMemberWithDeposit('2026-04-25 11:30:00', 100, 99, 9002);
        $this->seedPromoTopupBill('2026-04-25 11:35:00');

        $exitCode = Artisan::call('cashback:start', [
            '--mode' => 'daily',
            '--date' => '2026-04-25',
            '--target' => 'cashback',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('selected=1 dispatched=1', Artisan::output());

        Bus::assertDispatched(CashbackCalculate::class, function (CashbackCalculate $job): bool {
            return (float) $job->getItem()->deposit_amount === 300.0
                && (float) $job->getItem()->promo_topup_count === 1.0
                && (float) $job->getItem()->promo_deposit_amount === 100.0;
        });
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('members');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('withdraws');
        Schema::dropIfExists('bank_payment');
        Schema::dropIfExists('members_credit_log');

        Schema::create('promotions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('enable', 1)->default('Y');
            $table->string('active', 1)->default('Y');
            $table->string('use_auto', 1)->default('Y');
            $table->decimal('bonus_percent', 10, 2)->default(0);
            $table->decimal('bonus_min', 10, 2)->default(0);
            $table->decimal('bonus_max', 10, 2)->default(0);
        });

        Schema::create('members', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
            $table->unsignedBigInteger('upline_code')->default(0);
            $table->string('user_name')->nullable();
            $table->string('name')->nullable();
            $table->decimal('balance', 10, 2)->default(0);
        });

        Schema::create('bills', function (Blueprint $table): void {
            $table->bigIncrements('code');
            $table->unsignedBigInteger('member_code')->nullable();
            $table->decimal('credit_bonus', 10, 2)->default(0);
            $table->unsignedBigInteger('pro_code')->default(0);
            $table->string('method')->nullable();
            $table->string('enable', 1)->default('Y');
            $table->unsignedTinyInteger('transfer_type')->default(1);
            $table->dateTime('date_create')->nullable();
        });

        Schema::create('withdraws', function (Blueprint $table): void {
            $table->bigIncrements('code');
            $table->unsignedBigInteger('member_code')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('enable', 1)->default('Y');
            $table->unsignedTinyInteger('status')->default(1);
            $table->dateTime('date_approve')->nullable();
        });

        Schema::create('bank_payment', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
            $table->unsignedBigInteger('member_topup')->nullable();
            $table->decimal('value', 10, 2)->default(0);
            $table->unsignedTinyInteger('bankstatus')->default(1);
            $table->unsignedBigInteger('pro_id')->default(0);
            $table->string('enable', 1)->default('Y');
            $table->unsignedTinyInteger('status')->default(1);
            $table->dateTime('date_approve')->nullable();
        });

        Schema::create('members_credit_log', function (Blueprint $table): void {
            $table->bigIncrements('code');
            $table->unsignedBigInteger('member_code')->default(0);
            $table->unsignedBigInteger('refer_code')->default(0);
            $table->string('refer_table', 10)->default('');
            $table->string('credit_type', 5)->default('W');
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('bonus', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('balance_before', 10, 2)->default(0);
            $table->decimal('balance_after', 10, 2)->default(0);
            $table->decimal('credit', 10, 2)->default(0);
            $table->decimal('credit_bonus', 10, 2)->default(0);
            $table->decimal('credit_total', 10, 2)->default(0);
            $table->decimal('credit_before', 10, 2)->default(0);
            $table->decimal('credit_after', 10, 2)->default(0);
            $table->string('kind', 10)->default('CASHBACK');
            $table->string('auto', 1)->default('Y');
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('emp_code')->default(0);
            $table->string('ip', 30)->default('SYSTEM');
            $table->decimal('amount_balance', 10, 2)->default(0);
            $table->decimal('withdraw_limit', 10, 2)->default(0);
            $table->decimal('withdraw_limit_amount', 10, 2)->default(0);
            $table->string('user_create')->default('SYSTEM');
            $table->string('user_update')->default('SYSTEM');
            $table->dateTime('date_create')->nullable();
            $table->dateTime('date_update')->nullable();
        });
    }

    private function seedPromotion(): void
    {
        DB::table('promotions')->insert([
            'id' => 'pro_cashback',
            'enable' => 'Y',
            'active' => 'Y',
            'use_auto' => 'Y',
            'bonus_percent' => 5,
            'bonus_min' => 0,
            'bonus_max' => 0,
        ]);
    }

    private function seedMemberWithDeposit(
        string $approvedAt,
        float $value = 300,
        int $proId = 0,
        int $code = 9001
    ): void {
        if (! DB::table('members')->where('code', 1001)->exists()) {
            DB::table('members')->insert([
                'code' => 1001,
                'upline_code' => 5001,
                'user_name' => 'cashback.user',
                'name' => 'Cashback User',
                'balance' => 100,
            ]);
        }

        DB::table('bank_payment')->insert([
            'code' => $code,
            'member_topup' => 1001,
            'value' => $value,
            'bankstatus' => 1,
            'pro_id' => $proId,
            'enable' => 'Y',
            'status' => 1,
            'date_approve' => $approvedAt,
        ]);
    }

    private function seedPromoTopupBill(string $createdAt): void
    {
        DB::table('bills')->insert([
            'member_code' => 1001,
            'credit_bonus' => 100,
            'pro_code' => 55,
            'method' => 'TOPUP',
            'enable' => 'Y',
            'transfer_type' => 1,
            'date_create' => $createdAt,
        ]);
    }

    private function mockCashbackLogger(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->andReturnNull();
    }
}
