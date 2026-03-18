<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBalanceMemberPromotionlog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumns('members_promotionlog', ['balance','total_amount_balance'])) {
            Schema::table('members_promotionlog', function (Blueprint $table) {
                $table->decimal('balance', 10, 2);
                $table->decimal('total_amount_balance', 10, 2);
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
        Schema::table('members_promotionlog', function (Blueprint $table) {
            $table->dropColumn(['balance']);
            $table->dropColumn(['total_amount_balance']);
        });
    }
}
