<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('yeekee_shoot_reward_logs')) {
            return;
        }

        Schema::table('yeekee_shoot_reward_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('yeekee_shoot_reward_logs', 'lotto_draw_id')) {
                $table->unsignedBigInteger('lotto_draw_id')->nullable()->after('yeekee_round_id');
            }

            if (! Schema::hasColumn('yeekee_shoot_reward_logs', 'market_id')) {
                $table->unsignedBigInteger('market_id')->nullable()->after('lotto_draw_id');
            }

            if (! Schema::hasColumn('yeekee_shoot_reward_logs', 'yeekee_shoot_id')) {
                $table->unsignedBigInteger('yeekee_shoot_id')->nullable()->after('market_id');
            }

            if (! Schema::hasColumn('yeekee_shoot_reward_logs', 'currency')) {
                $table->string('currency', 8)->default('THB')->after('credit_amount');
            }

            if (! Schema::hasColumn('yeekee_shoot_reward_logs', 'status')) {
                $table->string('status', 32)->default('paid')->after('currency');
            }

            if (! Schema::hasColumn('yeekee_shoot_reward_logs', 'reason')) {
                $table->string('reason', 128)->nullable()->after('status');
            }

            if (! Schema::hasColumn('yeekee_shoot_reward_logs', 'policy_source')) {
                $table->string('policy_source', 32)->nullable()->after('reason');
            }

            if (! Schema::hasColumn('yeekee_shoot_reward_logs', 'policy_hash')) {
                $table->string('policy_hash', 64)->nullable()->after('policy_source');
            }

            if (! Schema::hasColumn('yeekee_shoot_reward_logs', 'wallet_transaction_id')) {
                $table->unsignedBigInteger('wallet_transaction_id')->nullable()->after('idempotency_key');
            }

            if (! Schema::hasColumn('yeekee_shoot_reward_logs', 'paid_at')) {
                $table->dateTime('paid_at')->nullable()->after('wallet_transaction_id');
            }

            if (! Schema::hasColumn('yeekee_shoot_reward_logs', 'metadata_json')) {
                $table->json('metadata_json')->nullable()->after('paid_at');
            }
        });

        Schema::table('yeekee_shoot_reward_logs', function (Blueprint $table): void {
            if (! $this->hasIndex('yeekee_shoot_reward_logs', 'yeekee_reward_scope_day_idx')) {
                $table->index(['member_id', 'market_id', 'created_at'], 'yeekee_reward_scope_day_idx');
            }

            if (! $this->hasIndex('yeekee_shoot_reward_logs', 'yeekee_reward_status_created_idx')) {
                $table->index(['status', 'created_at'], 'yeekee_reward_status_created_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('yeekee_shoot_reward_logs')) {
            return;
        }

        Schema::table('yeekee_shoot_reward_logs', function (Blueprint $table): void {
            if ($this->hasIndex('yeekee_shoot_reward_logs', 'yeekee_reward_scope_day_idx')) {
                $table->dropIndex('yeekee_reward_scope_day_idx');
            }

            if ($this->hasIndex('yeekee_shoot_reward_logs', 'yeekee_reward_status_created_idx')) {
                $table->dropIndex('yeekee_reward_status_created_idx');
            }
        });

        Schema::table('yeekee_shoot_reward_logs', function (Blueprint $table): void {
            foreach ([
                'metadata_json',
                'paid_at',
                'wallet_transaction_id',
                'policy_hash',
                'policy_source',
                'reason',
                'status',
                'currency',
                'yeekee_shoot_id',
                'market_id',
                'lotto_draw_id',
            ] as $column) {
                if (Schema::hasColumn('yeekee_shoot_reward_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return false;
        }

        $database = DB::getDatabaseName();
        if (! $database) {
            return false;
        }

        $row = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->first();

        return $row !== null;
    }
};
