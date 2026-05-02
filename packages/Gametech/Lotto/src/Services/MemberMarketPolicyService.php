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

    public function rolloutMarkets(
        array $marketIds,
        string $scope,
        array $memberIds = [],
        string $mode = self::ROLLOUT_MODE_MISSING_ONLY,
        int $chunkSize = 1000,
        bool $dryRun = false,
        bool $forceAllow = false
    ): array {
        $normalizedScope = $this->normalizeScope($scope);
        $normalizedMode = $this->normalizePolicyRolloutMode($mode);
        $chunkSize = max(1, $chunkSize);

        $markets = $this->loadEnabledMarketsWithGroup(function (Builder $query) use ($marketIds): void {
            $query->whereIn('id', $marketIds);
        })->values();

        $marketIds = $markets->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();

        if ($marketIds === []) {
            return [
                'mode' => $normalizedMode,
                'scope' => $normalizedScope,
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

        $resolvedMemberIds = $this->resolveTargetMemberIds($normalizedScope, $memberIds);
        $selectedMembersCount = $normalizedScope === self::ROLLOUT_SELECTED
            ? $resolvedMemberIds->count()
            : (int) Member::query()->count();

        $existingPolicyCount = $this->countExistingPolicies($marketIds, $normalizedScope, $resolvedMemberIds);
        $possiblePairs = $selectedMembersCount * count($marketIds);
        $missingPolicyCount = max($possiblePairs - $existingPolicyCount, 0);

        $summary = [
            'mode' => $normalizedMode,
            'scope' => $normalizedScope,
            'dry_run' => $dryRun,
            'selected_markets_count' => count($marketIds),
            'selected_members_count' => $selectedMembersCount,
            'existing_policy_count' => $existingPolicyCount,
            'missing_policy_count' => $missingPolicyCount,
            'estimated_insert_rows' => $missingPolicyCount,
            'inserted_rows' => 0,
            'skipped_existing_rows' => 0,
        ];

        if ($dryRun || $selectedMembersCount === 0) {
            return $summary;
        }

        if ($normalizedMode === self::ROLLOUT_MODE_RESYNC) {
            $this->processMemberChunks($normalizedScope, $resolvedMemberIds, $chunkSize, function (Collection $chunkMemberIds) use (&$summary, $markets, $forceAllow): void {
                $this->syncPoliciesForMembers($chunkMemberIds, $markets, $forceAllow, self::POLICY_SOURCE_INHERIT);
                $summary['inserted_rows'] += $chunkMemberIds->count() * $markets->count();
            });

            $summary['skipped_existing_rows'] = 0;

            return $summary;
        }

        $this->processMemberChunks($normalizedScope, $resolvedMemberIds, $chunkSize, function (Collection $chunkMemberIds) use (&$summary, $markets, $marketIds, $forceAllow): void {
            $existingInChunk = $this->countExistingPolicies($marketIds, self::ROLLOUT_SELECTED, $chunkMemberIds);
            $summary['skipped_existing_rows'] += $existingInChunk;

            $rows = $this->buildPolicyRows($chunkMemberIds, $markets, $forceAllow, self::POLICY_SOURCE_INHERIT);
            if ($rows === []) {
                return;
            }

            foreach (array_chunk($rows, 1000) as $rowChunk) {
                $summary['inserted_rows'] += DB::table('member_lotto_market_policies')->insertOrIgnore($rowChunk);
            }
        });

        return $summary;
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
