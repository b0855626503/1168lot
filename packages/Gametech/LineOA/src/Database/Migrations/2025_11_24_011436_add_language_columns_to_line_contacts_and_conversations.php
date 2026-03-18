<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLanguageColumnsToLineContactsAndConversations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // ----- เพิ่มที่ line_contacts -----
        Schema::table('line_contacts', function (Blueprint $table) {
            // ภาษาหลักของลูกค้า (ใช้แทนภาษา default เวลาแปลขาออก)
            $table->string('preferred_language', 20)
                ->nullable()
                ->after('display_name');

            // ภาษา detect ล่าสุดจาก message ขาเข้า
            $table->string('last_detected_language', 20)
                ->nullable()
                ->after('preferred_language');
        });

        // ----- เพิ่มที่ line_conversations -----
        Schema::table('line_conversations', function (Blueprint $table) {
            // ภาษา override สำหรับห้องนี้ (ใช้แปลข้อความขาออก)
            $table->string('outgoing_language', 20)
                ->nullable()
                ->after('status');

            // ภาษา detect จากข้อความขาเข้าเฉพาะห้องนี้ (optional)
            $table->string('incoming_language', 20)
                ->nullable()
                ->after('outgoing_language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('line_contacts', function (Blueprint $table) {
            $table->dropColumn([
                'preferred_language',
                'last_detected_language',
            ]);
        });

        Schema::table('line_conversations', function (Blueprint $table) {
            $table->dropColumn([
                'outgoing_language',
                'incoming_language',
            ]);
        });
    }
}
