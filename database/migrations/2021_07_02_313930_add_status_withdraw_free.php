<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusWithdrawFree extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('withdraws_free', 'status_withdraw')) {
            Schema::table('withdraws_free', function (Blueprint $table) {
                $table->string('status_withdraw',1)->default('W');
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
        Schema::table('withdraws_free', function (Blueprint $table) {
            $table->dropColumn(['status_withdraw']);
        });
    }
}
