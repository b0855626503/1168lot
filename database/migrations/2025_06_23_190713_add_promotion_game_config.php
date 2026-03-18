<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPromotionGameConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumns('promotions', ['lotto', 'keno', 'card', 'cock', 'poker'])) {
            Schema::table('promotions', function (Blueprint $table) {

                $table->enum('lotto', ['Y', 'N'])->default('N');
                $table->enum('keno', ['Y', 'N'])->default('N');
                $table->enum('card', ['Y', 'N'])->default('N');
                $table->enum('cock', ['Y', 'N'])->default('N');
                $table->enum('poker', ['Y', 'N'])->default('N');
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
