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
        Schema::table('line_conversations', function (Blueprint $table) {
            $table->boolean('is_pinned')
                ->default(false)
                ->index()
                ->after('status'); // หรือหลัง column ที่เหมาะสม
        });

        Schema::table('line_messages', function (Blueprint $table) {
            $table->boolean('is_pinned')
                ->default(false)
                ->index()
                ->after('type'); // เลือกตำแหน่งตามสะดวก
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('line_conversations', function (Blueprint $table) {
            //
        });
    }
};
