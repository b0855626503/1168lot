<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableCheckCaseBankCode extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasColumns('check_case', ['bank_code','expired_date'])) {
            Schema::table('check_case', function (Blueprint $table) {
                $table->timestamp('expired_date')->nullable();
                $table->unsignedInteger('bank_code')->nullable()->after('code');
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
