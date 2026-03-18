<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // members
        $this->addIndexIfPossible('members', 'idx_members_date_regis', ['date_regis']);
        $this->addIndexIfPossible('members', 'idx_members_date_regis_campaign', ['date_regis', 'campaign_id']);
        $this->addIndexIfPossible('members', 'idx_members_date_regis_upline', ['date_regis', 'upline_code']);

        // bank_payment
        $this->addIndexIfPossible('bank_payment', 'idx_bp_date_status_enable', ['date_create', 'status', 'enable']);
        $this->addIndexIfPossible('bank_payment', 'idx_bp_member_status_enable_date', ['member_topup', 'status', 'enable', 'date_create']);
        $this->addIndexIfPossible('bank_payment', 'idx_bp_status_enable_date', ['status', 'enable', 'date_create']);

        // withdraws
        $this->addIndexIfPossible('withdraws', 'idx_wd_status_enable_approve', ['status', 'enable', 'date_approve']);
        $this->addIndexIfPossible('withdraws', 'idx_wd_status_enable_create', ['status', 'enable', 'date_create']);

        // withdraws_seamless
        $this->addIndexIfPossible('withdraws_seamless', 'idx_wds_status_enable_approve', ['status', 'enable', 'date_approve']);
        $this->addIndexIfPossible('withdraws_seamless', 'idx_wds_status_enable_create', ['status', 'enable', 'date_create']);

        // payments_promotion
        $this->addIndexIfPossible('payments_promotion', 'idx_pp_pro_enable_date', ['pro_code', 'enable', 'date_create']);

        // bills
        $this->addIndexIfPossible('bills', 'idx_bills_enable_transfer_date_pro', ['enable', 'transfer_type', 'date_create', 'pro_code']);
        $this->addIndexIfPossible('bills', 'idx_bills_enable_date', ['enable', 'date_create']);

        // members_credit_log
        $this->addIndexIfPossible('members_credit_log', 'idx_mcl_kind_credit_type_date', ['kind', 'credit_type', 'date_create']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('members', 'idx_members_date_regis');
        $this->dropIndexIfExists('members', 'idx_members_date_regis_campaign');
        $this->dropIndexIfExists('members', 'idx_members_date_regis_upline');

        $this->dropIndexIfExists('bank_payment', 'idx_bp_date_status_enable');
        $this->dropIndexIfExists('bank_payment', 'idx_bp_member_status_enable_date');
        $this->dropIndexIfExists('bank_payment', 'idx_bp_status_enable_date');

        $this->dropIndexIfExists('withdraws', 'idx_wd_status_enable_approve');
        $this->dropIndexIfExists('withdraws', 'idx_wd_status_enable_create');

        $this->dropIndexIfExists('withdraws_seamless', 'idx_wds_status_enable_approve');
        $this->dropIndexIfExists('withdraws_seamless', 'idx_wds_status_enable_create');

        $this->dropIndexIfExists('payments_promotion', 'idx_pp_pro_enable_date');

        $this->dropIndexIfExists('bills', 'idx_bills_enable_transfer_date_pro');
        $this->dropIndexIfExists('bills', 'idx_bills_enable_date');

        $this->dropIndexIfExists('members_credit_log', 'idx_mcl_kind_credit_type_date');
    }

    private function addIndexIfPossible(string $table, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
        }

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD INDEX `%s` (%s)',
            $table,
            $indexName,
            $this->quoteColumns($columns)
        ));
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $indexName)) {
            return;
        }

        DB::statement(sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $table, $indexName));
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::connection()->getDatabaseName();

        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();

        return (bool) $exists;
    }

    private function quoteColumns(array $columns): string
    {
        return implode(', ', array_map(fn (string $column) => "`{$column}`", $columns));
    }
};
