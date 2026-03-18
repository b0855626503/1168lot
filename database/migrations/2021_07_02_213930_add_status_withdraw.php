<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusWithdraw extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('withdraws', 'status_withdraw')) {
            Schema::table('withdraws', function (Blueprint $table) {
                $table->string('status_withdraw',1)->default('W');
            });
        }

        if (!Schema::hasColumn('banks_account', 'local')) {
            Schema::table('banks_account', function (Blueprint $table) {
                $table->enum('local', ['Y', 'N'])->default('Y');
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
        Schema::table('withdraws', function (Blueprint $table) {
            $table->dropColumn(['status_withdraw']);
        });

        Schema::table('banks_account', function (Blueprint $table) {
            $table->dropColumn(['local']);
        });
    }
}
