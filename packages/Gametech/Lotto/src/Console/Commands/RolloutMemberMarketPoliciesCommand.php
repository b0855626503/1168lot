<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Services\MemberMarketPolicyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RolloutMemberMarketPoliciesCommand extends Command
{
    protected $signature = 'lotto:policy-rollout-markets
        {--market-ids= : Comma list or range, e.g. 61,62 or 61-105}
        {--group-id= : Lotto group id}
        {--scope=all : all|selected}
        {--member-ids= : Comma list member ids for selected scope}
        {--chunk=1000 : Number of members per batch}
        {--dry-run : Preview only, do not write}
        {--mode=missing-only : missing-only|resync}
        {--force-allow : Force allow selected rows when rolling out}';

    protected $description = 'Roll out member lotto market policies for selected markets with missing-only or resync mode';

    public function handle(MemberMarketPolicyService $policyService): int
    {
        if (! $policyService->supportsPolicyTables()) {
            $this->warn('Policy tables are not ready. Run migrations first.');

            return self::FAILURE;
        }

        $scope = (string) $this->option('scope');
        $mode = (string) $this->option('mode');
        $chunk = max(1, (int) $this->option('chunk'));
        $groupId = $this->option('group-id');
        $marketIdsOption = $this->option('market-ids');
        $memberIdsOption = $this->option('member-ids');

        if (! in_array($scope, [MemberMarketPolicyService::ROLLOUT_ALL, MemberMarketPolicyService::ROLLOUT_SELECTED], true)) {
            $this->error('Invalid --scope. Allowed: all, selected');

            return self::FAILURE;
        }

        if (! $policyService->isValidPolicyRolloutMode($mode)) {
            $this->error('Invalid --mode. Allowed: missing-only, resync');

            return self::FAILURE;
        }

        if (! empty($marketIdsOption) && ! empty($groupId)) {
            $this->error('Use either --market-ids or --group-id, not both.');

            return self::FAILURE;
        }

        if (empty($marketIdsOption) && empty($groupId)) {
            $this->error('You must provide at least one of --market-ids or --group-id.');

            return self::FAILURE;
        }

        $memberIds = $this->parseCommaSeparatedInts((string) $memberIdsOption);
        if ($scope === MemberMarketPolicyService::ROLLOUT_SELECTED && $memberIds === []) {
            $this->error('When --scope=selected, --member-ids is required.');

            return self::FAILURE;
        }

        $marketIds = [];
        if (! empty($marketIdsOption)) {
            try {
                $marketIds = $this->parseMarketIds((string) $marketIdsOption);
            } catch (\InvalidArgumentException $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            if ($marketIds === []) {
                $this->error('No valid market ids found from --market-ids.');

                return self::FAILURE;
            }
        } else {
            $groupIdInt = (int) $groupId;
            if ($groupIdInt <= 0) {
                $this->error('Invalid --group-id.');

                return self::FAILURE;
            }

            $groupExistsEnabled = DB::table('lotto_groups')
                ->where('id', $groupIdInt)
                ->where('is_enabled', true)
                ->exists();

            if (! $groupExistsEnabled) {
                $this->error('Group not found or disabled.');

                return self::FAILURE;
            }

            $marketIds = DB::table('lotto_markets as m')
                ->join('lotto_groups as g', 'g.id', '=', 'm.group_id')
                ->where('m.group_id', $groupIdInt)
                ->where('m.is_enabled', true)
                ->where('g.is_enabled', true)
                ->pluck('m.id')
                ->map(static fn ($id): int => (int) $id)
                ->values()
                ->all();

            if ($marketIds === []) {
                $this->error('No enabled markets found in the selected group.');

                return self::FAILURE;
            }
        }

        $eligibleMarketIds = DB::table('lotto_markets as m')
            ->join('lotto_groups as g', 'g.id', '=', 'm.group_id')
            ->whereIn('m.id', $marketIds)
            ->where('m.is_enabled', true)
            ->where('g.is_enabled', true)
            ->pluck('m.id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();

        $invalidIds = array_values(array_diff($marketIds, $eligibleMarketIds));
        if ($invalidIds !== []) {
            $this->error('Some markets are missing/disabled or belong to disabled group: '.implode(',', $invalidIds));

            return self::FAILURE;
        }

        $startedAt = microtime(true);

        $summary = $policyService->rolloutMarkets(
            $eligibleMarketIds,
            $scope,
            $memberIds,
            $mode,
            $chunk,
            (bool) $this->option('dry-run'),
            (bool) $this->option('force-allow')
        );

        $summary['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);

        $this->line('Policy rollout summary:');
        $this->line('mode='.$summary['mode']);
        $this->line('scope='.$summary['scope']);
        $this->line('market_count='.$summary['selected_markets_count']);
        $this->line('member_count='.$summary['selected_members_count']);
        $this->line('existing_policy_count='.$summary['existing_policy_count']);
        $this->line('missing_policy_count='.$summary['missing_policy_count']);
        $this->line('inserted_rows='.$summary['inserted_rows']);
        $this->line('skipped_existing_rows='.$summary['skipped_existing_rows']);
        $this->line('dry_run='.(($summary['dry_run'] ?? false) ? 'true' : 'false'));
        $this->line('duration_ms='.$summary['duration_ms']);

        return self::SUCCESS;
    }

    /**
     * @return array<int>
     */
    private function parseMarketIds(string $value): array
    {
        $tokens = array_filter(array_map('trim', explode(',', $value)), static fn ($part): bool => $part !== '');
        $ids = [];

        foreach ($tokens as $token) {
            if (preg_match('/^(\d+)-(\d+)$/', $token, $matches) === 1) {
                $start = (int) $matches[1];
                $end = (int) $matches[2];

                if ($start <= 0 || $end <= 0 || $end < $start) {
                    throw new \InvalidArgumentException('Invalid market range: '.$token);
                }

                foreach (range($start, $end) as $rangeId) {
                    $ids[] = (int) $rangeId;
                }

                continue;
            }

            if (! ctype_digit($token)) {
                throw new \InvalidArgumentException('Invalid market id token: '.$token);
            }

            $intToken = (int) $token;
            if ($intToken <= 0) {
                throw new \InvalidArgumentException('Invalid market id token: '.$token);
            }

            $ids[] = $intToken;
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<int>
     */
    private function parseCommaSeparatedInts(string $value): array
    {
        $tokens = array_filter(array_map('trim', explode(',', $value)), static fn ($part): bool => $part !== '');

        return collect($tokens)
            ->filter(static fn ($token): bool => ctype_digit($token))
            ->map(static fn ($token): int => (int) $token)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
