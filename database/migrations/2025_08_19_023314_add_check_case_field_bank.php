<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCheckCaseFieldBank extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumns('check_case', ['bankAccountNumber','bankAccountName','bankName','promptpayNumber'])) {
            Schema::table('check_case', function (Blueprint $table) {
                $table->string('bankAccountNumber',10)->nullable();
                $table->string('bankAccountName',50)->nullable();
                $table->string('bankName',10)->nullable();
                $table->string('promptpayNumber',20)->nullable();

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
