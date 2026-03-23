<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('member_lotto_settings');
        Schema::dropIfExists('member_lotto_permissions');
        Schema::dropIfExists('lotto_rate_plan_items');
        Schema::dropIfExists('lotto_rate_plans');
    }

    public function down()
    {
        Schema::create('lotto_rate_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('lotto_groups')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(true);
        });

        Schema::create('lotto_rate_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rate_plan_id')->constrained('lotto_rate_plans')->onDelete('cascade');
            $table->string('bet_type');
            $table->decimal('payout', 8, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->unique(['rate_plan_id', 'bet_type']);
        });

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
};

