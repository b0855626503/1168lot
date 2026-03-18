<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNorefillCoupon extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasColumns('coupons', ['norefill','newuser'])) {

            Schema::table('coupons', function (Blueprint $table) {
                $table->enum('norefill', ['Y', 'N'])->default('N');
                $table->enum('newuser', ['Y', 'N'])->default('N');
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
