<?php

namespace Gametech\Lotto\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateLegacyLottoPermissionsCommand extends Command
{
    protected $signature = 'lotto:migrate-legacy-permissions {--dry-run : Preview without writing}';

    protected $description = 'Migrate member_lotto_permissions into member_lotto_market_policies';

    public function handle(): int
    {
        $this->warn('This command is deprecated. Under the blacklist model, only is_allowed=false rows block betting.');
        $this->info('Legacy allow rows (is_allowed=true) are no-ops and no longer need migration.');
        $this->info('Only legacy deny rows (is_allowed=false) remain meaningful.');

        $hasLegacyTable = Schema::hasTable('member_lotto_permissions');
        $hasPolicyTable = Schema::hasTable('member_lotto_market_policies');

        if (! $hasPolicyTable) {
            $this->error('member_lotto_market_policies table is missing. Run lotto migrations first.');

            return self::FAILURE;
        }

        if (! $hasLegacyTable) {
            $this->info('Legacy table member_lotto_permissions does not exist. Nothing to migrate.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $legacyRows = DB::table('member_lotto_permissions')->orderBy('id')->get();

        // Under blacklist model, only is_allowed=false rows are meaningful.
        // Legacy allow rows (is_allowed=true) are no-ops and must not be migrated.
        $denyRows = $legacyRows->filter(fn ($row): bool => ! (bool) $row->is_allowed);
        $skippedAllowRows = $legacyRows->count() - $denyRows->count();

        if ($denyRows->isEmpty()) {
            $this->info('No legacy deny rows to migrate.'.($skippedAllowRows > 0 ? " Skipped {$skippedAllowRows} legacy allow rows (no-ops under blacklist)." : ''));

            return self::SUCCESS;
        }

        $markets = DB::table('lotto_markets')
            ->select('id', 'group_id')
            ->where('is_enabled', true)
            ->get()
            ->groupBy('group_id');

        $allMarkets = DB::table('lotto_markets')
            ->select('id', 'group_id')
            ->where('is_enabled', true)
            ->get();

        $upsertPayloads = [];

        foreach ($denyRows as $row) {
            $targetMarkets = is_null($row->group_id)
                ? $allMarkets
                : ($markets->get((int) $row->group_id) ?? collect());

            foreach ($targetMarkets as $market) {
                $upsertPayloads[] = [
                    'member_id' => (int) $row->member_id,
                    'market_id' => (int) $market->id,
                    'group_id' => (int) $market->group_id,
                    'is_allowed' => false,
                    'source' => 'legacy_permission_migration',
                    'policy_version' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            }
        }

        if (! $dryRun && ! empty($upsertPayloads)) {
            DB::table('member_lotto_market_policies')->upsert(
                $upsertPayloads,
                ['member_id', 'market_id'],
                ['group_id', 'is_allowed', 'source', 'policy_version', 'updated_at']
            );
        }

        $this->info('Legacy rows: '.$legacyRows->count());
        $this->info('Legacy deny rows '.($dryRun ? 'to migrate (dry-run)' : 'migrated').': '.$denyRows->count());
        $this->info('Legacy allow rows skipped: '.$skippedAllowRows.' (no-ops under blacklist)');
        $this->info('Target policy rows: '.count($upsertPayloads));
        $this->info('Dry-run: '.($dryRun ? 'yes' : 'no'));

        return self::SUCCESS;
    }
}
