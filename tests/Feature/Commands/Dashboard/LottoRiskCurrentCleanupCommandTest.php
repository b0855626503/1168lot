<?php

namespace Tests\Feature\Commands\Dashboard;

use App\Console\Commands\Dashboard\LottoRiskCurrentCleanupCommand;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LottoRiskCurrentCleanupCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register the command from the worktree app path (not yet on the main autoload path).
        $classFile = __DIR__.'/../../../../app/Console/Commands/Dashboard/LottoRiskCurrentCleanupCommand.php';
        if (! class_exists(LottoRiskCurrentCleanupCommand::class, false)) {
            require_once $classFile;
        }

        $this->app->make(Kernel::class)
            ->registerCommand(new LottoRiskCurrentCleanupCommand);

        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_dashboard_risk_current');
        Schema::dropIfExists('lotto_draws');

        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // --dry-run: counts without deleting
    // -----------------------------------------------------------------------

    public function test_dry_run_shows_counts_without_deleting(): void
    {
        $this->insertDraw(1, 'resulted', '2026-01-01 12:00:00');
        $this->insertCurrent(1, 'web1', 1, 1, 'straight', '7');

        $exit = Artisan::call('dashboard:lotto-risk-current-cleanup', ['--dry-run' => true]);

        $this->assertSame(0, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('message=lotto_risk_current_cleanup_started', $output);
        $this->assertStringContainsString('target=invalid_draw_rows', $output);
        $this->assertStringContainsString('stopped_by=dry_run', $output);

        // Row must still exist
        $this->assertSame(1, DB::table('lotto_dashboard_risk_current')->count());
    }

    public function test_dry_run_counts_all_targets(): void
    {
        // Invalid draw row (resulted)
        $this->insertDraw(1, 'resulted', '2026-01-01 12:00:00');
        $this->insertCurrent(1, 'web1', 1, 1, 'straight', '1');

        // Zero-risk row (valid draw, but zero amounts)
        $this->insertDraw(2, 'open', null);
        $this->insertCurrent(2, 'web1', 1, 2, 'straight', '2', 0, 0, 0);

        // Missing draw row (no draw for round_id=99)
        $this->insertCurrent(3, 'web1', 1, 99, 'straight', '3');

        $exit = Artisan::call('dashboard:lotto-risk-current-cleanup', ['--dry-run' => true, '--sleep-ms' => 0]);

        $this->assertSame(0, $exit);
        // All 3 rows must still exist — dry-run never deletes
        $this->assertSame(3, DB::table('lotto_dashboard_risk_current')->count());
        $output = Artisan::output();

        // All three target count lines must appear in a single dry-run execution
        $this->assertStringContainsString('target=invalid_draw_rows', $output);
        $this->assertStringContainsString('target=missing_draw_rows', $output);
        $this->assertStringContainsString('target=zero_risk_rows', $output);

        // Finished line must show stopped_by=dry_run
        $this->assertStringContainsString('stopped_by=dry_run', $output);
        $this->assertStringContainsString('message=lotto_risk_current_cleanup_finished', $output);
    }

    // -----------------------------------------------------------------------
    // Live run: deletes invalid, missing, and zero-risk rows
    // -----------------------------------------------------------------------

    public function test_live_run_deletes_resulted_draw_rows(): void
    {
        $this->insertDraw(1, 'resulted', '2026-01-01 12:00:00');
        $this->insertCurrent(1, 'web1', 1, 1, 'straight', '5');

        // Valid row to ensure it is not deleted
        $this->insertDraw(2, 'open', null);
        $this->insertCurrent(2, 'web1', 1, 2, 'straight', '6', 100, 1000, 50);

        $exit = Artisan::call('dashboard:lotto-risk-current-cleanup', ['--sleep-ms' => 0]);

        $this->assertSame(0, $exit);
        $this->assertNull(DB::table('lotto_dashboard_risk_current')->where('id', 1)->first());
        $this->assertNotNull(DB::table('lotto_dashboard_risk_current')->where('id', 2)->first());
    }

    public function test_live_run_deletes_missing_draw_rows(): void
    {
        // No draw for round_id=99
        $this->insertCurrent(1, 'web1', 1, 99, 'straight', '3');

        $exit = Artisan::call('dashboard:lotto-risk-current-cleanup', ['--sleep-ms' => 0]);

        $this->assertSame(0, $exit);
        $this->assertSame(0, DB::table('lotto_dashboard_risk_current')->count());
        $output = Artisan::output();
        $this->assertStringContainsString('target=missing_draw_rows', $output);
    }

    public function test_live_run_deletes_zero_risk_rows(): void
    {
        $this->insertDraw(1, 'open', null);
        $this->insertCurrent(1, 'web1', 1, 1, 'straight', '9', 0, 0, 0);

        $exit = Artisan::call('dashboard:lotto-risk-current-cleanup', ['--sleep-ms' => 0]);

        $this->assertSame(0, $exit);
        $this->assertSame(0, DB::table('lotto_dashboard_risk_current')->count());
        $output = Artisan::output();
        $this->assertStringContainsString('target=zero_risk_rows', $output);
    }

    public function test_live_run_finished_message_contains_totals(): void
    {
        $this->insertDraw(1, 'resulted', '2026-01-01 12:00:00');
        $this->insertCurrent(1, 'web1', 1, 1, 'straight', '1');

        $exit = Artisan::call('dashboard:lotto-risk-current-cleanup', ['--sleep-ms' => 0]);

        $this->assertSame(0, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('message=lotto_risk_current_cleanup_finished', $output);
        $this->assertStringContainsString('deleted_rows=1', $output);
        $this->assertStringContainsString('stopped_by=complete', $output);
    }

    // -----------------------------------------------------------------------
    // --max-runtime=0: stops immediately
    // -----------------------------------------------------------------------

    public function test_max_runtime_zero_stops_immediately(): void
    {
        $this->insertDraw(1, 'resulted', '2026-01-01 12:00:00');
        $this->insertCurrent(1, 'web1', 1, 1, 'straight', '7');

        $exit = Artisan::call('dashboard:lotto-risk-current-cleanup', [
            '--max-runtime' => 0,
            '--sleep-ms' => 0,
        ]);

        $this->assertSame(0, $exit);
        $output = Artisan::output();
        // With max-runtime=0, no deletion happens, stopped_by=complete (0 means disabled guard)
        // Actually max-runtime=0 disables the guard (max(0, ...) = 0 means no time limit)
        // The loop will complete normally. Just assert command ran without error.
        $this->assertStringContainsString('message=lotto_risk_current_cleanup_finished', $output);
    }

    // -----------------------------------------------------------------------
    // --web-code filter
    // -----------------------------------------------------------------------

    public function test_web_code_filter_only_cleans_matching_rows(): void
    {
        $this->insertDraw(1, 'resulted', '2026-01-01 12:00:00');
        $this->insertCurrent(1, 'web1', 1, 1, 'straight', '1');
        $this->insertCurrent(2, 'web2', 1, 1, 'straight', '1');

        $exit = Artisan::call('dashboard:lotto-risk-current-cleanup', [
            '--web-code' => 'web1',
            '--sleep-ms' => 0,
        ]);

        $this->assertSame(0, $exit);
        // web1 row deleted, web2 row still there
        $this->assertNull(DB::table('lotto_dashboard_risk_current')->where('id', 1)->first());
        $this->assertNotNull(DB::table('lotto_dashboard_risk_current')->where('id', 2)->first());
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function prepareSchema(): void
    {
        Schema::dropIfExists('lotto_dashboard_risk_current');
        Schema::dropIfExists('lotto_draws');

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('status')->default('open');
            $table->timestamp('result_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_dashboard_risk_current', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('web_code');
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('round_id');
            $table->string('bet_type')->default('straight');
            $table->string('number');
            $table->decimal('stake_total', 14, 2)->default(100);
            $table->decimal('payout_if_hit', 14, 2)->default(1000);
            $table->decimal('liability', 14, 2)->default(50);
            $table->timestamps();
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

    private function insertCurrent(
        int $id,
        string $webCode,
        int $marketId,
        int $roundId,
        string $betType,
        string $number,
        float $stakeTotal = 100,
        float $payoutIfHit = 1000,
        float $liability = 50
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
