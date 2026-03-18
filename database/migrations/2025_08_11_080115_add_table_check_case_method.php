<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableCheckCaseMethod extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('check_case', 'method')) {
            Schema::table('check_case', function (Blueprint $table) {

                $table->tinyInteger('method')->default(1)->unsigned()->comment('1=Deposit, 2=Withdraw');

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
