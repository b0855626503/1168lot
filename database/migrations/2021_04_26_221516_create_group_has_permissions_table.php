<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupHasPermissionsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('group_has_permissions')) {
            Schema::create('group_has_permissions', function (Blueprint $table) {
                $table->bigInteger('group_id')->unsigned();
                $table->bigInteger('permission_id')->unsigned();
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
        Schema::drop('group_has_permissions');
    }

}
