<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up()
    {
        Schema::create('lotto_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_enabled')->default(true);
            $table->integer('sort')->default(0);
        });
        Schema::create('lotto_markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('lotto_groups')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_enabled')->default(true);
        });
        Schema::create('lotto_market_bet_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained('lotto_markets')->onDelete('cascade');
            $table->string('bet_type');
            $table->boolean('is_enabled')->default(true);
            $table->decimal('min_bet', 10, 2)->default(1);
            $table->decimal('max_bet', 12, 2)->default(100000);
            $table->decimal('max_per_number', 12, 2)->default(1000000);
            $table->unique(['market_id', 'bet_type']);
        });
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
    }
    public function down()
    {
        Schema::dropIfExists('lotto_rate_plan_items');
        Schema::dropIfExists('lotto_rate_plans');
        Schema::dropIfExists('lotto_market_bet_settings');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');
    }
};
