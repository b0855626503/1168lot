<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOptionConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasColumns('configs', ['point_per_bill','points_amount','points_topup'])) {

            Schema::table('configs', function (Blueprint $table) {
                $table->enum('point_per_bill', ['Y', 'N'])->default('N');
                $table->decimal('points_amount', 10)->default(0.00);
                $table->decimal('points_topup', 10)->default(0.00);
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
