<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLineConversationNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('line_conversation_notes')) {
            Schema::create('line_conversation_notes', function (Blueprint $table) {
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
                $table->bigIncrements('id');

                // ผูกกับห้องสนทนา
                $table->unsignedBigInteger('line_conversation_id')->index();

                // optional: เผื่ออนาคตอยาก filter / join
                $table->unsignedBigInteger('line_account_id')->nullable()->index();
                $table->unsignedBigInteger('line_contact_id')->nullable()->index();

                // ข้อมูลพนักงานที่เขียน note
                $table->unsignedBigInteger('employee_id')->nullable()->index();
                $table->string('employee_name', 100)->nullable();

                // ตัวเนื้อหาโน้ต
                $table->text('body');

                $table->timestamps();

                // ถ้าชอบตั้ง charset/collation ระบุเพิ่มได้
                // $table->charset = 'utf8mb4';
                // $table->collation = 'utf8mb4_unicode_ci';

                // FK แบบหลวม ๆ (ถ้าอยากเพิ่มจริงให้เช็คชื่อ table/model ที่ใช้อยู่)
                // $table->foreign('line_conversation_id')
                //     ->references('id')->on('line_conversations')
                //     ->onDelete('cascade');
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
        Schema::dropIfExists('line_conversation_notes');
    }
}
