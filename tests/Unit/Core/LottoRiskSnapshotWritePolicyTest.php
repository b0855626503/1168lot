<?php

namespace Tests\Unit\Core;

use App\Services\Dashboard\LottoRiskSnapshotWritePolicy;
use Tests\TestCase;

class LottoRiskSnapshotWritePolicyTest extends TestCase
{
    private LottoRiskSnapshotWritePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new LottoRiskSnapshotWritePolicy;
    }

    public function test_zero_risk_scheduled_sync_is_blocked(): void
    {
        $decision = $this->policy->evaluate([
            ['stake_total' => 0, 'payout_if_hit' => 0, 'liability' => 0],
        ], ['source' => 'scheduled']);

        $this->assertFalse($decision['allowed']);
        $this->assertFalse($decision['has_meaningful_risk']);
        $this->assertSame('zero_risk_non_audit', $decision['reason']);
    }

    public function test_meaningful_risk_scheduled_sync_is_allowed(): void
    {
        $decision = $this->policy->evaluate([
            ['stake_total' => 1, 'payout_if_hit' => 0, 'liability' => 0],
        ], ['source' => 'scheduled']);

        $this->assertTrue($decision['allowed']);
        $this->assertTrue($decision['has_meaningful_risk']);
        $this->assertSame('meaningful_risk', $decision['reason']);
    }

    public function test_draw_closed_audit_event_allows_zero_risk_snapshot(): void
    {
        $decision = $this->policy->evaluate([
            ['stake_total' => 0, 'payout_if_hit' => 0, 'liability' => 0],
        ], ['source' => 'draw_closed']);

        $this->assertTrue($decision['allowed']);
        $this->assertFalse($decision['has_meaningful_risk']);
        $this->assertSame('audit_event', $decision['reason']);
    }

    public function test_draw_resulted_audit_event_allows_zero_risk_snapshot(): void
    {
        $decision = $this->policy->evaluate([
            ['stake_total' => 0, 'payout_if_hit' => 0, 'liability' => 0],
        ], ['source' => 'draw_resulted']);

        $this->assertTrue($decision['allowed']);
        $this->assertFalse($decision['has_meaningful_risk']);
        $this->assertSame('audit_event', $decision['reason']);
    }

    public function test_manual_audit_event_allows_zero_risk_snapshot(): void
    {
        $decision = $this->policy->evaluate([
            ['stake_total' => 0, 'payout_if_hit' => 0, 'liability' => 0],
        ], ['source' => 'manual_audit', 'reason' => 'operator request']);

        $this->assertTrue($decision['allowed']);
        $this->assertFalse($decision['has_meaningful_risk']);
        $this->assertSame('audit_event', $decision['reason']);
    }

    public function test_unknown_source_without_meaningful_risk_is_blocked(): void
    {
        $decision = $this->policy->evaluate([
            ['stake_total' => 0, 'payout_if_hit' => 0, 'liability' => 0],
        ], ['source' => 'lotto']);

        $this->assertFalse($decision['allowed']);
        $this->assertSame('lotto', $decision['source']);
    }
}
