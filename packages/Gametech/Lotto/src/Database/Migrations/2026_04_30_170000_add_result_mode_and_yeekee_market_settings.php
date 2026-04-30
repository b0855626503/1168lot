<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lotto_markets')) {
            Schema::table('lotto_markets', function (Blueprint $table): void {
                if (! Schema::hasColumn('lotto_markets', 'result_mode')) {
                    $table->string('result_mode', 32)->default('normal')->after('code');
                    $table->index('result_mode', 'lotto_markets_result_mode_idx');
                }
            });
        }

        if (! Schema::hasTable('yeekee_market_settings')) {
            Schema::create('yeekee_market_settings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('market_id')->constrained('lotto_markets')->onDelete('cascade');
                $table->json('round_config')->nullable();
                $table->json('formula_config')->nullable();
                $table->json('reward_config')->nullable();
                $table->json('refund_config')->nullable();
                $table->json('ui_config')->nullable();
                $table->boolean('reward_enabled')->default(false);
                $table->boolean('refund_if_bet_entries_below_min')->default(false);
                $table->timestamps();
                $table->unique('market_id', 'yeekee_market_settings_market_id_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('yeekee_market_settings');

        if (Schema::hasTable('lotto_markets')) {
            Schema::table('lotto_markets', function (Blueprint $table): void {
                if (Schema::hasColumn('lotto_markets', 'result_mode')) {
                    $table->dropIndex('lotto_markets_result_mode_idx');
                    $table->dropColumn('result_mode');
                }
            });
        }
    }
};
