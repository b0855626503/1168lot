<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMemberCreditFreeLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumns('members_credit_free_log', ['amount_balance','withdraw_limit','withdraw_limit_amount'])) {
            Schema::table('members_credit_free_log', function (Blueprint $table) {
                $table->decimal('amount_balance', 10, 2)->default('0.00');
                $table->decimal('withdraw_limit', 10, 2)->default('0.00');
                $table->decimal('withdraw_limit_amount', 10, 2)->default('0.00');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('members_credit_free_log', function (Blueprint $table) {
            $table->dropColumn(['amount_balance']);
            $table->dropColumn(['withdraw_limit']);
            $table->dropColumn(['withdraw_limit_amount']);
        });
    }
}
