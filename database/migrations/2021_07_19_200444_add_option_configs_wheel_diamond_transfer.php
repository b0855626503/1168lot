<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOptionConfigsWheelDiamondTransfer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('configs', 'wheel_open')) {
            Schema::table('configs', function (Blueprint $table) {
                $table->enum('wheel_open', ['Y', 'N'])->default('Y');
            });
        }

        if (!Schema::hasColumn('configs', 'diamond_in_game')) {
            Schema::table('configs', function (Blueprint $table) {
                $table->enum('diamond_in_game', ['Y', 'N'])->default('N');
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
        Schema::table('configs', function (Blueprint $table) {
            $table->dropColumn(['wheel_open','diamond_in_game']);
        });
    }
}
