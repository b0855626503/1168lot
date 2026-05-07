<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\YeekeeMarketSetting;
use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Models\YeekeeShoot;
use Gametech\Lotto\Models\YeekeeShootRewardLog;
use Gametech\Lotto\Services\WalletTransactionService as LottoWalletTransactionService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class YeekeeShootingRewardService
{
    private const REWARD_REF_TYPE = 'YEEKEE_SHOOT_REWARD';

    /**
     * @param  array<string,mixed>  $context
     * @return array<string,mixed>
     */
    public function applyForRound(YeekeeRound $round, LottoDraw $draw, array $context = []): array
    {
        if (! (bool) config('yeekee.reward_enabled', true)) {
            $this->recordAudit($round, $draw, null, [
                'status' => 'skipped',
                'reason' => 'emergency_kill_switch',
            ]);

            return $this->result('skipped', 'emergency_kill_switch');
        }

        try {
            return DB::transaction(function () use ($round, $draw, $context): array {
                $lockedRound = YeekeeRound::query()->lockForUpdate()->findOrFail((int) $round->id);
                $policy = $this->resolvePolicy($lockedRound);

                if (! (bool) ($policy['reward_enabled'] ?? false)) {
                    $this->recordAudit($lockedRound, $draw, null, [
                        'status' => 'skipped',
                        'reason' => 'reward_disabled',
                        'policy_source' => (string) $policy['source'],
                    ]);

                    return $this->result('skipped', 'reward_disabled', (string) $policy['source']);
                }

                $rewardConfig = is_array($policy['reward_config'] ?? null) ? $policy['reward_config'] : [];
                if (array_key_exists('reward_enabled', $rewardConfig) && ! $this->toBool($rewardConfig['reward_enabled'])) {
                    $this->recordAudit($lockedRound, $draw, null, [
                        'status' => 'skipped',
                        'reason' => 'reward_config_disabled',
                        'policy_source' => (string) $policy['source'],
                    ]);

                    return $this->result('skipped', 'reward_config_disabled', (string) $policy['source']);
                }

                $normalizedPolicy = $this->normalizeRewardPolicy($rewardConfig);
                if ($normalizedPolicy['reward_positions'] === []) {
                    $this->recordAudit($lockedRound, $draw, null, [
                        'status' => 'invalid_policy',
                        'reason' => 'missing_valid_reward_positions',
                        'policy_source' => (string) $policy['source'],
                        'policy_hash' => $normalizedPolicy['policy_hash'],
                        'metadata_json' => ['formula_preset' => (string) ($context['formula_preset'] ?? '')],
                    ]);

                    return $this->result('invalid_policy', 'missing_valid_reward_positions', (string) $policy['source']);
                }

                $results = [];
                foreach ($normalizedPolicy['reward_positions'] as $rewardPosition) {
                    $results[] = $this->applyRewardPosition(
                        $lockedRound,
                        $draw,
                        (string) $policy['source'],
                        $normalizedPolicy,
                        $rewardPosition
                    );
                }

                return $this->aggregateResults($results, (string) $policy['source']);
            });
        } catch (Throwable $exception) {
            Log::error('yeekee.shooting_reward.failed', [
                'yeekee_round_id' => (int) $round->id,
                'lotto_draw_id' => (int) $draw->id,
                'market_id' => (int) $draw->market_id,
                'reason' => 'exception',
                'message' => $exception->getMessage(),
            ]);

            $this->recordAudit($round, $draw, null, [
                'status' => 'failed',
                'reason' => 'exception',
                'metadata_json' => ['message' => $exception->getMessage()],
            ]);

            return $this->result('failed', 'exception');
        }
    }

    /**
     * @param  array{reward_positions:array<int,array{position:int,credit_amount:float}>,min_bet_amount:float,reward_scope:string,max_rewards_per_member_per_round:int,max_rewards_per_member_per_day:int,currency:string,policy_hash:string}  $policy
     * @param  array{position:int,credit_amount:float}  $rewardPosition
     * @return array<string,mixed>
     */
    private function applyRewardPosition(YeekeeRound $round, LottoDraw $draw, string $policySource, array $policy, array $rewardPosition): array
    {
        $position = (int) $rewardPosition['position'];
        $amount = (float) $rewardPosition['credit_amount'];
        $currency = (string) $policy['currency'];
        $policyHash = (string) $policy['policy_hash'];

        $shoot = YeekeeShoot::query()
            ->where('yeekee_round_id', (int) $round->id)
            ->where('position', $position)
            ->orderBy('id')
            ->first();

        if (! $shoot instanceof YeekeeShoot) {
            return $this->skipPosition($round, $draw, null, $policySource, $policyHash, $position, $amount, $currency, 'missing_position_shoot');
        }

        $memberId = (int) $shoot->member_id;
        $minBetAmount = (float) $policy['min_bet_amount'];
        if ($minBetAmount > 0) {
            $memberRoundBet = $this->memberValidBetAmount((int) $draw->id, $memberId);
            if ($memberRoundBet <= 0) {
                return $this->skipPosition($round, $draw, $shoot, $policySource, $policyHash, $position, $amount, $currency, 'member_has_no_valid_bet_same_round');
            }

            if ($memberRoundBet < $minBetAmount) {
                return $this->skipPosition($round, $draw, $shoot, $policySource, $policyHash, $position, $amount, $currency, 'min_bet_amount_not_met', [
                    'member_round_bet' => $memberRoundBet,
                    'min_bet_amount' => $minBetAmount,
                ]);
            }
        }

        $idempotencyKey = sprintf(
            'YEEKEE_SHOOT_REWARD:%d:%d:%d:%d:%s',
            (int) $draw->id,
            (int) $round->id,
            $memberId,
            $position,
            $policyHash
        );

        $log = YeekeeShootRewardLog::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if (! $log instanceof YeekeeShootRewardLog) {
            $log = YeekeeShootRewardLog::query()
                ->where('yeekee_round_id', (int) $round->id)
                ->where('member_id', $memberId)
                ->where('position', $position)
                ->first();
        }

        if (! $log instanceof YeekeeShootRewardLog) {
            $log = new YeekeeShootRewardLog;
        }

        $log->forceFill($this->rewardLogPayload($round, $draw, $shoot, $position, $amount, $currency, $policySource, $policyHash, 'pending', 'pending', null) + [
            'idempotency_key' => $idempotencyKey,
        ])->save();

        $alreadyPaid = DB::table('wallet_transactions')
            ->where('member_id', $memberId)
            ->where('direction', 'CREDIT')
            ->where('ref_type', self::REWARD_REF_TYPE)
            ->where('ref_id', (int) $log->id)
            ->exists();

        if ($alreadyPaid) {
            $this->updateRewardLog($log, [
                'status' => 'paid',
                'reason' => 'already_paid',
            ]);

            Log::info('yeekee.shooting_reward.already_paid', $this->logContext($round, $draw, $shoot, $position, $amount, $currency, $policySource, $idempotencyKey, 'already_paid', $policyHash));

            return $this->result('already_paid', 'already_paid', $policySource, $idempotencyKey, (int) $shoot->id, $memberId, $position, $amount, $currency);
        }

        $scopeDuplicateReason = $this->resolveScopeDuplicateReason($round, $draw, $memberId, $policy);
        if ($scopeDuplicateReason !== null) {
            return $this->skipPosition($round, $draw, $shoot, $policySource, $policyHash, $position, $amount, $currency, $scopeDuplicateReason);
        }

        $walletTransactionId = app(LottoWalletTransactionService::class)->creditMemberBalance(
            memberId: $memberId,
            amount: $amount,
            refType: self::REWARD_REF_TYPE,
            refId: (int) $log->id,
            refCode: (string) $draw->id,
            groupCode: sprintf('YEEKEE_SHOOT_REWARD_%d', (int) $draw->id),
            meta: [
                'draw_id' => (int) $draw->id,
                'yeekee_round_id' => (int) $round->id,
                'yeekee_shoot_id' => (int) $shoot->id,
                'position' => $position,
                'policy_hash' => $policyHash,
                'idempotency_key' => $idempotencyKey,
            ],
            createdByType: 'system',
            createdById: null,
            description: 'จ่ายรางวัลผู้ยิงเลขยี่กี่'
        );

        $this->updateRewardLog($log, [
            'status' => 'paid',
            'reason' => 'paid',
            'wallet_transaction_id' => $walletTransactionId,
            'paid_at' => now(),
        ]);

        Log::info('yeekee.shooting_reward.paid', $this->logContext($round, $draw, $shoot, $position, $amount, $currency, $policySource, $idempotencyKey, 'paid', $policyHash));

        return $this->result('paid', 'paid', $policySource, $idempotencyKey, (int) $shoot->id, $memberId, $position, $amount, $currency);
    }

    /**
     * @return array{reward_enabled:bool,reward_config:array<string,mixed>,source:string}
     */
    private function resolvePolicy(YeekeeRound $round): array
    {
        $snapshot = is_array($round->config_snapshot_json) ? $round->config_snapshot_json : [];
        $hasSnapshotRewardPolicy = array_key_exists('reward_config', $snapshot) || array_key_exists('reward_enabled', $snapshot);
        if ($hasSnapshotRewardPolicy) {
            $rewardConfig = is_array($snapshot['reward_config'] ?? null) ? $snapshot['reward_config'] : [];
            $marketRewardEnabled = $this->resolveMarketRewardEnabled((int) $round->market_id);
            $snapshotRewardEnabled = array_key_exists('reward_enabled', $snapshot)
                ? $this->toBool($snapshot['reward_enabled'])
                : $marketRewardEnabled;

            return [
                'reward_enabled' => $snapshotRewardEnabled,
                'reward_config' => $rewardConfig,
                'source' => 'round_snapshot',
            ];
        }

        $setting = $this->resolveMarketSetting((int) $round->market_id);
        if (! $setting instanceof YeekeeMarketSetting) {
            return [
                'reward_enabled' => false,
                'reward_config' => [],
                'source' => 'default_disabled',
            ];
        }

        return [
            'reward_enabled' => (bool) ($setting->reward_enabled ?? false),
            'reward_config' => is_array($setting->reward_config) ? $setting->reward_config : [],
            'source' => 'market_setting',
        ];
    }

    private function resolveMarketRewardEnabled(int $marketId): bool
    {
        $setting = $this->resolveMarketSetting($marketId);

        return $setting instanceof YeekeeMarketSetting
            ? (bool) ($setting->reward_enabled ?? false)
            : false;
    }

    private function resolveMarketSetting(int $marketId): ?YeekeeMarketSetting
    {
        if (! Schema::hasTable('yeekee_market_settings')) {
            return null;
        }

        $setting = YeekeeMarketSetting::query()->where('market_id', $marketId)->first();

        return $setting instanceof YeekeeMarketSetting ? $setting : null;
    }

    /**
     * @param  array<string,mixed>  $rewardConfig
     * @return array{reward_positions:array<int,array{position:int,credit_amount:float}>,min_bet_amount:float,reward_scope:string,max_rewards_per_member_per_round:int,max_rewards_per_member_per_day:int,currency:string,policy_hash:string}
     */
    private function normalizeRewardPolicy(array $rewardConfig): array
    {
        $rewardPositions = [];
        foreach ((array) ($rewardConfig['reward_positions'] ?? []) as $positionConfig) {
            if (! is_array($positionConfig)) {
                continue;
            }

            $position = (int) ($positionConfig['position'] ?? 0);
            $creditAmount = round((float) ($positionConfig['credit_amount'] ?? 0), 2);
            if ($position <= 0 || $creditAmount <= 0) {
                continue;
            }

            $rewardPositions[] = [
                'position' => $position,
                'credit_amount' => $creditAmount,
            ];
        }

        $rewardScope = strtolower(trim((string) ($rewardConfig['reward_scope'] ?? 'per_round')));
        if (! in_array($rewardScope, ['per_round', 'per_market_per_day', 'global_per_day'], true)) {
            $rewardScope = 'per_round';
        }

        $policy = [
            'reward_positions' => $rewardPositions,
            'min_bet_amount' => max(0.0, round((float) ($rewardConfig['min_bet_amount'] ?? 0), 2)),
            'reward_scope' => $rewardScope,
            'max_rewards_per_member_per_round' => max(1, (int) ($rewardConfig['max_rewards_per_member_per_round'] ?? max(1, count($rewardPositions)))),
            'max_rewards_per_member_per_day' => max(0, (int) ($rewardConfig['max_rewards_per_member_per_day'] ?? 0)),
            'currency' => strtoupper(trim((string) ($rewardConfig['currency'] ?? 'THB'))) ?: 'THB',
        ];

        $policy['policy_hash'] = hash('sha256', json_encode($policy, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));

        return $policy;
    }

    private function memberValidBetAmount(int $drawId, int $memberId): float
    {
        if (! Schema::hasTable('lotto_tickets')) {
            return 0.0;
        }

        $amountColumn = Schema::hasColumn('lotto_tickets', 'total_net_amount') ? 'total_net_amount' : 'total_amount';
        $query = LottoTicket::query()
            ->where('draw_id', $drawId)
            ->where('member_id', $memberId);

        if (Schema::hasColumn('lotto_tickets', 'status')) {
            $query->where('status', '!=', 'cancelled');
        }

        return round((float) $query->sum($amountColumn), 2);
    }

    /**
     * @param  array<string,mixed>  $policy
     */
    private function resolveScopeDuplicateReason(YeekeeRound $round, LottoDraw $draw, int $memberId, array $policy): ?string
    {
        $roundPaidCount = $this->paidRewardLogQuery()
            ->where('yeekee_round_id', (int) $round->id)
            ->where('member_id', $memberId)
            ->count();

        if ($roundPaidCount >= (int) $policy['max_rewards_per_member_per_round']) {
            return 'max_rewards_per_member_per_round_reached';
        }

        $maxDaily = (int) $policy['max_rewards_per_member_per_day'];
        if ($maxDaily <= 0 || (string) $policy['reward_scope'] === 'per_round') {
            return null;
        }

        $dailyQuery = $this->dailyPaidRewardLogQuery($draw)
            ->where('yeekee_shoot_reward_logs.member_id', $memberId);

        if ((string) $policy['reward_scope'] === 'per_market_per_day' && Schema::hasColumn('yeekee_shoot_reward_logs', 'market_id')) {
            $dailyQuery->where('yeekee_shoot_reward_logs.market_id', (int) $draw->market_id);
        }

        return $dailyQuery->count() >= $maxDaily
            ? 'max_rewards_per_member_per_day_reached'
            : null;
    }

    private function paidRewardLogQuery(): Builder
    {
        $query = DB::table('yeekee_shoot_reward_logs')
            ->where('yeekee_shoot_reward_logs.reward_ref_type', self::REWARD_REF_TYPE);

        if (Schema::hasColumn('yeekee_shoot_reward_logs', 'status')) {
            $query->where('yeekee_shoot_reward_logs.status', 'paid');
        }

        return $query;
    }

    private function dailyPaidRewardLogQuery(LottoDraw $draw): Builder
    {
        $query = $this->paidRewardLogQuery();
        $drawDate = $draw->draw_date instanceof \DateTimeInterface
            ? $draw->draw_date->format('Y-m-d')
            : substr((string) $draw->draw_date, 0, 10);

        if (
            Schema::hasTable('lotto_draws')
            && Schema::hasColumn('yeekee_shoot_reward_logs', 'lotto_draw_id')
            && Schema::hasColumn('lotto_draws', 'draw_date')
        ) {
            return $query
                ->join('lotto_draws', 'lotto_draws.id', '=', 'yeekee_shoot_reward_logs.lotto_draw_id')
                ->whereDate('lotto_draws.draw_date', $drawDate);
        }

        return $query->whereDate('yeekee_shoot_reward_logs.created_at', $drawDate);
    }

    /**
     * @param  array<string,mixed>  $metadata
     * @return array<string,mixed>
     */
    private function skipPosition(YeekeeRound $round, LottoDraw $draw, ?YeekeeShoot $shoot, string $policySource, string $policyHash, int $position, float $amount, string $currency, string $reason, array $metadata = []): array
    {
        $this->recordAudit($round, $draw, $shoot, [
            'status' => 'skipped',
            'reason' => $reason,
            'position' => $position,
            'amount' => $amount,
            'currency' => $currency,
            'policy_source' => $policySource,
            'policy_hash' => $policyHash,
            'metadata_json' => $metadata,
        ]);

        Log::info('yeekee.shooting_reward.skipped', $this->logContext($round, $draw, $shoot, $position, $amount, $currency, $policySource, null, $reason, $policyHash) + ['metadata' => $metadata]);

        return $this->result('skipped', $reason, $policySource, null, $shoot instanceof YeekeeShoot ? (int) $shoot->id : null, $shoot instanceof YeekeeShoot ? (int) $shoot->member_id : null, $position, $amount, $currency);
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    private function recordAudit(YeekeeRound $round, LottoDraw $draw, ?YeekeeShoot $shoot, array $attributes): void
    {
        if (! Schema::hasTable('yeekee_shoot_reward_logs') || ! Schema::hasColumn('yeekee_shoot_reward_logs', 'status')) {
            return;
        }

        $position = (int) ($attributes['position'] ?? 0);
        if ($position <= 0 || ! $shoot instanceof YeekeeShoot) {
            Log::info('yeekee.shooting_reward.audit', $this->logContext(
                $round,
                $draw,
                $shoot,
                $position > 0 ? $position : null,
                isset($attributes['amount']) ? (float) $attributes['amount'] : null,
                isset($attributes['currency']) ? (string) $attributes['currency'] : null,
                isset($attributes['policy_source']) ? (string) $attributes['policy_source'] : null,
                null,
                (string) ($attributes['reason'] ?? ''),
                isset($attributes['policy_hash']) ? (string) $attributes['policy_hash'] : null
            ));

            return;
        }

        $payload = $this->rewardLogPayload(
            $round,
            $draw,
            $shoot,
            $position,
            (float) ($attributes['amount'] ?? 0),
            (string) ($attributes['currency'] ?? 'THB'),
            isset($attributes['policy_source']) ? (string) $attributes['policy_source'] : null,
            isset($attributes['policy_hash']) ? (string) $attributes['policy_hash'] : null,
            (string) ($attributes['status'] ?? 'skipped'),
            (string) ($attributes['reason'] ?? 'skipped'),
            is_array($attributes['metadata_json'] ?? null) ? $attributes['metadata_json'] : null
        );

        YeekeeShootRewardLog::query()->firstOrCreate([
            'yeekee_round_id' => (int) $round->id,
            'member_id' => (int) $shoot->member_id,
            'position' => $position,
        ], $payload);
    }

    /**
     * @param  array<string,mixed>|null  $metadata
     * @return array<string,mixed>
     */
    private function rewardLogPayload(YeekeeRound $round, LottoDraw $draw, YeekeeShoot $shoot, int $position, float $amount, string $currency, ?string $policySource, ?string $policyHash, string $status, string $reason, ?array $metadata): array
    {
        $payload = [
            'yeekee_round_id' => (int) $round->id,
            'member_id' => (int) $shoot->member_id,
            'position' => $position,
            'credit_amount' => $amount,
            'reward_ref_type' => self::REWARD_REF_TYPE,
        ];

        $optional = [
            'lotto_draw_id' => (int) $draw->id,
            'market_id' => (int) $draw->market_id,
            'yeekee_shoot_id' => (int) $shoot->id,
            'currency' => $currency,
            'status' => $status,
            'reason' => $reason,
            'policy_source' => $policySource,
            'policy_hash' => $policyHash,
            'metadata_json' => $metadata,
        ];

        foreach ($optional as $column => $value) {
            if (Schema::hasColumn('yeekee_shoot_reward_logs', $column)) {
                $payload[$column] = $value;
            }
        }

        return $payload;
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    private function updateRewardLog(YeekeeShootRewardLog $log, array $attributes): void
    {
        $payload = [];
        foreach ($attributes as $column => $value) {
            if (Schema::hasColumn('yeekee_shoot_reward_logs', $column)) {
                $payload[$column] = $value;
            }
        }

        if ($payload !== []) {
            $log->forceFill($payload)->save();
        }
    }

    /**
     * @param  array<int,array<string,mixed>>  $results
     * @return array<string,mixed>
     */
    private function aggregateResults(array $results, string $policySource): array
    {
        $paidCount = count(array_filter($results, static fn (array $result): bool => (string) ($result['status'] ?? '') === 'paid'));
        $alreadyPaidCount = count(array_filter($results, static fn (array $result): bool => (string) ($result['status'] ?? '') === 'already_paid'));
        $skippedCount = count(array_filter($results, static fn (array $result): bool => (string) ($result['status'] ?? '') === 'skipped'));
        $invalidCount = count(array_filter($results, static fn (array $result): bool => (string) ($result['status'] ?? '') === 'invalid_policy'));

        $primary = $results[0] ?? $this->result('skipped', 'no_reward_positions', $policySource);
        if ($paidCount > 0) {
            $primary = $this->firstResultByStatus($results, 'paid') ?? $primary;
        } elseif ($alreadyPaidCount > 0 && $skippedCount === 0 && $invalidCount === 0) {
            $primary = $this->firstResultByStatus($results, 'already_paid') ?? $primary;
        }

        return $primary + [
            'paid_count' => $paidCount,
            'already_paid_count' => $alreadyPaidCount,
            'skipped_count' => $skippedCount,
            'invalid_count' => $invalidCount,
            'total_positions' => count($results),
        ];
    }

    /**
     * @param  array<int,array<string,mixed>>  $results
     * @return array<string,mixed>|null
     */
    private function firstResultByStatus(array $results, string $status): ?array
    {
        foreach ($results as $result) {
            if ((string) ($result['status'] ?? '') === $status) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @return array<string,mixed>
     */
    private function result(
        string $status,
        string $reason,
        ?string $policySource = null,
        ?string $idempotencyKey = null,
        ?int $shootId = null,
        ?int $memberId = null,
        ?int $position = null,
        ?float $amount = null,
        ?string $currency = null
    ): array {
        return [
            'status' => $status,
            'reason' => $reason,
            'policy_source' => $policySource,
            'idempotency_key' => $idempotencyKey,
            'shoot_id' => $shootId,
            'member_id' => $memberId,
            'position' => $position,
            'amount' => $amount,
            'currency' => $currency,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function logContext(YeekeeRound $round, LottoDraw $draw, ?YeekeeShoot $shoot, ?int $position, ?float $amount, ?string $currency, ?string $policySource, ?string $idempotencyKey, string $reason, ?string $policyHash): array
    {
        return [
            'yeekee_round_id' => (int) $round->id,
            'lotto_draw_id' => (int) $draw->id,
            'market_id' => (int) $draw->market_id,
            'shoot_id' => $shoot instanceof YeekeeShoot ? (int) $shoot->id : null,
            'member_id' => $shoot instanceof YeekeeShoot ? (int) $shoot->member_id : null,
            'position' => $position,
            'amount' => $amount,
            'currency' => $currency,
            'policy_source' => $policySource,
            'policy_hash' => $policyHash,
            'idempotency_key' => $idempotencyKey,
            'reason' => $reason,
        ];
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
