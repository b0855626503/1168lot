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
        Schema::create('members_cashback', function (Blueprint $table): void {
            $table->bigIncrements('code');
            $table->date('date_cashback')->nullable();
            $table->unsignedBigInteger('downline_code')->nullable();
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
}
