<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Services\MemberMarketPolicyService;
use Illuminate\Console\Command;

class BootstrapMemberMarketPoliciesCommand extends Command
{
    protected $signature = 'lotto:policy-bootstrap-members {--chunk=500 : Number of members per batch}';

    protected $description = 'Bootstrap lotto market policy rows for existing members';

    public function handle(MemberMarketPolicyService $policyService): int
    {
        if (! $policyService->supportsPolicyTables()) {
            $this->warn('Policy tables are not ready. Run migrations first.');

            return self::FAILURE;
        }

        $chunk = max(50, (int) $this->option('chunk'));
        $count = $policyService->bootstrapAllMembers($chunk);

        $this->info("Bootstrap completed for {$count} members.");

        return self::SUCCESS;
    }
}

