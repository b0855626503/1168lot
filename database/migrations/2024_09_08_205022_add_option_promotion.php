<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOptionPromotion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasColumns('promotions', ['slot','casino','sport','huay'])) {

            Schema::table('promotions', function (Blueprint $table) {
                $table->enum('slot', ['Y', 'N'])->default('N');
                $table->enum('casino', ['Y', 'N'])->default('N');
                $table->enum('sport', ['Y', 'N'])->default('N');
                $table->enum('huay', ['Y', 'N'])->default('N');
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
