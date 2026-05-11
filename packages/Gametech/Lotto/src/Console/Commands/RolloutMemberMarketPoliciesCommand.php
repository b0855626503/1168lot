<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Services\MemberMarketPolicyService;
use Illuminate\Console\Command;

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
        $this->warn('This command is deprecated under the blacklist model.');
        $this->info('The system now operates as default-allow: no policy row means the member can bet.');
        $this->info('Only explicit is_allowed=false rows block betting. Mass policy rollout is no longer needed.');
        $this->info('Use the admin MemberLottoPermission panel to explicitly block individual members.');

        return self::SUCCESS;
    }
}
