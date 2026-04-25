<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('members_select_pro')) {
            return;
        }

        Schema::create('members_select_pro', function (Blueprint $table): void {
            $table->integer('code', true);
            $table->integer('member_code')->default(0)->unique('member_code');
            $table->integer('pro_code')->default(0);
            $table->string('pro_name', 100);
            $table->string('pro_id', 30);
            $table->timestamp('date_create')->nullable();
            $table->timestamp('date_update')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members_select_pro');
    }
};
