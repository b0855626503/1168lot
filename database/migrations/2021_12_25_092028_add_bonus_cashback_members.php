<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBonusCashbackMembers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumns('members', ['bonus','cashback','faststart','ic'])) {
            Schema::table('members', function (Blueprint $table) {
                $table->decimal('bonus', 10, 2)->default('0.00');
                $table->decimal('cashback', 10, 2)->default('0.00');
                $table->decimal('faststart', 10, 2)->default('0.00');
                $table->decimal('ic', 10, 2)->default('0.00');

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
