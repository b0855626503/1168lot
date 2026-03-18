<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFailedRequestsTableNew extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::dropIfExists('failed_requests');

        Schema::create('failed_requests', function (Blueprint $table) {
            $table->bigIncrements('id');

            // ใช้เป็นคีย์กันซ้ำตอน upsert
            $table->string('trace_id', 64)->unique();

            $table->string('url', 2048)->nullable();
            $table->string('method', 16)->nullable();

            // เก็บเป็น LONGTEXT (เราจะ json_encode จากแอปก่อนบันทึก)
            $table->longText('headers')->nullable();
            $table->longText('body')->nullable();

            $table->string('status', 8)->nullable();

            // response อาจยาวมาก
            $table->longText('response')->nullable();

            // เวลาในการประมวลผล วินาที (เช่น 1.257)
            $table->decimal('duration', 8, 3)->nullable();

            // เดิมเป็น array -> เก็บเป็น JSON string ใน LONGTEXT
            $table->longText('txid')->nullable();
            $table->longText('roundId')->nullable();

            $table->string('company', 64)->nullable();
            $table->string('game_user', 128)->nullable();

            $table->timestamps();

            // ดัชนีช่วยค้นดูรายการย้อนหลัง
            $table->index(['created_at']);
            $table->index(['status']);
            $table->index(['company']);
            $table->index(['game_user']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('failed_requests_table_new');
    }
}
