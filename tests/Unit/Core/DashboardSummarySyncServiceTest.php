<?php

namespace Tests\Unit\Core;

use App\Services\Dashboard\DashboardBucketResolver;
use App\Services\Dashboard\DashboardSummaryBroadcastNotifier;
use App\Services\Dashboard\DashboardSummaryProjector;
use App\Services\Dashboard\DashboardSummarySyncService;
use App\Services\Dashboard\DashboardWebCodeResolver;
use App\Services\Dashboard\LottoRiskSnapshotWritePolicy;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class DashboardSummarySyncServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        Cache::flush();
    }

    protected function tearDown(): void
    {
        $this->dropTestTables();
        Mockery::close();
        parent::tearDown();
    }

    public function test_pending_bucket_payload_merges_sections_and_uses_latest_source_context(): void
    {
        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadZeroRisk()),
            $this->mockNotifier(),
        );

        $service->mergePendingBucketPayload(
            summaryDate: '2026-04-04',
            webCode: 'main',
            updatedSections: ['lotto_cash'],
            sourceType: 'lotto',
            sourceId: '100'
        );

        $service->mergePendingBucketPayload(
            summaryDate: '2026-04-04',
            webCode: 'main',
            updatedSections: ['net', 'lotto_product'],
            sourceType: 'wallet',
            sourceId: '200'
        );

        $payload = $service->consumePendingBucketPayload(
            summaryDate: '2026-04-04',
            webCode: 'main',
            fallbackUpdatedSections: ['lotto_cash'],
            fallbackSourceType: 'fallback',
            fallbackSourceId: 'fallback-id'
        );

        $this->assertSame('2026-04-04', $payload['summary_date']);
        $this->assertSame('main', $payload['web_code']);
        $this->assertSame(['lotto_cash', 'lotto_product', 'net'], $payload['updated_sections']);
        $this->assertSame('wallet', $payload['source_type']);
        $this->assertSame('200', $payload['source_id']);
    }

    public function test_sync_bucket_zero_risk_scheduled_skips_snapshot_and_keeps_current(): void
    {
        $this->createTestTables();
        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadZeroRisk()),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'scheduled');

        $this->assertSame(1, DB::table('lotto_dashboard_risk_current')->count());
        $this->assertSame(0, DB::table('lotto_dashboard_risk_snapshot')->count());
    }

    public function test_sync_bucket_meaningful_risk_scheduled_writes_snapshot_and_current(): void
    {
        $this->createTestTables();
        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadMeaningfulRisk()),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'scheduled');

        $this->assertSame(1, DB::table('lotto_dashboard_risk_current')->count());
        $this->assertSame(1, DB::table('lotto_dashboard_risk_snapshot')->count());
    }

    public function test_sync_bucket_draw_closed_zero_risk_allows_snapshot_write(): void
    {
        $this->createTestTables();
        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadZeroRisk()),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'draw_closed');

        $this->assertSame(1, DB::table('lotto_dashboard_risk_current')->count());
        $this->assertSame(1, DB::table('lotto_dashboard_risk_snapshot')->count());
    }

    public function test_sync_bucket_manual_audit_without_reason_blocks_snapshot_write(): void
    {
        $this->createTestTables();
        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadZeroRisk()),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'manual_audit');

        $this->assertSame(1, DB::table('lotto_dashboard_risk_current')->count());
        $this->assertSame(0, DB::table('lotto_dashboard_risk_snapshot')->count());
    }

    private function makeService(
        DashboardSummaryProjector $projector,
        DashboardSummaryBroadcastNotifier $notifier,
    ): DashboardSummarySyncService {
        return new DashboardSummarySyncService(
            new DashboardBucketResolver(new DashboardWebCodeResolver),
            new DashboardWebCodeResolver,
            $projector,
            $notifier,
            new LottoRiskSnapshotWritePolicy,
        );
    }

    private function mockProjectorWithPayload(array $dailyPayload, array $lottoPayload): DashboardSummaryProjector
    {
        $projector = Mockery::mock(DashboardSummaryProjector::class);
        $projector->shouldReceive('projectDaily')->andReturn($dailyPayload);
        $projector->shouldReceive('projectLotto')->andReturn($lottoPayload);

        return $projector;
    }

    private function mockNotifier(): DashboardSummaryBroadcastNotifier
    {
        $notifier = Mockery::mock(DashboardSummaryBroadcastNotifier::class);
        $notifier->shouldReceive('notify')->andReturnNull();

        return $notifier;
    }

    /**
     * @return array<string, mixed>
     */
    private function dailyPayload(): array
    {
        return [
            'summary_date' => '2026-05-06',
            'web_code' => 'main',
            'last_synced_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lottoPayloadZeroRisk(): array
    {
        return [
            'daily' => [
                'summary_date' => '2026-05-06',
                'web_code' => 'main',
                'last_synced_at' => now()->toDateTimeString(),
            ],
            'risk' => [
                [
                    'web_code' => 'main',
                    'market_id' => 1,
                    'round_id' => 10,
                    'bet_type' => '2top',
                    'number' => '12',
                    'stake_total' => 0,
                    'payout_if_hit' => 0,
                    'liability' => 0,
                    'snapshot_at' => now()->toDateTimeString(),
                ],
            ],
            'markets' => [],
            'risk_aggregate' => [],
            'insights' => ['daily' => [], 'numbers' => []],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lottoPayloadMeaningfulRisk(): array
    {
        $payload = $this->lottoPayloadZeroRisk();
        $payload['risk'][0]['stake_total'] = 100;

        return $payload;
    }

    private function createTestTables(): void
    {
        $this->dropTestTables();

        Schema::create('dashboard_summary_daily', function (Blueprint $table): void {
            $table->string('summary_date');
            $table->string('web_code');
            $table->string('last_synced_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['summary_date', 'web_code']);
        });

        Schema::create('lotto_dashboard_summary_daily', function (Blueprint $table): void {
            $table->string('summary_date');
            $table->string('web_code');
            $table->string('last_synced_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['summary_date', 'web_code']);
        });

        Schema::create('lotto_dashboard_risk_current', function (Blueprint $table): void {
            $table->string('web_code');
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('round_id');
            $table->string('bet_type');
            $table->string('number');
            $table->decimal('stake_total', 18, 4)->default(0);
            $table->decimal('payout_if_hit', 18, 4)->default(0);
            $table->decimal('liability', 18, 4)->default(0);
            $table->unique(['web_code', 'market_id', 'round_id', 'bet_type', 'number'], 'risk_current_unique');
        });

        Schema::create('lotto_dashboard_risk_snapshot', function (Blueprint $table): void {
            $table->string('web_code');
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('round_id');
            $table->string('bet_type');
            $table->string('number');
            $table->decimal('stake_total', 18, 4)->default(0);
            $table->decimal('payout_if_hit', 18, 4)->default(0);
            $table->decimal('liability', 18, 4)->default(0);
            $table->string('snapshot_at');
            $table->unique(['web_code', 'market_id', 'round_id', 'bet_type', 'number', 'snapshot_at'], 'risk_snapshot_unique');
        });
    }

    private function dropTestTables(): void
    {
        foreach ([
            'lotto_dashboard_risk_snapshot',
            'lotto_dashboard_risk_current',
            'lotto_dashboard_summary_daily',
            'dashboard_summary_daily',
        ] as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
            }
        }
    }
}
