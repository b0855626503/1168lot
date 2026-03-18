<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDurationToFailedRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('failed_requests', 'duration')) {
            Schema::table('failed_requests', function (Blueprint $table) {

                $table->float('duration')->nullable()->after('response');
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
        Schema::table('failed_requests', function (Blueprint $table) {
            //
        });
    }
}
