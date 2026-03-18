<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBackAccountRate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumns('banks_account', ['rate','rate_update'])) {

            Schema::table('banks_account', function (Blueprint $table) {
                $table->decimal('rate', 10)->default(0.00);
                $table->timestamp('rate_update', $precision = 0);
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
        //
    }
}
