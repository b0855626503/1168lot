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
        $this->warn('This command is deprecated under the blacklist model.');
        $this->info('The system now operates as default-allow: no policy row means the member can bet.');
        $this->info('Only explicit is_allowed=false rows block betting. Mass allow-row creation is no longer needed.');

        return self::SUCCESS;
    }
}
