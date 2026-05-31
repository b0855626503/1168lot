<?php

namespace Tests\Unit;

use App\Jobs\CashbackCalculate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashbackCalculateJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::dropIfExists('members_cashback');
        Schema::dropIfExists('members_credit_log');
        Schema::create('members_cashback', function (Blueprint $table): void {
            $table->bigIncrements('code');
            $table->date('date_cashback')->nullable();
            $table->unsignedBigInteger('downline_code')->nullable();
        });
        Schema::create('members_credit_log', function (Blueprint $table): void {
            $table->bigIncrements('code');
            $table->unsignedBigInteger('member_code')->default(0);
            $table->unsignedBigInteger('refer_code')->default(0);
            $table->string('refer_table', 30)->default('');
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
            $table->string('kind', 20)->default('CASHBACK');
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

    public function test_job_routes_credit_to_wallet_when_target_is_wallet(): void
    {
        $repository = new class
        {
            public array $created = [];
            public array $wallet = [];
            public array $cashback = [];

            public function create(array $attributes): object
            {
                $this->created[] = $attributes;

                return (object) ['code' => 77];
            }

            public function refillSeamlessDirect(array $data): bool
            {
                $this->wallet[] = $data;

                return true;
            }

            public function refillSeamless(array $data): bool
            {
                $this->cashback[] = $data;

                return true;
            }
        };

        $this->app->instance('Gametech\Member\Repositories\MemberCashbackRepository', $repository);

        $job = new CashbackCalculate(
            '2026-04-18 00:00:00',
            '2026-04-24 23:59:59',
            '2026-04-18',
            (object) [
                'upline_code' => 5001,
                'member_code' => 1001,
                'balance' => 100.0,
                'deposit_amount' => 300.0,
                'withdraw_amount' => 50.0,
                'bonus_amount' => 0.0,
            ],
            (object) [
                'bonus_percent' => 10,
                'bonus_min' => 0,
                'bonus_max' => 0,
            ],
            'wallet'
        );

        $job->handle();

        $this->assertCount(1, $repository->created);
        $this->assertSame('2026-04-18', $repository->created[0]['date_cashback']);
        $this->assertSame(300.0, (float) $repository->created[0]['sum_deposit']);
        $this->assertSame(50.0, (float) $repository->created[0]['sum_withdraw']);
        $this->assertSame(100.0, (float) $repository->created[0]['sum_balance']);
        $this->assertCount(1, $repository->wallet);
        $this->assertCount(0, $repository->cashback);
    }

    public function test_job_routes_credit_to_member_cashback_when_target_is_cashback(): void
    {
        $repository = new class
        {
            public array $created = [];
            public array $wallet = [];
            public array $cashback = [];

            public function create(array $attributes): object
            {
                $this->created[] = $attributes;

                return (object) ['code' => 88];
            }

            public function refillSeamlessDirect(array $data): bool
            {
                $this->wallet[] = $data;

                return true;
            }

            public function refillSeamless(array $data): bool
            {
                $this->cashback[] = $data;

                return true;
            }
        };

        $this->app->instance('Gametech\Member\Repositories\MemberCashbackRepository', $repository);

        $job = new CashbackCalculate(
            '2026-04-25 00:00:00',
            '2026-04-25 23:59:59',
            '2026-04-25',
            (object) [
                'upline_code' => 5001,
                'member_code' => 1001,
                'balance' => 100.0,
                'deposit_amount' => 300.0,
                'withdraw_amount' => 50.0,
                'bonus_amount' => 0.0,
            ],
            (object) [
                'bonus_percent' => 10,
                'bonus_min' => 0,
                'bonus_max' => 0,
            ],
            'cashback'
        );

        $job->handle();

        $this->assertCount(1, $repository->created);
        $this->assertCount(0, $repository->wallet);
        $this->assertCount(1, $repository->cashback);
        $this->assertSame('2026-04-25', $repository->cashback[0]['date_cashback']);
        $this->assertSame(300.0, (float) $repository->cashback[0]['sum_deposit']);
        $this->assertSame(50.0, (float) $repository->cashback[0]['sum_withdraw']);
        $this->assertSame(100.0, (float) $repository->cashback[0]['sum_balance']);
    }

    public function test_unique_id_includes_period_target_and_member(): void
    {
        $job = new CashbackCalculate(
            '2026-04-18 00:00:00',
            '2026-04-24 23:59:59',
            '2026-04-18',
            (object) ['member_code' => 1001],
            (object) ['bonus_percent' => 10, 'bonus_min' => 0, 'bonus_max' => 0],
            'wallet'
        );

        $this->assertSame(
            'cashback:2026-04-18:2026-04-18 00:00:00:2026-04-24 23:59:59:wallet:1001',
            $job->uniqueId()
        );
    }

    public function test_no_balance_flag_excludes_balance_from_net_amount_calculation(): void
    {
        config()->set('gametech.cashback.start.no_balance', true);

        $repository = new class
        {
            public array $created = [];
            public array $wallet = [];
            public array $cashback = [];

            public function create(array $attributes): object
            {
                $this->created[] = $attributes;

                return (object) ['code' => 77];
            }

            public function refillSeamlessDirect(array $data): bool
            {
                $this->wallet[] = $data;

                return true;
            }

            public function refillSeamless(array $data): bool
            {
                $this->cashback[] = $data;

                return true;
            }
        };

        $this->app->instance('Gametech\Member\Repositories\MemberCashbackRepository', $repository);

        $job = new CashbackCalculate(
            '2026-04-18 00:00:00',
            '2026-04-24 23:59:59',
            '2026-04-18',
            (object) [
                'upline_code' => 5001,
                'member_code' => 1001,
                'balance' => 900.0,
                'deposit_amount' => 300.0,
                'withdraw_amount' => 50.0,
                'bonus_amount' => 0.0,
            ],
            (object) [
                'bonus_percent' => 10,
                'bonus_min' => 0,
                'bonus_max' => 0,
            ],
            'wallet'
        );

        // Without no_balance: netAmount = 300 - 50 - 900 = -650 → skipped
        // With no_balance:    netAmount = 300 - 50 - 0 = 250 → cashback = 25
        $job->handle();

        $this->assertCount(1, $repository->created);
        $this->assertSame('2026-04-18', $repository->created[0]['date_cashback']);
        $this->assertSame(300.0, (float) $repository->created[0]['sum_deposit']);
        $this->assertSame(50.0, (float) $repository->created[0]['sum_withdraw']);
        $this->assertSame(900.0, (float) $repository->created[0]['sum_balance']);
        $this->assertSame(250.0, (float) $repository->created[0]['balance']);

        // balance_total = 300 - 50 - 0 = 250, cashback = 250 * 10% = 25
        $this->assertSame(25.0, (float) $repository->created[0]['cashback']);
        $this->assertSame('N', $repository->created[0]['topupic']);
    }

    public function test_job_records_skip_audit_when_net_amount_is_not_positive(): void
    {
        $repository = new class
        {
            public array $created = [];
            public array $wallet = [];
            public array $cashback = [];

            public function create(array $attributes): object
            {
                $this->created[] = $attributes;

                return (object) ['code' => 99];
            }

            public function refillSeamlessDirect(array $data): bool
            {
                $this->wallet[] = $data;

                return true;
            }

            public function refillSeamless(array $data): bool
            {
                $this->cashback[] = $data;

                return true;
            }
        };

        $this->app->instance('Gametech\Member\Repositories\MemberCashbackRepository', $repository);

        $job = new CashbackCalculate(
            '2026-04-25 00:00:00',
            '2026-04-25 23:59:59',
            '2026-04-25',
            (object) [
                'upline_code' => 5001,
                'member_code' => 1001,
                'balance' => 900.0,
                'deposit_amount' => 861.0,
                'withdraw_amount' => 0.0,
                'bonus_amount' => 0.0,
            ],
            (object) [
                'bonus_percent' => 10,
                'bonus_min' => 0,
                'bonus_max' => 0,
            ],
            'cashback'
        );

        $job->handle();

        $this->assertCount(1, $repository->created);
        $this->assertSame('X', $repository->created[0]['topupic']);
        $this->assertCount(0, $repository->wallet);
        $this->assertCount(0, $repository->cashback);
        $this->assertDatabaseHas('members_credit_log', [
            'member_code' => 1001,
            'refer_table' => 'members_cashback',
            'kind' => 'CASHBACK',
            'auto' => 'Y',
        ]);
    }
}
