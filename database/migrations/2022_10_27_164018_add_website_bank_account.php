<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebsiteBankAccount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumns('banks_account', ['website','pattern'])) {
            Schema::table('banks_account', function (Blueprint $table) {
                $table->enum('pattern',['G','O'])->default('G');
                $table->string('website')->default('http://sv2.168gametech.com');
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
