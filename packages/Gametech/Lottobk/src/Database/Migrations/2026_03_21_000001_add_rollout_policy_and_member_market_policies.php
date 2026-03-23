<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lotto_groups', function (Blueprint $table) {
            $table->string('rollout_mode', 20)->default('new_only');
            $table->boolean('affect_existing_members')->default(false);
            $table->unsignedInteger('policy_version')->default(1);
        });

        Schema::table('lotto_markets', function (Blueprint $table) {
            $table->string('rollout_mode', 20)->nullable();
            $table->boolean('affect_existing_members')->nullable();
            $table->unsignedInteger('policy_version')->default(1);
        });

        Schema::create('member_lotto_market_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('member_id');
            $table->foreignId('group_id')->nullable()->constrained('lotto_groups')->onDelete('cascade');
            $table->foreignId('market_id')->constrained('lotto_markets')->onDelete('cascade');
            $table->boolean('is_allowed')->default(false);
            $table->string('source', 20)->default('inherit');
            $table->unsignedInteger('policy_version')->default(1);
            $table->timestamps();

            $table->foreign('member_id')->references('code')->on('members')->onDelete('cascade');
            $table->unique(['member_id', 'market_id'], 'member_market_unique');
            $table->index(['member_id', 'is_allowed']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_lotto_market_policies');

        Schema::table('lotto_markets', function (Blueprint $table) {
            $table->dropColumn(['rollout_mode', 'affect_existing_members', 'policy_version']);
        });

        Schema::table('lotto_groups', function (Blueprint $table) {
            $table->dropColumn(['rollout_mode', 'affect_existing_members', 'policy_version']);
        });
    }
};

