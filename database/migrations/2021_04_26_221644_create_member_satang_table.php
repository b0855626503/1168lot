<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemberSatangTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('member_satang')) {
            Schema::create('member_satang', function (Blueprint $table) {
                $table->increments('code');
                $table->integer('member_code');
                $table->integer('bank_code');
                $table->string('shortcode', 10);
                $table->integer('value')->nullable();
                $table->index(['shortcode', 'bank_code'], 'memberbank');
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
        Schema::drop('member_satang');
    }

}
