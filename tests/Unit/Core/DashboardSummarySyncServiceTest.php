<?php

namespace Tests\Unit\Core;

use App\Services\Dashboard\DashboardBucketResolver;
use App\Services\Dashboard\DashboardSummaryBroadcastNotifier;
use App\Services\Dashboard\DashboardSummaryProjector;
use App\Services\Dashboard\DashboardSummarySyncService;
use App\Services\Dashboard\DashboardWebCodeResolver;
use App\Services\Dashboard\LottoRiskSnapshotWritePolicy;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $this->assertSame([], $payload['audit_context']);
    }

    public function test_consume_pending_bucket_payload_without_legacy_audit_context_falls_back_to_empty_array(): void
    {
        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadZeroRisk()),
            $this->mockNotifier(),
        );

        Cache::put('dashboard:summary:pending:main:2026-04-05', [
            'summary_date' => '2026-04-05',
            'web_code' => 'main',
            'updated_sections' => ['lotto_risk'],
            'source_type' => 'lotto',
            'source_id' => 'legacy-1',
            'revision' => 'legacy',
        ], now()->addMinutes(10));

        $payload = $service->consumePendingBucketPayload(
            summaryDate: '2026-04-05',
            webCode: 'main',
            fallbackUpdatedSections: [],
            fallbackSourceType: null,
            fallbackSourceId: null,
            fallbackAuditContext: []
        );

        $this->assertSame('lotto', $payload['source_type']);
        $this->assertSame('legacy-1', $payload['source_id']);
        $this->assertSame(['lotto_risk'], $payload['updated_sections']);
        $this->assertSame([], $payload['audit_context']);
    }

    public function test_sync_bucket_zero_risk_scheduled_skips_snapshot_and_does_not_write_current(): void
    {
        // BOA-230: zero-risk rows must not be upserted into current.
        $this->createTestTables();
        $this->seedOpenDraw(10);
        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadZeroRisk()),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'scheduled');

        $this->assertSame(0, DB::table('lotto_dashboard_risk_current')->count());
        $this->assertSame(0, DB::table('lotto_dashboard_risk_snapshot')->count());
    }

    public function test_sync_bucket_meaningful_risk_scheduled_writes_current_only_no_snapshot(): void
    {
        // BOA-229: syncBucket must never write lotto_dashboard_risk_snapshot.
        $this->createTestTables();
        $this->seedOpenDraw(10);
        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadMeaningfulRisk()),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'scheduled');

        $this->assertSame(1, DB::table('lotto_dashboard_risk_current')->count());
        $this->assertSame(0, DB::table('lotto_dashboard_risk_snapshot')->count());
    }

    public function test_sync_bucket_draw_closed_zero_risk_does_not_write_current_or_snapshot(): void
    {
        // BOA-229: draw_closed source no longer triggers a snapshot write at runtime.
        // BOA-230: zero-risk row is not written to current.
        $this->createTestTables();
        $this->seedOpenDraw(10);
        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadZeroRisk()),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'draw_closed');

        $this->assertSame(0, DB::table('lotto_dashboard_risk_current')->count());
        $this->assertSame(0, DB::table('lotto_dashboard_risk_snapshot')->count());
    }

    public function test_sync_bucket_manual_audit_without_reason_does_not_write_current_or_snapshot(): void
    {
        $this->createTestTables();
        $this->seedOpenDraw(10);
        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadZeroRisk()),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'manual_audit');

        $this->assertSame(0, DB::table('lotto_dashboard_risk_current')->count());
        $this->assertSame(0, DB::table('lotto_dashboard_risk_snapshot')->count());
    }

    public function test_sync_bucket_manual_audit_with_reason_still_does_not_write_snapshot(): void
    {
        // BOA-229: even an operator-initiated manual audit no longer writes
        // lotto_dashboard_risk_snapshot from the runtime sync path.
        // BOA-230: zero-risk row is not written to current either.
        $this->createTestTables();
        $this->seedOpenDraw(10);
        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadZeroRisk()),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'manual_audit', null, [
            'reason' => 'operator verify exposure',
            'actor_id' => 9001,
        ]);

        $this->assertSame(0, DB::table('lotto_dashboard_risk_current')->count());
        $this->assertSame(0, DB::table('lotto_dashboard_risk_snapshot')->count());
    }

    public function test_current_writer_upserts_open_draw_with_meaningful_risk(): void
    {
        $this->createTestTables();
        $this->seedOpenDraw(10);
        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadMeaningfulRisk()),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'scheduled');

        $this->assertSame(1, DB::table('lotto_dashboard_risk_current')->where('round_id', 10)->count());
    }

    public function test_current_writer_upserts_closed_but_not_resulted_draw_with_risk(): void
    {
        $this->createTestTables();
        $this->seedDraw(10, ['status' => 'closed', 'result_at' => null]);
        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadMeaningfulRisk()),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'scheduled');

        $this->assertSame(1, DB::table('lotto_dashboard_risk_current')->where('round_id', 10)->count());
    }

    public function test_current_writer_deletes_existing_row_for_resulted_draw(): void
    {
        $this->createTestTables();
        $this->seedDraw(10, ['status' => 'resulted', 'result_at' => '2026-05-05 12:00:00']);
        DB::table('lotto_dashboard_risk_current')->insert($this->existingCurrentRowFor(10));

        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadMeaningfulRisk()),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'scheduled');

        $this->assertSame(0, DB::table('lotto_dashboard_risk_current')->where('round_id', 10)->count());
    }

    public function test_current_writer_keeps_open_draw_even_when_result_at_is_pre_scheduled(): void
    {
        // Production data can pre-schedule result_at while draw is still open/closed.
        // Current risk must remain active until status becomes resulted.
        $this->createTestTables();
        $this->seedDraw(10, ['status' => 'open', 'result_at' => '2026-05-05 12:00:00']);

        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadMeaningfulRisk()),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'scheduled');

        $this->assertSame(1, DB::table('lotto_dashboard_risk_current')->where('round_id', 10)->count());
    }

    public function test_current_writer_excludes_defensive_extended_statuses(): void
    {
        // Production enum is only draft|open|closed|resulted; this guards the
        // defensive exclude list (cancelled/void/refunded/no_result/...) from
        // future enum additions per BOA-228/230.
        $this->createTestTables();
        $this->seedDraw(10, ['status' => 'draft', 'result_at' => null]);
        // Pre-existing legacy row for a round whose draw is now in a defensive
        // exclude state simulated via direct status override:
        DB::table('lotto_draws')->where('id', 10)->update(['status' => 'cancelled']);
        DB::table('lotto_dashboard_risk_current')->insert($this->existingCurrentRowFor(10));

        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadMeaningfulRisk()),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'scheduled');

        $this->assertSame(0, DB::table('lotto_dashboard_risk_current')->where('round_id', 10)->count());
    }

    public function test_current_writer_deletes_existing_zero_risk_row_by_full_key(): void
    {
        $this->createTestTables();
        $this->seedOpenDraw(10);
        DB::table('lotto_dashboard_risk_current')->insert($this->existingCurrentRowFor(10, [
            'bet_type' => '2top',
            'number' => '12',
            'stake_total' => 50,
            'payout_if_hit' => 50,
            'liability' => 50,
        ]));

        // Payload has zero-risk for the same key; row must be deleted, not upserted.
        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadZeroRisk()),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'scheduled');

        $this->assertSame(0, DB::table('lotto_dashboard_risk_current')
            ->where('round_id', 10)
            ->where('bet_type', '2top')
            ->where('number', '12')
            ->count());
    }

    public function test_current_writer_deletes_existing_rows_for_missing_draw(): void
    {
        $this->createTestTables();
        // Note: NO row inserted into lotto_draws for round_id=10.
        DB::table('lotto_dashboard_risk_current')->insert($this->existingCurrentRowFor(10));

        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadMeaningfulRisk()),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'scheduled');

        $this->assertSame(0, DB::table('lotto_dashboard_risk_current')->where('round_id', 10)->count());
    }

    public function test_current_writer_is_set_based_no_n_plus_one_against_lotto_draws(): void
    {
        // BOA-230: classifying N payload rows must not issue N queries against
        // lotto_draws. We assert at most a small constant number of SELECTs hit
        // lotto_draws regardless of payload row count.
        $this->createTestTables();

        $payload = $this->lottoPayloadMeaningfulRisk();
        $template = $payload['risk'][0];
        $payload['risk'] = [];
        for ($i = 1; $i <= 25; $i++) {
            $drawId = 1000 + $i;
            $this->seedOpenDraw($drawId);
            $row = $template;
            $row['round_id'] = $drawId;
            $row['number'] = sprintf('%02d', $i);
            $payload['risk'][] = $row;
        }

        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $payload),
            $this->mockNotifier(),
        );

        DB::flushQueryLog();
        DB::enableQueryLog();
        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'scheduled');
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        $drawSelects = 0;
        foreach ($log as $entry) {
            $sql = (string) ($entry['query'] ?? '');
            if (stripos($sql, 'from "lotto_draws"') !== false || stripos($sql, 'from `lotto_draws`') !== false) {
                if (stripos($sql, 'select') === 0) {
                    $drawSelects++;
                }
            }
        }

        // One classification SELECT for all 25 round_ids — never per-row.
        $this->assertLessThanOrEqual(2, $drawSelects, 'lotto_draws classification must be set-based, not N+1.');
        $this->assertSame(25, DB::table('lotto_dashboard_risk_current')->count());
    }

    public function test_cancelled_ticket_leaves_zero_risk_row_which_is_cleaned_from_current(): void
    {
        // BOA-230: cancelled ticket leaves sold_amount=0 in exposure; zero-risk delete path removes stale current row.
        $this->createTestTables();
        // Active draw (status='open', result_at=NULL) — the draw itself is NOT cancelled.
        $this->seedOpenDraw(10);

        // Pre-existing current row simulating a prior upsert when the ticket was active and had risk.
        DB::table('lotto_dashboard_risk_current')->insert($this->existingCurrentRowFor(10, [
            'bet_type' => '2top',
            'number' => '12',
            'stake_total' => 200,
            'payout_if_hit' => 18000,
            'liability' => 17800,
        ]));

        // Payload where the exposure pipeline has decremented sold_amount to 0 after ticket cancellation.
        $payload = $this->lottoPayloadZeroRisk();
        // Ensure the zero-risk payload matches the same draw+number+bet_type key.
        $payload['risk'][0] = array_merge($payload['risk'][0], [
            'round_id' => 10,
            'bet_type' => '2top',
            'number' => '12',
            'stake_total' => 0,
            'payout_if_hit' => 0,
            'liability' => 0,
        ]);

        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $payload),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'scheduled');

        // Zero-risk delete path must have removed the stale current row.
        $this->assertSame(0, DB::table('lotto_dashboard_risk_current')
            ->where('round_id', 10)
            ->where('bet_type', '2top')
            ->where('number', '12')
            ->count());
    }

    public function test_current_writer_mixed_payload_partial_upsert_partial_delete(): void
    {
        $this->createTestTables();
        $this->seedOpenDraw(10);                                            // valid -> upsert
        $this->seedDraw(11, ['status' => 'resulted', 'result_at' => '2026-05-05']); // invalid -> delete

        // Pre-existing rows for both
        DB::table('lotto_dashboard_risk_current')->insert($this->existingCurrentRowFor(10));
        DB::table('lotto_dashboard_risk_current')->insert($this->existingCurrentRowFor(11));

        $payload = $this->lottoPayloadMeaningfulRisk();
        $payload['risk'][] = array_merge($payload['risk'][0], ['round_id' => 11, 'number' => '13']);

        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $payload),
            $this->mockNotifier(),
        );

        $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'scheduled');

        $this->assertSame(1, DB::table('lotto_dashboard_risk_current')->where('round_id', 10)->count());
        $this->assertSame(0, DB::table('lotto_dashboard_risk_current')->where('round_id', 11)->count());
    }

    public function test_sync_risk_current_for_draw_upserts_only_target_draw_rows(): void
    {
        $this->createTestTables();
        $this->seedOpenDraw(10);
        $this->seedOpenDraw(11);
        $this->seedExposureRow(10, '2top', '12', 100);
        $this->seedExposureRow(11, '2top', '99', 200);
        $this->seedBetSetting(10, '2top', 90);
        $this->seedBetSetting(11, '2top', 90);

        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadZeroRisk()),
            $this->mockNotifier(),
        );

        $service->syncRiskCurrentForDraw(10, 'main', 'lotto', 'draw-10');

        $this->assertSame(1, DB::table('lotto_dashboard_risk_current')->where('round_id', 10)->count());
        $this->assertSame(0, DB::table('lotto_dashboard_risk_current')->where('round_id', 11)->count());
        $row = DB::table('lotto_dashboard_risk_current')
            ->where('round_id', 10)
            ->where('number', '12')
            ->first();
        $this->assertNotNull($row);
        $this->assertSame(100.0, (float) $row->stake_total);
        $this->assertSame(9000.0, (float) $row->payout_if_hit);
        $this->assertSame(8900.0, (float) $row->liability);
    }

    public function test_sync_risk_current_for_draw_deletes_rows_when_draw_is_resulted(): void
    {
        $this->createTestTables();
        Log::spy();
        $this->seedDraw(77, ['status' => 'resulted', 'result_at' => '2026-05-07 10:00:00']);
        DB::table('lotto_dashboard_risk_current')->insert($this->existingCurrentRowFor(77));

        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadZeroRisk()),
            $this->mockNotifier(),
        );

        $service->syncRiskCurrentForDraw(77, 'main', 'draw_resulted', '77');

        $this->assertSame(0, DB::table('lotto_dashboard_risk_current')->where('round_id', 77)->count());
        Log::shouldHaveReceived('info')
            ->with('lotto_risk_current_draw_sync_completed', Mockery::on(function (array $context): bool {
                return (int) ($context['draw_id'] ?? 0) === 77
                    && (int) ($context['rows_deleted'] ?? -1) === 1
                    && array_key_exists('duration_ms', $context);
            }))
            ->once();
    }

    public function test_sync_risk_current_for_draw_removes_stale_rows_missing_from_latest_payload(): void
    {
        $this->createTestTables();
        $this->seedOpenDraw(10);
        $this->seedBetSetting(10, '2top', 90);
        $this->seedExposureRow(10, '2top', '12', 100);

        DB::table('lotto_dashboard_risk_current')->insert($this->existingCurrentRowFor(10, [
            'number' => '12',
            'stake_total' => 50,
            'payout_if_hit' => 4500,
            'liability' => 4450,
        ]));
        DB::table('lotto_dashboard_risk_current')->insert($this->existingCurrentRowFor(10, [
            'number' => '99',
            'stake_total' => 99,
            'payout_if_hit' => 8910,
            'liability' => 8811,
        ]));

        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadZeroRisk()),
            $this->mockNotifier(),
        );

        $service->syncRiskCurrentForDraw(10, 'main', 'lotto', 'draw-10');

        $this->assertSame(1, DB::table('lotto_dashboard_risk_current')->where('round_id', 10)->count());
        $this->assertSame(1, DB::table('lotto_dashboard_risk_current')->where('round_id', 10)->where('number', '12')->count());
        $this->assertSame(0, DB::table('lotto_dashboard_risk_current')->where('round_id', 10)->where('number', '99')->count());
    }

    public function test_legacy_snapshot_source_is_blocked_when_feature_flag_disabled(): void
    {
        config()->set('dashboard.lotto.legacy_snapshot_write_enabled', false);
        Log::spy();
        $this->createTestTables();
        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadMeaningfulRisk()),
            $this->mockNotifier(),
        );

        $service->writeRiskSnapshot($this->lottoPayloadMeaningfulRisk()['risk'], [
            'source' => 'rebuild_filtered',
            'reason' => 'test',
            'class' => __CLASS__,
            'file' => __FILE__,
        ]);

        $this->assertSame(0, DB::table('lotto_dashboard_risk_snapshot')->count());
        Log::shouldHaveReceived('warning')
            ->with('lotto_snapshot_legacy_write_blocked', Mockery::type('array'))
            ->once();
        // BOA-229: writeRiskSnapshot() is now deprecated and emits a warning
        // log on every invocation (legacy callers only).
        Log::shouldHaveReceived('warning')
            ->with('lotto_risk_snapshot_write_deprecated', Mockery::type('array'))
            ->once();
    }

    public function test_legacy_snapshot_source_is_allowed_when_feature_flag_enabled(): void
    {
        config()->set('dashboard.lotto.legacy_snapshot_write_enabled', true);
        $this->createTestTables();
        $service = $this->makeService(
            $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadMeaningfulRisk()),
            $this->mockNotifier(),
        );

        $service->writeRiskSnapshot($this->lottoPayloadMeaningfulRisk()['risk'], [
            'source' => 'rebuild_filtered',
            'reason' => 'test',
            'class' => __CLASS__,
            'file' => __FILE__,
        ]);

        $this->assertSame(1, DB::table('lotto_dashboard_risk_snapshot')->count());
    }

    public function test_write_risk_snapshot_normalizes_future_payload_timestamp_to_checkpoint_time(): void
    {
        $this->createTestTables();
        $frozen = Carbon::parse('2026-05-06 07:00:00');
        Carbon::setTestNow($frozen);

        try {
            $service = $this->makeService(
                $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadMeaningfulRisk()),
                $this->mockNotifier(),
            );

            $payloadRow = $this->lottoPayloadMeaningfulRisk()['risk'][0];
            $payloadRow['snapshot_at'] = '2026-05-06 23:59:59';
            $payloadRow['created_at'] = '2026-05-06 23:59:59';
            $payloadRow['updated_at'] = '2026-05-06 23:59:59';

            $service->writeRiskSnapshot([$payloadRow], [
                'source' => 'scheduled',
                'class' => __CLASS__,
                'file' => __FILE__,
            ]);

            $row = DB::table('lotto_dashboard_risk_snapshot')->first();
            $this->assertNotNull($row);
            $this->assertSame($frozen->toDateTimeString(), (string) $row->snapshot_at);
            $this->assertSame($frozen->toDateTimeString(), (string) $row->created_at);
            $this->assertSame($frozen->toDateTimeString(), (string) $row->updated_at);
            $this->assertLessThanOrEqual(
                strtotime((string) $row->created_at),
                strtotime((string) $row->snapshot_at)
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_write_risk_snapshot_uses_single_immutable_checkpoint_per_batch(): void
    {
        $this->createTestTables();
        $frozen = Carbon::parse('2026-05-06 07:15:00');
        Carbon::setTestNow($frozen);

        try {
            $service = $this->makeService(
                $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadMeaningfulRisk()),
                $this->mockNotifier(),
            );

            $base = $this->lottoPayloadMeaningfulRisk()['risk'][0];
            $rowA = $base;
            $rowA['number'] = '11';
            $rowA['snapshot_at'] = '2026-05-06 23:59:59';

            $rowB = $base;
            $rowB['number'] = '22';
            $rowB['snapshot_at'] = '2099-12-31 23:59:59';

            $service->writeRiskSnapshot([$rowA, $rowB], [
                'source' => 'scheduled',
                'class' => __CLASS__,
                'file' => __FILE__,
            ]);

            $rows = DB::table('lotto_dashboard_risk_snapshot')->orderBy('number')->get();
            $this->assertCount(2, $rows);
            foreach ($rows as $row) {
                $this->assertSame($frozen->toDateTimeString(), (string) $row->snapshot_at);
            }
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_write_risk_snapshot_dedupes_same_dimension_within_one_checkpoint(): void
    {
        $this->createTestTables();
        Carbon::setTestNow(Carbon::parse('2026-05-06 07:30:00'));

        try {
            $service = $this->makeService(
                $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadMeaningfulRisk()),
                $this->mockNotifier(),
            );

            $base = $this->lottoPayloadMeaningfulRisk()['risk'][0];
            $rowA = $base;
            $rowA['snapshot_at'] = '2026-05-06 23:59:59';
            $rowB = $base;
            $rowB['snapshot_at'] = '2026-05-07 23:59:59';

            $service->writeRiskSnapshot([$rowA, $rowB], [
                'source' => 'scheduled',
                'class' => __CLASS__,
                'file' => __FILE__,
            ]);

            $this->assertSame(1, DB::table('lotto_dashboard_risk_snapshot')->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_snapshot_write_path_is_append_only_and_does_not_mutate_existing_row(): void
    {
        config()->set('dashboard.lotto.legacy_snapshot_write_enabled', true);
        $this->createTestTables();
        Carbon::setTestNow(Carbon::parse('2026-05-06 07:45:00'));

        try {
            $service = $this->makeService(
                $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadMeaningfulRisk()),
                $this->mockNotifier(),
            );

            $existing = $this->lottoPayloadMeaningfulRisk()['risk'][0];
            $existing['snapshot_at'] = Carbon::now()->startOfSecond()->toDateTimeString();
            DB::table('lotto_dashboard_risk_snapshot')->insert($existing);

            $mutated = $existing;
            $mutated['stake_total'] = 9999;
            $mutated['payout_if_hit'] = 9999;
            $mutated['liability'] = 9999;

            $service->writeRiskSnapshot([$mutated], [
                'source' => 'scheduled',
                'reason' => 'test_append_only',
                'class' => __CLASS__,
                'file' => __FILE__,
            ]);

            $this->assertSame(1, DB::table('lotto_dashboard_risk_snapshot')->count());

            $row = DB::table('lotto_dashboard_risk_snapshot')->first();
            $this->assertSame((float) $existing['stake_total'], (float) $row->stake_total);
            $this->assertSame((float) $existing['payout_if_hit'], (float) $row->payout_if_hit);
            $this->assertSame((float) $existing['liability'], (float) $row->liability);
        } finally {
            Carbon::setTestNow();
        }
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

    private function seedOpenDraw(int $id): void
    {
        $this->seedDraw($id, ['status' => 'open', 'result_at' => null]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedDraw(int $id, array $overrides = []): void
    {
        DB::table('lotto_draws')->insertOrIgnore(array_merge([
            'id' => $id,
            'market_id' => 1,
            'status' => 'open',
            'result_at' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ], $overrides));
    }

    private function seedExposureRow(int $drawId, string $betType, string $number, float $soldAmount): void
    {
        DB::table('lotto_number_exposures')->insert([
            'draw_id' => $drawId,
            'bet_type' => $betType,
            'number' => $number,
            'sold_amount' => $soldAmount,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);
    }

    private function seedBetSetting(int $drawId, string $betType, float $payout): void
    {
        DB::table('lotto_draw_bet_settings')->insert([
            'draw_id' => $drawId,
            'bet_type' => $betType,
            'payout' => $payout,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function existingCurrentRowFor(int $roundId, array $overrides = []): array
    {
        return array_merge([
            'web_code' => 'main',
            'market_id' => 1,
            'round_id' => $roundId,
            'bet_type' => '2top',
            'number' => '12',
            'stake_total' => 100,
            'payout_if_hit' => 9000,
            'liability' => 8900,
        ], $overrides);
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

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('market_id')->default(1);
            $table->string('status')->default('open');
            $table->dateTime('result_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('lotto_number_exposures', function (Blueprint $table): void {
            $table->unsignedBigInteger('draw_id');
            $table->string('bet_type');
            $table->string('number');
            $table->decimal('sold_amount', 12, 2)->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('lotto_draw_bet_settings', function (Blueprint $table): void {
            $table->unsignedBigInteger('draw_id');
            $table->string('bet_type');
            $table->decimal('payout', 12, 2)->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
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
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
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
            'lotto_draw_bet_settings',
            'lotto_number_exposures',
            'lotto_draws',
        ] as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
            }
        }
    }
}
