<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\YeekeeMarketSetting;
use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Models\YeekeeShoot;
use Gametech\Lotto\Models\YeekeeShootRewardLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class YeekeeShootingRewardService
{
    private const REWARD_REF_TYPE = 'YEEKEE_SHOOT_REWARD';

    /**
     * @param  array<string,mixed>  $context
     * @return array{status:string,reason:string,policy_source:?string,idempotency_key:?string,shoot_id:?int,member_id:?int,position:?int,amount:?float,currency:?string}
     */
    public function applyForRound(YeekeeRound $round, LottoDraw $draw, array $context = []): array
    {
        if (! (bool) config('yeekee.reward_enabled', true)) {
            Log::info('yeekee.shooting_reward.skipped', [
                'yeekee_round_id' => (int) $round->id,
                'lotto_draw_id' => (int) $draw->id,
                'market_id' => (int) $draw->market_id,
                'shoot_id' => null,
                'member_id' => null,
                'position' => null,
                'amount' => null,
                'currency' => null,
                'policy_source' => null,
                'idempotency_key' => null,
                'reason' => 'emergency_kill_switch',
            ]);

            return $this->result('skipped', 'emergency_kill_switch');
        }

        try {
            return DB::transaction(function () use ($round, $draw, $context): array {
                $lockedRound = YeekeeRound::query()->lockForUpdate()->findOrFail((int) $round->id);
                $policy = $this->resolvePolicy($lockedRound);

                if (! (bool) ($policy['reward_enabled'] ?? false)) {
                    Log::info('yeekee.shooting_reward.skipped', [
                        'yeekee_round_id' => (int) $lockedRound->id,
                        'lotto_draw_id' => (int) $draw->id,
                        'market_id' => (int) $draw->market_id,
                        'shoot_id' => null,
                        'member_id' => null,
                        'position' => null,
                        'amount' => null,
                        'currency' => null,
                        'policy_source' => (string) $policy['source'],
                        'idempotency_key' => null,
                        'reason' => 'reward_disabled',
                    ]);

                    return $this->result('skipped', 'reward_disabled', (string) $policy['source']);
                }

                $rewardConfig = is_array($policy['reward_config'] ?? null) ? $policy['reward_config'] : [];
                if (! $this->toBool($rewardConfig['enabled'] ?? false)) {
                    Log::info('yeekee.shooting_reward.skipped', [
                        'yeekee_round_id' => (int) $lockedRound->id,
                        'lotto_draw_id' => (int) $draw->id,
                        'market_id' => (int) $draw->market_id,
                        'shoot_id' => null,
                        'member_id' => null,
                        'position' => null,
                        'amount' => null,
                        'currency' => null,
                        'policy_source' => (string) $policy['source'],
                        'idempotency_key' => null,
                        'reason' => 'reward_config_disabled',
                    ]);

                    return $this->result('skipped', 'reward_config_disabled', (string) $policy['source']);
                }

                $normalizedPolicy = $this->validateV1Policy($rewardConfig);
                if ($normalizedPolicy === null) {
                    Log::warning('yeekee.shooting_reward.invalid_policy', [
                        'yeekee_round_id' => (int) $lockedRound->id,
                        'lotto_draw_id' => (int) $draw->id,
                        'market_id' => (int) $draw->market_id,
                        'policy_source' => (string) $policy['source'],
                        'formula_preset' => (string) ($context['formula_preset'] ?? ''),
                        'reason' => 'unsupported_or_invalid_policy',
                    ]);

                    return $this->result('invalid_policy', 'unsupported_or_invalid_policy', (string) $policy['source']);
                }

                $shoot = YeekeeShoot::query()
                    ->where('yeekee_round_id', (int) $lockedRound->id)
                    ->where('position', (int) $normalizedPolicy['position'])
                    ->orderBy('id')
                    ->first();

                if (! $shoot instanceof YeekeeShoot) {
                    Log::info('yeekee.shooting_reward.skipped', [
                        'yeekee_round_id' => (int) $lockedRound->id,
                        'lotto_draw_id' => (int) $draw->id,
                        'market_id' => (int) $draw->market_id,
                        'shoot_id' => null,
                        'member_id' => null,
                        'position' => (int) $normalizedPolicy['position'],
                        'amount' => (float) $normalizedPolicy['amount'],
                        'currency' => (string) $normalizedPolicy['currency'],
                        'policy_source' => (string) $policy['source'],
                        'idempotency_key' => null,
                        'reason' => 'missing_position_shoot',
                    ]);

                    return $this->result(
                        'skipped',
                        'missing_position_shoot',
                        (string) $policy['source'],
                        null,
                        null,
                        null,
                        (int) $normalizedPolicy['position'],
                        (float) $normalizedPolicy['amount'],
                        (string) $normalizedPolicy['currency']
                    );
                }

                $idempotencyKey = sprintf(
                    'YEEKEE_SHOOT_REWARD:%d:%d:%d:%d',
                    (int) $draw->id,
                    (int) $lockedRound->id,
                    (int) $shoot->id,
                    (int) $normalizedPolicy['position']
                );

                $log = YeekeeShootRewardLog::query()->firstOrCreate(
                    [
                        'idempotency_key' => $idempotencyKey,
                    ],
                    [
                        'yeekee_round_id' => (int) $lockedRound->id,
                        'member_id' => (int) $shoot->member_id,
                        'position' => (int) $normalizedPolicy['position'],
                        'credit_amount' => (float) $normalizedPolicy['amount'],
                        'reward_ref_type' => self::REWARD_REF_TYPE,
                    ]
                );

                $alreadyPaid = DB::table('wallet_transactions')
                    ->where('member_id', (int) $shoot->member_id)
                    ->where('direction', 'CREDIT')
                    ->where('ref_type', self::REWARD_REF_TYPE)
                    ->where('ref_id', (int) $log->id)
                    ->exists();

                if ($alreadyPaid) {
                    Log::info('yeekee.shooting_reward.already_paid', [
                        'yeekee_round_id' => (int) $lockedRound->id,
                        'lotto_draw_id' => (int) $draw->id,
                        'market_id' => (int) $draw->market_id,
                        'shoot_id' => (int) $shoot->id,
                        'member_id' => (int) $shoot->member_id,
                        'position' => (int) $normalizedPolicy['position'],
                        'amount' => (float) $normalizedPolicy['amount'],
                        'currency' => (string) $normalizedPolicy['currency'],
                        'policy_source' => (string) $policy['source'],
                        'idempotency_key' => $idempotencyKey,
                        'reason' => 'already_paid',
                    ]);

                    return $this->result(
                        'already_paid',
                        'already_paid',
                        (string) $policy['source'],
                        $idempotencyKey,
                        (int) $shoot->id,
                        (int) $shoot->member_id,
                        (int) $normalizedPolicy['position'],
                        (float) $normalizedPolicy['amount'],
                        (string) $normalizedPolicy['currency']
                    );
                }

                app(WalletTransactionService::class)->creditMemberBalance(
                    memberId: (int) $shoot->member_id,
                    amount: (float) $normalizedPolicy['amount'],
                    refType: self::REWARD_REF_TYPE,
                    refId: (int) $log->id,
                    refCode: (string) $draw->id,
                    groupCode: sprintf('YEEKEE_SHOOT_REWARD_%d', (int) $draw->id),
                    meta: [
                        'draw_id' => (int) $draw->id,
                        'yeekee_round_id' => (int) $lockedRound->id,
                        'yeekee_shoot_id' => (int) $shoot->id,
                        'position' => (int) $normalizedPolicy['position'],
                        'idempotency_key' => $idempotencyKey,
                    ],
                    createdByType: 'system',
                    createdById: null,
                    description: 'จ่ายรางวัลผู้ยิงเลขยี่กี่'
                );

                Log::info('yeekee.shooting_reward.paid', [
                    'yeekee_round_id' => (int) $lockedRound->id,
                    'lotto_draw_id' => (int) $draw->id,
                    'market_id' => (int) $draw->market_id,
                    'shoot_id' => (int) $shoot->id,
                    'member_id' => (int) $shoot->member_id,
                    'position' => (int) $normalizedPolicy['position'],
                    'amount' => (float) $normalizedPolicy['amount'],
                    'currency' => (string) $normalizedPolicy['currency'],
                    'policy_source' => (string) $policy['source'],
                    'idempotency_key' => $idempotencyKey,
                    'reason' => 'paid',
                ]);

                return $this->result(
                    'paid',
                    'paid',
                    (string) $policy['source'],
                    $idempotencyKey,
                    (int) $shoot->id,
                    (int) $shoot->member_id,
                    (int) $normalizedPolicy['position'],
                    (float) $normalizedPolicy['amount'],
                    (string) $normalizedPolicy['currency']
                );
            });
        } catch (Throwable $exception) {
            Log::error('yeekee.shooting_reward.failed', [
                'yeekee_round_id' => (int) $round->id,
                'lotto_draw_id' => (int) $draw->id,
                'market_id' => (int) $draw->market_id,
                'shoot_id' => null,
                'member_id' => null,
                'position' => null,
                'amount' => null,
                'currency' => null,
                'policy_source' => null,
                'idempotency_key' => null,
                'reason' => 'exception',
                'message' => $exception->getMessage(),
            ]);

            return $this->result('failed', 'exception');
        }
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
     * @return array{position:int,amount:float,currency:string}|null
     */
    private function validateV1Policy(array $rewardConfig): ?array
    {
        $type = strtoupper(trim((string) ($rewardConfig['type'] ?? '')));
        $payOn = strtoupper(trim((string) ($rewardConfig['pay_on'] ?? 'SETTLED_ONLY')));
        $position = max(1, (int) ($rewardConfig['position'] ?? 16));
        $amount = round((float) ($rewardConfig['amount'] ?? 0), 2);
        $currency = strtoupper(trim((string) ($rewardConfig['currency'] ?? 'THB')));
        if ($currency === '') {
            $currency = 'THB';
        }

        if ($type !== 'FIXED_AMOUNT_BY_POSITION') {
            return null;
        }

        if ($payOn !== 'SETTLED_ONLY') {
            return null;
        }

        if ($amount <= 0) {
            return null;
        }

        return [
            'position' => $position,
            'amount' => $amount,
            'currency' => $currency,
        ];
    }

    /**
     * @return array{status:string,reason:string,policy_source:?string,idempotency_key:?string,shoot_id:?int,member_id:?int,position:?int,amount:?float,currency:?string}
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
