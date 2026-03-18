<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableFailedRequestsIdRoundid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumns('failed_requests', ['txid','roundId'])) {
            Schema::table('failed_requests', function (Blueprint $table) {
                $table->string('txid',100)->nullable();
                $table->string('roundId',100)->nullable();
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
