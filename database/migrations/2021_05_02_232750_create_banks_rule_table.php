<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBanksRuleTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('banks_rule')) {
            Schema::create('banks_rule', function (Blueprint $table) {
                $table->integer('code', true);
                $table->enum('types', array('IF', 'IFNOT'))->default('IF');
                $table->integer('bank_code')->nullable();
                $table->enum('method', array('CAN', 'CANNOT'))->default('CAN');
                $table->text('bank_number');
                $table->string('user_create', 100);
                $table->string('user_update', 100);
                $table->timestamp('date_create', 0)->nullable(true);
                $table->timestamp('date_update', 0)->nullable(true);
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
        Schema::drop('banks_rule');
    }

}
