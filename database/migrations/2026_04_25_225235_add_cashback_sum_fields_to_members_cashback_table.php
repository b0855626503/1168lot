<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumns('members_cashback', ['sum_deposit', 'sum_withdraw', 'sum_balance'])) {
            Schema::table('members_cashback', function (Blueprint $table): void {
                $table->decimal('sum_deposit', 10, 2)->default(0.00);
                $table->decimal('sum_withdraw', 10, 2)->default(0.00);
                $table->decimal('sum_balance', 10, 2)->default(0.00);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumns('members_cashback', ['sum_deposit', 'sum_withdraw', 'sum_balance'])) {
            Schema::table('members_cashback', function (Blueprint $table): void {
                $table->dropColumn(['sum_deposit', 'sum_withdraw', 'sum_balance']);
            });
        }
    }
};
