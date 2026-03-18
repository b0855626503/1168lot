<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateConfigsAddtime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('configs', 'cashback_time')) {
            Schema::table('configs', function (Blueprint $table) {
                $table->char('cashback_time', 5)->default('00:30');
            });
        }

        if (!Schema::hasColumn('configs', 'ic_time')) {
            Schema::table('configs', function (Blueprint $table) {
                $table->char('ic_time', 5)->default('00:50');
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
            $table->dropColumn(['cashback_time','ic_time']);
        });
    }
}
