<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebsocketsStatisticsEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('configs', 'diamond_per_bill','diamonds_topup','diamonds_amount')) {
            Schema::table('configs', function (Blueprint $table) {
                $table->enum('diamond_per_bill', ['Y', 'N'])->default('N');
                $table->decimal('diamonds_topup', 10, 2)->default('0.00');
                $table->decimal('diamonds_amount', 10, 2)->default('0.00');
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
            $table->dropColumn(['diamond_per_bill', 'diamonds_topup', 'diamonds_amount']);
        });
    }
}
