<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Member\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MemberMarketPolicyService
{
    public const ROLLOUT_NEW_ONLY = 'new_only';
    public const ROLLOUT_ALL = 'all';
    public const ROLLOUT_SELECTED = 'selected';
    private const POLICY_SOURCE_INHERIT = 'inherit';
    private const POLICY_SOURCE_ADMIN_ROLLOUT = 'admin_rollout';

    private const VALID_ROLLOUT_MODES = [
        self::ROLLOUT_NEW_ONLY,
        self::ROLLOUT_ALL,
        self::ROLLOUT_SELECTED,
    ];

    public function bootstrapForMember(int $memberId): void
    {
        if (! $this->supportsPolicyTables()) {
            return;
        }

        $markets = $this->loadEnabledMarketsWithGroup();

        $this->syncPoliciesForMembers(collect([$memberId]), $markets, false, self::POLICY_SOURCE_INHERIT);
    }

    public function bootstrapAllMembers(int $chunkSize = 500): int
    {
        if (! $this->supportsPolicyTables()) {
            return 0;
        }

        $processed = 0;
        $markets = $this->loadEnabledMarketsWithGroup();

        Member::query()
            ->select('code')
            ->orderBy('code')
            ->chunk($chunkSize, function (Collection $members) use (&$processed, $markets): void {
                $memberIds = $members
                    ->pluck('code')
                    ->map(static fn ($code): int => (int) $code)
                    ->values();

                if ($memberIds->isEmpty()) {
                    return;
                }

                $this->syncPoliciesForMembers($memberIds, $markets, false, self::POLICY_SOURCE_INHERIT);
                $processed += $memberIds->count();
            });

        return $processed;
    }

    public function applyGroupRollout(int $groupId, string $scope, array $memberIds = []): int
    {
        if (! $this->supportsPolicyTables()) {
            return 0;
        }

        $normalizedScope = $this->normalizeScope($scope);
        $targetIds = $this->resolveTargetMemberIds($normalizedScope, $memberIds);

        if ($targetIds->isEmpty()) {
            return 0;
        }

        $markets = $this->loadEnabledMarketsWithGroup(function (Builder $query) use ($groupId): void {
            $query->where('group_id', $groupId);
        });

        $this->syncPoliciesForMembers($targetIds, $markets, true, self::POLICY_SOURCE_ADMIN_ROLLOUT);

        return $targetIds->count();
    }

    public function applyMarketRollout(int $marketId, string $scope, array $memberIds = []): int
    {
        if (! $this->supportsPolicyTables()) {
            return 0;
        }

        $normalizedScope = $this->normalizeScope($scope);
        $targetIds = $this->resolveTargetMemberIds($normalizedScope, $memberIds);

        if ($targetIds->isEmpty()) {
            return 0;
        }

        $markets = $this->loadEnabledMarketsWithGroup(function (Builder $query) use ($marketId): void {
            $query->where('id', $marketId);
        });

        $this->syncPoliciesForMembers($targetIds, $markets, true, self::POLICY_SOURCE_ADMIN_ROLLOUT);

        return $targetIds->count();
    }

    public function supportsPolicyTables(): bool
    {
        return Schema::hasTable('member_lotto_market_policies')
            && Schema::hasTable('lotto_markets')
            && Schema::hasTable('lotto_groups');
    }

    public function isValidRolloutMode(string $mode): bool
    {
        return in_array($mode, self::VALID_ROLLOUT_MODES, true);
    }

    private function normalizeScope(string $scope): string
    {
        return $this->isSelectedScope($scope) ? self::ROLLOUT_SELECTED : self::ROLLOUT_ALL;
    }

    private function resolveTargetMemberIds(string $scope, array $memberIds): Collection
    {
        if ($this->isSelectedScope($scope)) {
            return collect($memberIds)
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->unique()
                ->values();
        }

        return Member::query()
            ->select('code')
            ->orderBy('code')
            ->pluck('code')
            ->map(static fn ($id): int => (int) $id)
            ->values();
    }

    private function loadEnabledMarketsWithGroup(?\Closure $callback = null): Collection
    {
        $query = LotteryMarket::query()
            ->with('group')
            ->where('is_enabled', true)
            ->whereHas('group', function ($builder): void {
                $builder->where('is_enabled', true);
            });

        if ($callback) {
            $callback($query);
        }

        return $query->get();
    }

    private function syncPoliciesForMembers(
        Collection $memberIds,
        Collection $markets,
        bool $forceAllow,
        string $source
    ): void {
        $rows = [];
        $timestamp = now();

        foreach ($markets as $market) {
            $group = $market->group;
            if (! $group) {
                continue;
            }

            $mode = $this->resolveRolloutMode((string) ($market->rollout_mode ?: $group->rollout_mode));
            $isAllowed = $forceAllow ? true : $this->isAllowedByMode($mode);
            $policyVersion = max((int) $group->policy_version, (int) $market->policy_version);

            foreach ($memberIds as $memberId) {
                $rows[] = [
                    'member_id' => (int) $memberId,
                    'group_id' => (int) $group->id,
                    'market_id' => (int) $market->id,
                    'is_allowed' => $isAllowed,
                    'source' => $source,
                    'policy_version' => $policyVersion,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('member_lotto_market_policies')->upsert(
                $chunk,
                ['member_id', 'market_id'],
                ['group_id', 'is_allowed', 'source', 'policy_version', 'updated_at']
            );
        }
    }

    private function resolveRolloutMode(string $mode): string
    {
        return in_array($mode, self::VALID_ROLLOUT_MODES, true)
            ? $mode
            : self::ROLLOUT_NEW_ONLY;
    }

    private function isAllowedByMode(string $mode): bool
    {
        return in_array($mode, [self::ROLLOUT_NEW_ONLY, self::ROLLOUT_ALL], true);
    }

    private function isSelectedScope(string $scope): bool
    {
        return $scope === self::ROLLOUT_SELECTED;
    }
}
