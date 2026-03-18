<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBankAccountAuto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumns('banks_account', ['min_amount','max_amount','auto_transfer'])) {
            Schema::table('banks_account', function (Blueprint $table) {
                $table->enum('auto_transfer',['Y','N'])->default('N');
                $table->decimal('min_amount', 10)->default(0.00);
                $table->decimal('max_amount', 10)->default(0.00);
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
