<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotto_draws', function (Blueprint $table): void {
            if (! Schema::hasColumn('lotto_draws', 'telegram_sent_at')) {
                $table->dateTime('telegram_sent_at')->nullable()->after('exhausted_alerted_at');
                $table->index(['telegram_sent_at'], 'lotto_draws_telegram_sent_at_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lotto_draws', function (Blueprint $table): void {
            if (Schema::hasColumn('lotto_draws', 'telegram_sent_at')) {
                $table->dropIndex('lotto_draws_telegram_sent_at_idx');
                $table->dropColumn('telegram_sent_at');
            }
        });
    }
};
