<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\YeekeeMarketSetting;
use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Models\YeekeeShoot;
use Gametech\Lotto\Services\DrawCancelAllRefundService;
use Gametech\Lotto\Services\DrawService;
use Gametech\Lotto\Services\SettlementService;
use Gametech\Lotto\Services\Yeekee\Exceptions\YeekeeFormulaInputException;
use Gametech\Lotto\Services\YeekeeResultEngineService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SettleYeekeeRoundsCommand extends Command
{
    private const SUPPORTED_REFUND_POLICY_ACTION = 'VOID_AND_REFUND';

    private const PROCESSABLE_ROUND_STATUSES = [
        'draft',
        'open',
        'open_bet',
        'closed_bet',
        'shoot_open',
        'result_pending',
    ];

    private const RESULT_FETCH_STATUS_VOID_REFUND_FORMULA_INPUT_INSUFFICIENT = 'VOID_REFUND_FORMULA_INPUT_INSUFFICIENT';

    private const RESULT_FETCH_STATUS_NO_ACTIVITY_FORMULA_INPUT_INSUFFICIENT = 'NO_ACTIVITY_FORMULA_INPUT_INSUFFICIENT';

    private const RESULT_FETCH_STATUS_YEEKEE_MIN_BET_ENTRIES_VOID_REFUND = 'YEEKEE_MIN_BET_ENTRIES_VOID_REFUND';

    private const RESULT_FETCH_STATUS_YEEKEE_MIN_BET_ENTRIES_NO_ACTIVITY = 'YEEKEE_MIN_BET_ENTRIES_NO_ACTIVITY';

    protected $signature = 'lotto:settle-yeekee-rounds
        {--market_id= : Process only one yeekee market}
        {--draw_id= : Process only one draw}
        {--limit=100 : Maximum rounds per run}
        {--dry-run : Preview only without write}';

    protected $description = 'Resolve due yeekee rounds by policy: no-activity void, no-shoot refund, has-shoot settle';

    private ?bool $hasYeekeeMarketSettingsTable = null;

    private ?bool $hasLottoTicketItemsTable = null;

    public function handle(
        DrawService $drawService,
        YeekeeResultEngineService $yeekeeResultEngineService,
        SettlementService $settlementService,
        DrawCancelAllRefundService $drawCancelAllRefundService
    ): int {
        $drawService->syncScheduledStatuses();

        $dryRun = (bool) $this->option('dry-run');
        $limit = max(1, (int) $this->option('limit'));
        $marketId = $this->option('market_id');
        $drawId = $this->option('draw_id');

        $query = YeekeeRound::query()
            ->whereIn('status', self::PROCESSABLE_ROUND_STATUSES)
            ->orderBy('result_compute_at')
            ->orderBy('id')
            ->limit($limit);

        if ($marketId !== null && $marketId !== '') {
            $query->where('market_id', (int) $marketId);
        }

        if ($drawId !== null && $drawId !== '') {
            $query->where('lotto_draw_id', (int) $drawId);
        }

        $roundIds = $query->pluck('id')->all();

        $summary = [
            'dry_run' => $dryRun,
            'selected' => count($roundIds),
            'processed' => 0,
            'computed_settled' => 0,
            'void_no_activity' => 0,
            'void_refund' => 0,
            'minimum_void_refund' => 0,
            'minimum_no_activity' => 0,
            'skipped_not_due' => 0,
            'skipped_already_final' => 0,
            'errors' => 0,
        ];

        foreach ($roundIds as $roundId) {
            try {
                $result = DB::transaction(function () use (
                    $roundId,
                    $dryRun,
                    $yeekeeResultEngineService,
                    $settlementService,
                    $drawCancelAllRefundService
                ): string {
                    $round = YeekeeRound::query()->lockForUpdate()->find($roundId);
                    if (! $round instanceof YeekeeRound) {
                        return 'skipped_already_final';
                    }

                    if (! in_array((string) $round->status, self::PROCESSABLE_ROUND_STATUSES, true)) {
                        return 'skipped_already_final';
                    }

                    if (Carbon::parse((string) $round->result_compute_at)->gt(now())) {
                        return 'skipped_not_due';
                    }

                    $draw = LottoDraw::query()->lockForUpdate()->find((int) $round->lotto_draw_id);
                    if (! $draw instanceof LottoDraw) {
                        return 'skipped_already_final';
                    }

                    if ((string) $draw->status === 'resulted') {
                        if (! $dryRun) {
                            $round->status = 'resulted';
                            $round->save();
                        }

                        return 'skipped_already_final';
                    }

                    $activeBetCount = LottoTicket::query()
                        ->where('draw_id', (int) $draw->id)
                        ->where('status', 'active')
                        ->count();

                    $refundPolicy = $this->resolveRefundPolicy($round, (int) $draw->market_id, (int) $draw->id);
                    if ($refundPolicy['enabled'] && $refundPolicy['min_bet_entries_required'] > 0) {
                        $effectiveCountMode = $this->resolveCountMode((string) $refundPolicy['count_mode'], (int) $round->id, (int) $draw->id);
                        $effectiveBetEntryCount = $this->resolveEffectiveBetEntryCount($effectiveCountMode, (int) $draw->id);

                        if ($effectiveBetEntryCount < (int) $refundPolicy['min_bet_entries_required']) {
                            $noActiveTickets = $activeBetCount <= 0;
                            $resultFetchStatus = $noActiveTickets
                                ? self::RESULT_FETCH_STATUS_YEEKEE_MIN_BET_ENTRIES_NO_ACTIVITY
                                : self::RESULT_FETCH_STATUS_YEEKEE_MIN_BET_ENTRIES_VOID_REFUND;
                            $action = $noActiveTickets ? 'minimum_no_activity' : 'minimum_void_refund';

                            Log::info('yeekee.minimum_bet_entries_policy.applied', [
                                'yeekee_round_id' => (int) $round->id,
                                'lotto_draw_id' => (int) $draw->id,
                                'market_id' => (int) $draw->market_id,
                                'refund_enabled' => (bool) $refundPolicy['enabled'],
                                'min_bet_entries_required' => (int) $refundPolicy['min_bet_entries_required'],
                                'count_mode' => (string) $effectiveCountMode,
                                'effective_bet_entry_count' => (int) $effectiveBetEntryCount,
                                'active_ticket_count' => (int) $activeBetCount,
                                'action' => $action,
                                'result_fetch_status' => $resultFetchStatus,
                                'dry_run' => $dryRun,
                                'policy_source' => (string) $refundPolicy['source'],
                                'effective_action' => (string) $refundPolicy['action'],
                            ]);

                            if ($dryRun) {
                                return $action;
                            }

                            if (! $noActiveTickets) {
                                $drawCancelAllRefundService->cancelAllActiveTickets(
                                    $draw,
                                    reason: 'Yeekee bet entries below minimum: void and refund',
                                    createdByType: 'system',
                                    createdById: null,
                                    groupCode: sprintf(
                                        'YEEKEE_MIN_BET_ENTRIES_VOID_REFUND_%d_%d',
                                        (int) $draw->id,
                                        (int) $round->id
                                    )
                                );
                            }

                            $draw->forceFill([
                                'status' => 'resulted',
                                'result_number' => null,
                                'result_at' => $draw->result_at ?? now(),
                                'result_fetch_status' => $resultFetchStatus,
                                'result_applied_at' => now(),
                            ])->save();

                            $round->status = 'voided';
                            $round->save();

                            return $action;
                        }
                    }

                    $shootCount = YeekeeShoot::query()
                        ->where('yeekee_round_id', (int) $round->id)
                        ->count();

                    if ($shootCount > 0) {
                        if (! $dryRun) {
                            try {
                                $result = $yeekeeResultEngineService->computeFromRound((int) $round->id);
                                $settlementService->settleDraw($draw, [
                                    'top_3' => (string) ($result['top_3'] ?? ''),
                                    'bottom_2' => (string) ($result['bottom_2'] ?? ''),
                                ], 'settlement');
                                $this->attachFormulaAuditToDrawResult($draw->id, $result);
                                $round->status = 'resulted';
                                $round->save();
                            } catch (YeekeeFormulaInputException $exception) {
                                $formulaPreset = $this->resolveFormulaPresetFromRound($round);
                                if ($activeBetCount > 0) {
                                    $drawCancelAllRefundService->cancelAllActiveTickets(
                                        $draw,
                                        reason: 'Yeekee formula input insufficient: void and refund',
                                        createdByType: 'system',
                                        createdById: null,
                                        groupCode: 'YEEKEE_FORMULA_INPUT_VOID_REFUND_'.$draw->id.'_'.now()->format('YmdHis')
                                    );

                                    $draw->forceFill([
                                        'status' => 'resulted',
                                        'result_number' => null,
                                        'result_at' => $draw->result_at ?? now(),
                                        'result_fetch_status' => self::RESULT_FETCH_STATUS_VOID_REFUND_FORMULA_INPUT_INSUFFICIENT,
                                        'result_applied_at' => now(),
                                    ])->save();

                                    $round->status = 'voided';
                                    $round->save();

                                    Log::warning('yeekee.formula_failure_policy.recoverable', [
                                        'yeekee_round_id' => (int) $round->id,
                                        'lotto_draw_id' => (int) $draw->id,
                                        'formula_preset' => $formulaPreset,
                                        'failure_code' => $exception->failureCode(),
                                        'result_fetch_status' => self::RESULT_FETCH_STATUS_VOID_REFUND_FORMULA_INPUT_INSUFFICIENT,
                                        'ticket_count' => (int) $activeBetCount,
                                        'shoot_count' => (int) $shootCount,
                                        'message' => $exception->getMessage(),
                                    ]);

                                    return 'void_refund';
                                }

                                $draw->forceFill([
                                    'status' => 'resulted',
                                    'result_number' => null,
                                    'result_at' => $draw->result_at ?? now(),
                                    'result_fetch_status' => self::RESULT_FETCH_STATUS_NO_ACTIVITY_FORMULA_INPUT_INSUFFICIENT,
                                    'result_applied_at' => now(),
                                ])->save();

                                $round->status = 'voided';
                                $round->save();

                                Log::warning('yeekee.formula_failure_policy.recoverable', [
                                    'yeekee_round_id' => (int) $round->id,
                                    'lotto_draw_id' => (int) $draw->id,
                                    'formula_preset' => $formulaPreset,
                                    'failure_code' => $exception->failureCode(),
                                    'result_fetch_status' => self::RESULT_FETCH_STATUS_NO_ACTIVITY_FORMULA_INPUT_INSUFFICIENT,
                                    'ticket_count' => (int) $activeBetCount,
                                    'shoot_count' => (int) $shootCount,
                                    'message' => $exception->getMessage(),
                                ]);

                                return 'void_no_activity';
                            }
                        }

                        return 'computed_settled';
                    }

                    if ($activeBetCount <= 0) {
                        if (! $dryRun) {
                            $draw->forceFill([
                                'status' => 'resulted',
                                'result_number' => null,
                                'result_at' => $draw->result_at ?? now(),
                                'result_fetch_status' => 'NO_ACTIVITY',
                                'result_applied_at' => now(),
                            ])->save();

                            $round->status = 'voided';
                            $round->save();
                        }

                        return 'void_no_activity';
                    }

                    if (! $dryRun) {
                        $drawCancelAllRefundService->cancelAllActiveTickets(
                            $draw,
                            reason: 'Yeekee no shoot: void and refund',
                            createdByType: 'system',
                            createdById: null,
                            groupCode: 'YEEKEE_VOID_REFUND_'.$draw->id.'_'.now()->format('YmdHis')
                        );

                        $draw->forceFill([
                            'status' => 'resulted',
                            'result_number' => null,
                            'result_at' => $draw->result_at ?? now(),
                            'result_fetch_status' => 'VOID_REFUND',
                            'result_applied_at' => now(),
                        ])->save();

                        $round->status = 'voided';
                        $round->save();
                    }

                    return 'void_refund';
                });

                if (! isset($summary[$result])) {
                    $summary[$result] = 0;
                }
                $summary[$result]++;
                $summary['processed']++;
            } catch (\Throwable $exception) {
                $summary['errors']++;
                $this->error(sprintf('round_id=%d error=%s', (int) $roundId, $exception->getMessage()));
            }
        }

        $this->line(json_encode($summary, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    private function resolveFormulaPresetFromRound(YeekeeRound $round): string
    {
        $snapshot = is_array($round->config_snapshot_json) ? $round->config_snapshot_json : [];
        $formulaConfig = is_array($snapshot['formula_config'] ?? null) ? $snapshot['formula_config'] : [];

        return trim((string) ($formulaConfig['preset'] ?? 'SHOOTS_SUM_MINUS_POSITION'));
    }

    /**
     * @return array{enabled:bool,min_bet_entries_required:int,count_mode:string,action:string,source:string}
     */
    private function resolveRefundPolicy(YeekeeRound $round, int $marketId, int $drawId): array
    {
        $snapshot = is_array($round->config_snapshot_json) ? $round->config_snapshot_json : [];
        $snapshotRefundConfig = is_array($snapshot['refund_config'] ?? null) ? $snapshot['refund_config'] : null;
        if (is_array($snapshotRefundConfig)) {
            $enabled = $this->toBoolean(
                $snapshot['refund_if_bet_entries_below_min'] ?? $snapshotRefundConfig['refund_if_bet_entries_below_min'] ?? false
            );

            return [
                'enabled' => $enabled,
                'min_bet_entries_required' => max(0, (int) ($snapshotRefundConfig['min_bet_entries_required'] ?? 0)),
                'count_mode' => (string) ($snapshotRefundConfig['count_mode'] ?? 'count_bet_entries'),
                'action' => $this->resolvePolicyAction(
                    (string) ($snapshotRefundConfig['action'] ?? self::SUPPORTED_REFUND_POLICY_ACTION),
                    (int) $round->id,
                    $drawId,
                    $marketId,
                    'round_snapshot'
                ),
                'source' => 'round_snapshot',
            ];
        }

        if (! $this->hasYeekeeMarketSettingsTable()) {
            return [
                'enabled' => false,
                'min_bet_entries_required' => 0,
                'count_mode' => 'count_bet_entries',
                'action' => self::SUPPORTED_REFUND_POLICY_ACTION,
                'source' => 'default_disabled',
            ];
        }

        $setting = YeekeeMarketSetting::query()->where('market_id', $marketId)->first();
        if ($setting instanceof YeekeeMarketSetting) {
            $refundConfig = is_array($setting->refund_config) ? $setting->refund_config : [];
            $enabled = $this->toBoolean(
                $setting->refund_if_bet_entries_below_min ?? $refundConfig['refund_if_bet_entries_below_min'] ?? false
            );

            return [
                'enabled' => $enabled,
                'min_bet_entries_required' => max(0, (int) ($refundConfig['min_bet_entries_required'] ?? 0)),
                'count_mode' => (string) ($refundConfig['count_mode'] ?? 'count_bet_entries'),
                'action' => $this->resolvePolicyAction(
                    (string) ($refundConfig['action'] ?? self::SUPPORTED_REFUND_POLICY_ACTION),
                    (int) $round->id,
                    $drawId,
                    $marketId,
                    'market_setting'
                ),
                'source' => 'market_setting',
            ];
        }

        return [
            'enabled' => false,
            'min_bet_entries_required' => 0,
            'count_mode' => 'count_bet_entries',
            'action' => self::SUPPORTED_REFUND_POLICY_ACTION,
            'source' => 'default_disabled',
        ];
    }

    private function resolvePolicyAction(
        string $action,
        int $roundId,
        int $drawId,
        int $marketId,
        string $policySource
    ): string {
        $normalized = strtoupper(trim($action));
        if ($normalized === '') {
            return self::SUPPORTED_REFUND_POLICY_ACTION;
        }

        if ($normalized === self::SUPPORTED_REFUND_POLICY_ACTION) {
            return $normalized;
        }

        Log::warning('yeekee.minimum_bet_entries_policy.invalid_action', [
            'yeekee_round_id' => $roundId,
            'lotto_draw_id' => $drawId,
            'market_id' => $marketId,
            'policy_source' => $policySource,
            'original_action' => $action,
            'effective_action' => self::SUPPORTED_REFUND_POLICY_ACTION,
        ]);

        return self::SUPPORTED_REFUND_POLICY_ACTION;
    }

    private function resolveCountMode(string $countMode, int $roundId, int $drawId): string
    {
        $normalized = trim(strtolower($countMode));
        if (in_array($normalized, ['count_bet_entries', 'count_unique_members'], true)) {
            return $normalized;
        }

        Log::warning('yeekee.minimum_bet_entries_policy.invalid_count_mode', [
            'yeekee_round_id' => $roundId,
            'lotto_draw_id' => $drawId,
            'count_mode' => $countMode,
            'fallback_count_mode' => 'count_bet_entries',
        ]);

        return 'count_bet_entries';
    }

    private function resolveEffectiveBetEntryCount(string $countMode, int $drawId): int
    {
        if ($countMode === 'count_unique_members') {
            return (int) LottoTicket::query()
                ->where('draw_id', $drawId)
                ->where('status', 'active')
                ->distinct('member_id')
                ->count('member_id');
        }

        if ($this->hasLottoTicketItemsTable()) {
            return (int) DB::table('lotto_ticket_items as items')
                ->join('lotto_tickets as tickets', 'tickets.id', '=', 'items.ticket_id')
                ->where('tickets.draw_id', $drawId)
                ->where('tickets.status', 'active')
                ->count('items.id');
        }

        Log::warning('yeekee.minimum_bet_entries_policy.ticket_items_table_missing', [
            'lotto_draw_id' => $drawId,
            'fallback' => 'count_active_tickets',
        ]);

        return (int) LottoTicket::query()
            ->where('draw_id', $drawId)
            ->where('status', 'active')
            ->count();
    }

    private function hasYeekeeMarketSettingsTable(): bool
    {
        if ($this->hasYeekeeMarketSettingsTable === null) {
            $this->hasYeekeeMarketSettingsTable = Schema::hasTable('yeekee_market_settings');
        }

        return $this->hasYeekeeMarketSettingsTable;
    }

    private function hasLottoTicketItemsTable(): bool
    {
        if ($this->hasLottoTicketItemsTable === null) {
            $this->hasLottoTicketItemsTable = Schema::hasTable('lotto_ticket_items');
        }

        return $this->hasLottoTicketItemsTable;
    }

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }
        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    /**
     * @param  array<string,mixed>  $result
     */
    private function attachFormulaAuditToDrawResult(int $drawId, array $result): void
    {
        $formulaAudit = is_array($result['formula_audit'] ?? null) ? $result['formula_audit'] : null;
        if ($formulaAudit === null) {
            return;
        }

        $draw = LottoDraw::query()->find($drawId);
        if (! $draw instanceof LottoDraw) {
            return;
        }

        $existingResultNumber = is_array($draw->result_number) ? $draw->result_number : [];
        $existingResultNumber['formula_audit'] = $formulaAudit;
        $draw->forceFill([
            'result_number' => $existingResultNumber,
        ])->save();
    }
}
