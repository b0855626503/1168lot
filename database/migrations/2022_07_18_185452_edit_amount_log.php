<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditAmountLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('members_credit_log', function (Blueprint $table) {
            $table->decimal('amount', $precision = 10, $scale = 2)->default(0.00)->change();
        });

        Schema::table('members_credit_free_log', function (Blueprint $table) {
            $table->decimal('amount', $precision = 10, $scale = 2)->default(0.00)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
