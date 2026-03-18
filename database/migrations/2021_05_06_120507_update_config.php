<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('configs', 'verify_sms')) {
            Schema::table('configs', function (Blueprint $table) {
                $table->enum('verify_sms', ['Y', 'N'])->default('N');
            });
        }
        if (!Schema::hasColumn('configs', 'sms_provider')) {
            Schema::table('configs', function (Blueprint $table) {
                $table->tinyInteger('sms_provider')->nullable();
            });
        }
        if (!Schema::hasColumn('configs', 'sms_username')) {
            Schema::table('configs', function (Blueprint $table) {
                $table->string('sms_username',50);
            });
        }
        if (!Schema::hasColumn('configs', 'sms_password')) {
            Schema::table('configs', function (Blueprint $table) {
                $table->string('sms_password',50);
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
        Schema::table('configs', function (Blueprint $table) {
            $table->dropColumn(['verify_sms','verify_sms','sms_username','sms_password']);
        });
    }
}
