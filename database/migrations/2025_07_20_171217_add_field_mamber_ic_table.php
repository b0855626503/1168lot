<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldMamberIcTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumns('members_ic', ['sum_deposit','sum_withdraw','sum_balance'])) {
            Schema::table('members_ic', function (Blueprint $table) {
                $table->decimal('sum_deposit', 10)->default(0.00);
                $table->decimal('sum_withdraw', 10)->default(0.00);
                $table->decimal('sum_balance', 10)->default(0.00);
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
