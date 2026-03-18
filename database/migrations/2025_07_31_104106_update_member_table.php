<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMemberTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumns('members', ['sum_deposit','sum_withdraw'])) {
            Schema::table('members', function (Blueprint $table) {
                $table->decimal('sum_deposit', 10,2)->default(0.00);
                $table->decimal('sum_withdraw', 10,2)->default(0.00);

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
