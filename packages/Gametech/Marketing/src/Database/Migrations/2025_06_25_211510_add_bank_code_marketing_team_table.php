<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('marketing_teams', function (Blueprint $table) {
            $table->dropColumn('bank_name'); // ลบคอลัมน์เดิม (ถ้าไม่ต้องการเก็บไว้)
            $table->unsignedInteger('bank_code')->nullable()->after('password_hash');

            // ถ้าอยากตั้ง foreign key constraint ด้วย
            $table->foreign('bank_code')->references('code')->on('banks');
        });

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
};
