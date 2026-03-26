<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_draws')) {
            return;
        }

        Schema::table('lotto_draws', function (Blueprint $table): void {
            if (! Schema::hasColumn('lotto_draws', 'opened_at')) {
                $table->dateTime('opened_at')->nullable()->after('open_at');
            }

            if (! Schema::hasColumn('lotto_draws', 'closed_at')) {
                $table->dateTime('closed_at')->nullable()->after('close_at');
            }

            if (! Schema::hasColumn('lotto_draws', 'open_mode')) {
                $table->string('open_mode', 20)->nullable()->after('opened_at');
            }

            if (! Schema::hasColumn('lotto_draws', 'close_mode')) {
                $table->string('close_mode', 20)->nullable()->after('closed_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotto_draws')) {
            return;
        }

        Schema::table('lotto_draws', function (Blueprint $table): void {
            foreach (['open_mode', 'close_mode', 'opened_at', 'closed_at'] as $column) {
                if (Schema::hasColumn('lotto_draws', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
