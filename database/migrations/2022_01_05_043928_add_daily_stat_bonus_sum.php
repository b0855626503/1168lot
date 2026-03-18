<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDailyStatBonusSum extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('daily_stat', 'bonus_sum')) {
            Schema::table('daily_stat', function (Blueprint $table) {
                $table->decimal('bonus_sum',10,2)->default(0.00);
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
        Schema::table('daily_stat', function (Blueprint $table) {
            $table->dropColumn(['bonus_sum']);
        });
    }
}
