<?php

namespace Tests\Feature\Lotto;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackfillLottoRiskCurrentCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_dashboard_risk_current');
        Schema::dropIfExists('lotto_dashboard_risk_snapshot');
        Schema::dropIfExists('lotto_draws');

        parent::tearDown();
    }

    public function test_happy_path_writes_active_draw_with_risk_to_current(): void
    {
        $this->insertDraw(1, 'open', null);
        $this->insertSnapshot(1, 'web1', 1, 1, '3', '2026-01-01 10:00:00', 100, 1000, 50);

        $exit = Artisan::call('dashboard:lotto-risk-current-backfill');

        $this->assertSame(0, $exit);
        $row = DB::table('lotto_dashboard_risk_current')
            ->where('web_code', 'web1')
            ->where('round_id', 1)
            ->where('number', '3')
            ->first();
        $this->assertNotNull($row);
        $this->assertSame(100.0, (float) $row->stake_total);
    }

    public function test_resulted_draw_snapshot_is_not_written(): void
    {
        $this->insertDraw(2, 'resulted', '2026-01-01 12:00:00');
        $this->insertSnapshot(2, 'web1', 1, 2, '5', '2026-01-01 10:00:00', 200, 2000, 100);

        $exit = Artisan::call('dashboard:lotto-risk-current-backfill');

        $this->assertSame(0, $exit);
        $row = DB::table('lotto_dashboard_risk_current')
            ->where('round_id', 2)
            ->first();
        $this->assertNull($row);
    }

    public function test_closed_draw_with_risk_is_written_as_current_exposure(): void
    {
        $this->insertDraw(10, 'closed', null);
        $this->insertSnapshot(10, 'web1', 1, 10, '9', '2026-01-01 10:00:00', 500, 5000, 250);

        $exit = Artisan::call('dashboard:lotto-risk-current-backfill');

        $this->assertSame(0, $exit);
        $row = DB::table('lotto_dashboard_risk_current')
            ->where('round_id', 10)
            ->first();
        $this->assertNotNull($row, 'closed draw (awaiting result) must remain in current exposure');
        $this->assertSame(500.0, (float) $row->stake_total);
    }

    public function test_zero_risk_snapshot_for_active_draw_is_not_written(): void
    {
        $this->insertDraw(3, 'open', null);
        $this->insertSnapshot(3, 'web1', 1, 3, '7', '2026-01-01 10:00:00', 0, 0, 0);

        $exit = Artisan::call('dashboard:lotto-risk-current-backfill');

        $this->assertSame(0, $exit);
        $row = DB::table('lotto_dashboard_risk_current')
            ->where('round_id', 3)
            ->first();
        $this->assertNull($row);
    }

    public function test_until_cap_excludes_snapshots_after_cutoff(): void
    {
        $this->insertDraw(4, 'open', null);
        $this->insertSnapshot(4, 'web1', 1, 4, '1', '2026-06-01 10:00:00', 300, 3000, 150);

        $exit = Artisan::call('dashboard:lotto-risk-current-backfill', ['--until' => '2026-01-01 00:00:00']);

        $this->assertSame(0, $exit);
        $row = DB::table('lotto_dashboard_risk_current')
            ->where('round_id', 4)
            ->first();
        $this->assertNull($row);
    }

    public function test_limit_keys_caps_number_of_distinct_keys_written(): void
    {
        $this->insertDraw(5, 'open', null);
        $this->insertSnapshot(5, 'web1', 1, 5, '1', '2026-01-01 10:00:00', 100, 1000, 50);
        $this->insertSnapshot(6, 'web1', 1, 5, '2', '2026-01-01 10:00:00', 100, 1000, 50);
        $this->insertSnapshot(7, 'web1', 1, 5, '3', '2026-01-01 10:00:00', 100, 1000, 50);

        $exit = Artisan::call('dashboard:lotto-risk-current-backfill', ['--limit-keys' => 1]);

        $this->assertSame(0, $exit);
        $count = DB::table('lotto_dashboard_risk_current')
            ->where('round_id', 5)
            ->count();
        $this->assertSame(1, $count);
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('lotto_dashboard_risk_current');
        Schema::dropIfExists('lotto_dashboard_risk_snapshot');
        Schema::dropIfExists('lotto_draws');

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('status')->default('open');
            $table->timestamp('result_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_dashboard_risk_snapshot', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('web_code');
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('round_id');
            $table->string('bet_type');
            $table->string('number');
            $table->timestamp('snapshot_at');
            $table->decimal('stake_total', 14, 2)->default(0);
            $table->decimal('payout_if_hit', 14, 2)->default(0);
            $table->decimal('liability', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('lotto_dashboard_risk_current', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('web_code');
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('round_id');
            $table->string('bet_type');
            $table->string('number');
            $table->timestamp('snapshot_at')->nullable();
            $table->decimal('stake_total', 14, 2)->default(0);
            $table->decimal('payout_if_hit', 14, 2)->default(0);
            $table->decimal('liability', 14, 2)->default(0);
            $table->timestamps();
            $table->unique(['web_code', 'market_id', 'round_id', 'bet_type', 'number']);
        });
    }

    private function insertDraw(int $id, string $status, ?string $resultAt): void
    {
        DB::table('lotto_draws')->insert([
            'id' => $id,
            'status' => $status,
            'result_at' => $resultAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertSnapshot(
        int $id,
        string $webCode,
        int $marketId,
        int $roundId,
        string $number,
        string $snapshotAt,
        float $stakeTotal,
        float $payoutIfHit,
        float $liability,
        string $betType = 'straight'
    ): void {
        DB::table('lotto_dashboard_risk_snapshot')->insert([
            'id' => $id,
            'web_code' => $webCode,
            'market_id' => $marketId,
            'round_id' => $roundId,
            'bet_type' => $betType,
            'number' => $number,
            'snapshot_at' => $snapshotAt,
            'stake_total' => $stakeTotal,
            'payout_if_hit' => $payoutIfHit,
            'liability' => $liability,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
