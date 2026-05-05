<?php

namespace Tests\Unit\Core;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ArchiveLottoRiskSnapshotsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->recreateTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_dashboard_risk_snapshot_archive');
        Schema::dropIfExists('lotto_dashboard_risk_snapshot');

        parent::tearDown();
    }

    public function test_dry_run_previews_rows_without_writing_archive(): void
    {
        $this->seedSnapshotRows();

        $this->artisan('dashboard:lotto-risk-archive --dry-run --days=7 --chunk=100')
            ->expectsOutputToContain('message=lotto_risk_snapshot_archive_started')
            ->expectsOutputToContain('first_batch_would_archive=1')
            ->expectsOutputToContain('message=lotto_risk_snapshot_archive_finished')
            ->expectsOutputToContain('stopped_by=dry_run')
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('lotto_dashboard_risk_snapshot_archive')->count());
        $this->assertSame(2, DB::table('lotto_dashboard_risk_snapshot')->count());
    }

    public function test_archive_without_delete_source_copies_old_rows_only(): void
    {
        $this->seedSnapshotRows();

        $this->artisan('dashboard:lotto-risk-archive --days=7 --chunk=100')
            ->expectsOutputToContain('message=lotto_risk_snapshot_archive_finished')
            ->assertExitCode(0);

        $this->assertSame(1, DB::table('lotto_dashboard_risk_snapshot_archive')->count());
        $this->assertSame(2, DB::table('lotto_dashboard_risk_snapshot')->count());
    }

    public function test_archive_with_delete_source_moves_rows_to_cold_storage(): void
    {
        $this->seedSnapshotRows();

        $this->artisan('dashboard:lotto-risk-archive --days=7 --chunk=100 --delete-source')
            ->expectsOutputToContain('deleted_source_rows=1')
            ->assertExitCode(0);

        $this->assertSame(1, DB::table('lotto_dashboard_risk_snapshot_archive')->count());
        $this->assertSame(1, DB::table('lotto_dashboard_risk_snapshot')->count());
    }

    public function test_invalid_days_option_is_rejected(): void
    {
        $this->artisan('dashboard:lotto-risk-archive --dry-run --days=0')
            ->expectsOutputToContain('--days must be an integer between 1 and 180')
            ->assertExitCode(1);
    }

    private function recreateTables(): void
    {
        Schema::dropIfExists('lotto_dashboard_risk_snapshot_archive');
        Schema::dropIfExists('lotto_dashboard_risk_snapshot');

        Schema::create('lotto_dashboard_risk_snapshot', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('web_code', 64);
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('round_id');
            $table->string('bet_type', 64);
            $table->string('number', 32);
            $table->timestamp('snapshot_at');
            $table->decimal('stake_total', 18, 2)->default(0);
            $table->decimal('payout_if_hit', 18, 2)->default(0);
            $table->decimal('liability', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('lotto_dashboard_risk_snapshot_archive', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('web_code', 64);
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('round_id');
            $table->string('bet_type', 64);
            $table->string('number', 32);
            $table->timestamp('snapshot_at');
            $table->decimal('stake_total', 18, 2)->default(0);
            $table->decimal('payout_if_hit', 18, 2)->default(0);
            $table->decimal('liability', 18, 2)->default(0);
            $table->timestamp('archived_at');
            $table->timestamps();
        });
    }

    private function seedSnapshotRows(): void
    {
        DB::table('lotto_dashboard_risk_snapshot')->insert([
            [
                'id' => 1,
                'web_code' => 'web1',
                'market_id' => 10,
                'round_id' => 100,
                'bet_type' => 'straight',
                'number' => '12',
                'snapshot_at' => now()->subDays(10)->toDateTimeString(),
                'stake_total' => 100,
                'payout_if_hit' => 1000,
                'liability' => 200,
                'created_at' => now()->subDays(10)->toDateTimeString(),
                'updated_at' => now()->subDays(10)->toDateTimeString(),
            ],
            [
                'id' => 2,
                'web_code' => 'web1',
                'market_id' => 10,
                'round_id' => 101,
                'bet_type' => 'straight',
                'number' => '13',
                'snapshot_at' => now()->subDays(1)->toDateTimeString(),
                'stake_total' => 50,
                'payout_if_hit' => 500,
                'liability' => 100,
                'created_at' => now()->subDays(1)->toDateTimeString(),
                'updated_at' => now()->subDays(1)->toDateTimeString(),
            ],
        ]);
    }
}
