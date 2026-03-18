<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLineContactsTable extends Migration
{
    public function up()
    {
        Schema::create('line_contacts', function (Blueprint $table) {
            $table->bigIncrements('id');

            // ผูกกับ OA ตัวไหน
            $table->unsignedBigInteger('line_account_id');

            // userId จาก LINE
            $table->string('line_user_id')->index();

            // โปรไฟล์จาก LINE
            $table->string('display_name')->nullable();
            $table->string('picture_url')->nullable();
            $table->string('status_message')->nullable();

            // map เข้าสมาชิกในระบบ (member)
            $table->unsignedBigInteger('member_id')->nullable()->index();
            $table->string('member_username', 50)->nullable()->index();
            $table->string('member_mobile', 20)->nullable()->index();

            // tags / label ต่าง ๆ
            $table->json('tags')->nullable();

            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('blocked_at')->nullable();

            $table->timestamps();

            $table->index(['line_account_id', 'line_user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('line_contacts');
    }
}
