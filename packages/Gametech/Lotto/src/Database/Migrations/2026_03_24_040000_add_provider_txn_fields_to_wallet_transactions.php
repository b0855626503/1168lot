<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table = 'wallet_transactions';

    public function up(): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            if (! Schema::hasColumn('wallet_transactions', 'provider_txn_id')) {
                $table->string('provider_txn_id', 64)->nullable()->after('ref_code');
                $table->index(['provider_txn_id'], 'wallet_txn_provider_txn_idx');
            }

            if (! Schema::hasColumn('wallet_transactions', 'provider_round_id')) {
                $table->string('provider_round_id', 128)->nullable()->after('provider_txn_id');
                $table->index(['provider_round_id'], 'wallet_txn_provider_round_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            if (Schema::hasColumn('wallet_transactions', 'provider_round_id')) {
                $table->dropIndex('wallet_txn_provider_round_idx');
                $table->dropColumn('provider_round_id');
            }

            if (Schema::hasColumn('wallet_transactions', 'provider_txn_id')) {
                $table->dropIndex('wallet_txn_provider_txn_idx');
                $table->dropColumn('provider_txn_id');
            }
        });
    }
};

