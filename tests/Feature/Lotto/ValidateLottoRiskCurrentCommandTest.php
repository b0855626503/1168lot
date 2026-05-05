<?php

namespace Tests\Feature\Lotto;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ValidateLottoRiskCurrentCommandTest extends TestCase
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

    // -----------------------------------------------------------------------
    // Happy path
    // -----------------------------------------------------------------------

    public function test_passes_when_current_has_active_draw_with_real_exposure(): void
    {
        $this->insertDraw(1, 'open', null);
        $this->insertCurrent(1, 'web1', 1, 1, 'straight', '7', 100, 1000, 50);

        $exit = Artisan::call('dashboard:lotto-risk-current-validate');

        $this->assertSame(0, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('duplicate_current_keys = 0', $output);
        $this->assertStringContainsString('invalid_draw_rows = 0', $output);
        $this->assertStringContainsString('zero_risk_rows = 0', $output);
        $this->assertStringContainsString('snapshot_compare_checked = skipped', $output);
        $this->assertStringContainsString('validation_result = passed', $output);
    }

    // -----------------------------------------------------------------------
    // Duplicate key failures
    // -----------------------------------------------------------------------

    public function test_fails_when_duplicate_dimension_key_exists(): void
    {
        $this->insertDraw(1, 'open', null);
        // Drop unique constraint so we can insert duplicates
        Schema::table('lotto_dashboard_risk_current', function (Blueprint $table): void {
            $table->dropUnique(['web_code', 'market_id', 'round_id', 'bet_type', 'number']);
        });
        $this->insertCurrent(1, 'web1', 1, 1, 'straight', '5', 100, 1000, 50);
        $this->insertCurrent(2, 'web1', 1, 1, 'straight', '5', 100, 1000, 50);

        $exit = Artisan::call('dashboard:lotto-risk-current-validate');

        $this->assertSame(1, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('duplicate_current_keys = 1', $output);
        $this->assertStringContainsString('validation_result = failed', $output);
    }

    // -----------------------------------------------------------------------
    // Invalid draw rows
    // -----------------------------------------------------------------------

    public function test_fails_when_draw_relation_is_missing(): void
    {
        // No draw inserted — current row has no matching draw
        $this->insertCurrent(1, 'web1', 1, 99, 'straight', '3', 50, 500, 25);

        $exit = Artisan::call('dashboard:lotto-risk-current-validate');

        $this->assertSame(1, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('invalid_draw_rows = 1', $output);
        $this->assertStringContainsString('validation_result = failed', $output);
    }

    public function test_fails_when_draw_status_is_resulted(): void
    {
        $this->insertDraw(2, 'resulted', null);
        $this->insertCurrent(1, 'web1', 1, 2, 'straight', '8', 75, 750, 30);

        $exit = Artisan::call('dashboard:lotto-risk-current-validate');

        $this->assertSame(1, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('invalid_draw_rows = 1', $output);
        $this->assertStringContainsString('validation_result = failed', $output);
    }

    public function test_fails_when_draw_result_at_is_not_null(): void
    {
        $this->insertDraw(3, 'open', '2026-01-01 12:00:00');
        $this->insertCurrent(1, 'web1', 1, 3, 'straight', '2', 60, 600, 20);

        $exit = Artisan::call('dashboard:lotto-risk-current-validate');

        $this->assertSame(1, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('invalid_draw_rows = 1', $output);
        $this->assertStringContainsString('validation_result = failed', $output);
    }

    // -----------------------------------------------------------------------
    // Zero-risk rows
    // -----------------------------------------------------------------------

    public function test_fails_when_row_has_zero_risk(): void
    {
        $this->insertDraw(4, 'open', null);
        $this->insertCurrent(1, 'web1', 1, 4, 'straight', '1', 0, 0, 0);

        $exit = Artisan::call('dashboard:lotto-risk-current-validate');

        $this->assertSame(1, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('zero_risk_rows = 1', $output);
        $this->assertStringContainsString('validation_result = failed', $output);
    }

    // -----------------------------------------------------------------------
    // --compare-snapshot without scope fails fast
    // -----------------------------------------------------------------------

    public function test_fails_when_compare_snapshot_used_without_scope(): void
    {
        $this->insertDraw(5, 'open', null);
        $this->insertCurrent(1, 'web1', 1, 5, 'straight', '6', 100, 1000, 50);

        $exit = Artisan::call('dashboard:lotto-risk-current-validate', [
            '--compare-snapshot' => true,
        ]);

        $this->assertSame(1, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('--compare-snapshot requires at least one scope filter', $output);
    }

    // -----------------------------------------------------------------------
    // --compare-snapshot with scope passes when data matches
    // -----------------------------------------------------------------------

    public function test_snapshot_compare_passes_when_scoped_and_data_matches(): void
    {
        $this->insertDraw(6, 'open', null);
        $this->insertSnapshot(1, 'web1', 1, 6, 'straight', '4', '2026-01-01 10:00:00', 100, 1000, 50);
        $this->insertCurrent(1, 'web1', 1, 6, 'straight', '4', 100, 1000, 50);

        $exit = Artisan::call('dashboard:lotto-risk-current-validate', [
            '--compare-snapshot' => true,
            '--web-code' => 'web1',
        ]);

        $this->assertSame(0, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('validation_result = passed', $output);
        $this->assertMatchesRegularExpression('/snapshot_compare_checked = \d+/', $output);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

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
            $table->string('bet_type')->default('straight');
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
            $table->string('bet_type')->default('straight');
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
        string $betType,
        string $number,
        string $snapshotAt,
        float $stakeTotal,
        float $payoutIfHit,
        float $liability
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

    private function insertCurrent(
        int $id,
        string $webCode,
        int $marketId,
        int $roundId,
        string $betType,
        string $number,
        float $stakeTotal,
        float $payoutIfHit,
        float $liability
    ): void {
        DB::table('lotto_dashboard_risk_current')->insert([
            'id' => $id,
            'web_code' => $webCode,
            'market_id' => $marketId,
            'round_id' => $roundId,
            'bet_type' => $betType,
            'number' => $number,
            'stake_total' => $stakeTotal,
            'payout_if_hit' => $payoutIfHit,
            'liability' => $liability,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
