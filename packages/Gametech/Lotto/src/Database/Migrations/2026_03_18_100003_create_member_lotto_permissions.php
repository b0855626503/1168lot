<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_lotto_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('member_id');
            $table->foreignId('group_id')->nullable()->constrained('lotto_groups')->onDelete('cascade');
            $table->boolean('is_allowed')->default(true);
            $table->timestamps();

            $table->foreign('member_id')->references('code')->on('members')->onDelete('cascade');
            $table->unique(['member_id', 'group_id']);
        });

        Schema::create('member_lotto_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('member_id');
            $table->foreignId('rate_plan_id')->nullable()->constrained('lotto_rate_plans')->nullOnDelete();
            $table->timestamps();

            $table->foreign('member_id')->references('code')->on('members')->onDelete('cascade');
            $table->unique('member_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_lotto_settings');
        Schema::dropIfExists('member_lotto_permissions');
    }
};
