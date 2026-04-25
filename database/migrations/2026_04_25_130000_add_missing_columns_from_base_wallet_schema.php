<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->missingColumns() as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column => $definition) {
                if (Schema::hasColumn($table, $column)) {
                    continue;
                }

                $after = $definition['after'] ?? null;
                $afterSql = is_string($after) && Schema::hasColumn($table, $after)
                    ? ' AFTER `'.$after.'`'
                    : '';

                DB::statement(sprintf(
                    'ALTER TABLE `%s` ADD COLUMN `%s` %s%s',
                    $table,
                    $column,
                    $definition['sql'],
                    $afterSql
                ));
            }
        }
    }

    /**
     * @return array<string, array<string, array{sql: string, after?: string}>>
     */
    private function missingColumns(): array
    {
        return [
            'banks_account' => [
                'bank_code' => [
                    'sql' => 'INT(10) UNSIGNED DEFAULT NULL',
                    'after' => 'end_at',
                ],
                'rate_auto' => [
                    'sql' => "ENUM('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N'",
                    'after' => 'rate_update',
                ],
                'expired_date' => [
                    'sql' => 'TIMESTAMP NULL DEFAULT NULL',
                    'after' => 'slip',
                ],
                'bonus_max' => [
                    'sql' => 'DECIMAL(10,2) NOT NULL DEFAULT 0.00',
                    'after' => 'bonus',
                ],
                'visibility_scope' => [
                    'sql' => 'VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL',
                    'after' => 'remark',
                ],
            ],
            'bills' => [
                'pro_name' => [
                    'sql' => 'VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL',
                    'after' => 'pro_code',
                ],
                'method' => [
                    'sql' => 'VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL',
                    'after' => 'amount_limit',
                ],
                'refer_code' => [
                    'sql' => 'INT(11) NOT NULL DEFAULT 0',
                    'after' => 'method',
                ],
                'refer_table' => [
                    'sql' => 'VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL',
                    'after' => 'refer_code',
                ],
                'complete' => [
                    'sql' => "ENUM('Y','N','R') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N'",
                    'after' => 'refer_table',
                ],
            ],
            'configs' => [
                'withdraw_status' => [
                    'sql' => "ENUM('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Y'",
                    'after' => 'qrscan',
                ],
            ],
            'games' => [
                'token' => [
                    'sql' => 'MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL',
                    'after' => 'newuser',
                ],
                'token_expired' => [
                    'sql' => 'TIMESTAMP NULL DEFAULT NULL',
                    'after' => 'token',
                ],
            ],
            'lotto_markets' => [
                'draw_schedule_type' => [
                    'sql' => 'VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL',
                    'after' => 'draw_mode',
                ],
                'draw_days' => [
                    'sql' => 'LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL',
                    'after' => 'draw_schedule_type',
                ],
                'draw_dates' => [
                    'sql' => 'LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL',
                    'after' => 'draw_days',
                ],
            ],
            'members' => [
                'lastname_addon' => [
                    'sql' => 'VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL',
                    'after' => 'firstname_addon',
                ],
                'maxwithdraw_day' => [
                    'sql' => 'DECIMAL(10,2) NOT NULL DEFAULT 0.00',
                    'after' => 'ic',
                ],
                'game_user' => [
                    'sql' => 'VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL',
                    'after' => 'maxwithdraw_day',
                ],
            ],
            'members_log' => [
                'username' => [
                    'sql' => 'VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL',
                    'after' => 'date_update',
                ],
                'password' => [
                    'sql' => 'VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL',
                    'after' => 'username',
                ],
                'username_real' => [
                    'sql' => 'VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL',
                    'after' => 'password',
                ],
                'password_real' => [
                    'sql' => 'VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL',
                    'after' => 'username_real',
                ],
                'summary' => [
                    'sql' => 'MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL',
                    'after' => 'password_real',
                ],
            ],
            'promotions' => [
                'fish' => [
                    'sql' => "ENUM('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N'",
                    'after' => 'poker',
                ],
            ],
            'withdraws' => [
                'amount_balance' => [
                    'sql' => 'DECIMAL(10,2) NOT NULL DEFAULT 0.00',
                    'after' => 'balance',
                ],
                'amount_limit' => [
                    'sql' => 'DECIMAL(10,2) NOT NULL DEFAULT 0.00',
                    'after' => 'amount_balance',
                ],
                'amount_limit_rate' => [
                    'sql' => 'DECIMAL(10,2) NOT NULL DEFAULT 0.00',
                    'after' => 'amount_limit',
                ],
                'transaction_id' => [
                    'sql' => 'VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL',
                    'after' => 'txid',
                ],
                'pro_code' => [
                    'sql' => 'INT(11) NOT NULL DEFAULT 0',
                    'after' => 'transaction_id',
                ],
                'pro_name' => [
                    'sql' => 'VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL',
                    'after' => 'pro_code',
                ],
            ],
        ];
    }

    public function down(): void {}
};
