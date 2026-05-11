<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Member\Models\Member;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MemberMarketPolicyService
{
    public const ROLLOUT_NEW_ONLY = 'new_only';
    public const ROLLOUT_ALL = 'all';
    public const ROLLOUT_SELECTED = 'selected';

    public const ROLLOUT_MODE_MISSING_ONLY = 'missing-only';
    public const ROLLOUT_MODE_RESYNC = 'resync';

    private const POLICY_SOURCE_INHERIT = 'inherit';
    private const POLICY_SOURCE_ADMIN_ROLLOUT = 'admin_rollout';

    private const VALID_ROLLOUT_MODES = [
        self::ROLLOUT_NEW_ONLY,
        self::ROLLOUT_ALL,
        self::ROLLOUT_SELECTED,
    ];

    private const VALID_POLICY_ROLLOUT_MODES = [
        self::ROLLOUT_MODE_MISSING_ONLY,
        self::ROLLOUT_MODE_RESYNC,
    ];

    public function bootstrapForMember(int $memberId): void
    {
        Log::warning('MemberMarketPolicyService: bootstrapForMember is disabled under blacklist model.', [
            'member_id' => $memberId,
        ]);
    }

    public function bootstrapAllMembers(int $chunkSize = 500): int
    {
        Log::warning('MemberMarketPolicyService: bootstrapAllMembers is disabled under blacklist model.');

        return 0;
    }

    public function applyGroupRollout(int $groupId, string $scope, array $memberIds = []): int
    {
        Log::warning('MemberMarketPolicyService: applyGroupRollout is disabled under blacklist model.', [
            'group_id' => $groupId,
            'scope' => $scope,
        ]);

        return 0;
    }

    public function applyMarketRollout(int $marketId, string $scope, array $memberIds = []): int
    {
        Log::warning('MemberMarketPolicyService: applyMarketRollout is disabled under blacklist model.', [
            'market_id' => $marketId,
            'scope' => $scope,
        ]);

        return 0;
    }

    public function rolloutMarkets(
        array $marketIds,
        string $scope,
        array $memberIds = [],
        string $mode = self::ROLLOUT_MODE_MISSING_ONLY,
        int $chunkSize = 1000,
        bool $dryRun = false,
        bool $forceAllow = false
    ): array {
        Log::warning('MemberMarketPolicyService: rolloutMarkets is disabled under blacklist model.', [
            'market_ids' => $marketIds,
            'scope' => $scope,
            'mode' => $mode,
            'dry_run' => $dryRun,
        ]);

        return [
            'mode' => $this->normalizePolicyRolloutMode($mode),
            'scope' => $this->normalizeScope($scope),
            'dry_run' => $dryRun,
            'selected_markets_count' => 0,
            'selected_members_count' => 0,
            'existing_policy_count' => 0,
            'missing_policy_count' => 0,
            'estimated_insert_rows' => 0,
            'inserted_rows' => 0,
            'skipped_existing_rows' => 0,
        ];
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

    public function isValidPolicyRolloutMode(string $mode): bool
    {
        return in_array($mode, self::VALID_POLICY_ROLLOUT_MODES, true);
    }

    private function normalizeScope(string $scope): string
    {
        return $this->isSelectedScope($scope) ? self::ROLLOUT_SELECTED : self::ROLLOUT_ALL;
    }

    private function normalizePolicyRolloutMode(string $mode): string
    {
        return $this->isValidPolicyRolloutMode($mode) ? $mode : self::ROLLOUT_MODE_MISSING_ONLY;
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

    private function processMemberChunks(string $scope, Collection $resolvedMemberIds, int $chunkSize, callable $callback): void
    {
        if ($this->isSelectedScope($scope)) {
            $resolvedMemberIds
                ->chunk($chunkSize)
                ->each(function (Collection $memberChunk) use ($callback): void {
                    $callback($memberChunk->values());
                });

            return;
        }

        Member::query()
            ->select('code')
            ->orderBy('code')
            ->chunk($chunkSize, function (Collection $members) use ($callback): void {
                $memberIds = $members->pluck('code')
                    ->map(static fn ($id): int => (int) $id)
                    ->values();

                if ($memberIds->isEmpty()) {
                    return;
                }

                $callback($memberIds);
            });
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

    private function buildPolicyRows(Collection $memberIds, Collection $markets, bool $forceAllow, string $source): array
    {
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

        return $rows;
    }

    private function syncPoliciesForMembers(
        Collection $memberIds,
        Collection $markets,
        bool $forceAllow,
        string $source
    ): void {
        $rows = $this->buildPolicyRows($memberIds, $markets, $forceAllow, $source);

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('member_lotto_market_policies')->upsert(
                $chunk,
                ['member_id', 'market_id'],
                ['group_id', 'is_allowed', 'source', 'policy_version', 'updated_at']
            );
        }
    }

    private function countExistingPolicies(array $marketIds, string $scope, Collection $memberIds): int
    {
        $query = DB::table('member_lotto_market_policies')
            ->whereIn('market_id', $marketIds);

        if ($this->isSelectedScope($scope)) {
            if ($memberIds->isEmpty()) {
                return 0;
            }

            $query->whereIn('member_id', $memberIds->all());
        }

        return (int) $query->count();
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
