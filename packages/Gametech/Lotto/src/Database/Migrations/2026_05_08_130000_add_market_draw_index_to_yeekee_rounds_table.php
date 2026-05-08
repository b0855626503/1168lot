<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'yeekee_rounds_market_draw_id_idx';

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable('yeekee_rounds')) {
            return;
        }

        if ($this->indexExists()) {
            return;
        }

        Schema::table('yeekee_rounds', function (Blueprint $table): void {
            $table->index(['market_id', 'lotto_draw_id', 'id'], self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable('yeekee_rounds')) {
            return;
        }

        if (! $this->indexExists()) {
            return;
        }

        Schema::table('yeekee_rounds', function (Blueprint $table): void {
            $table->dropIndex(self::INDEX_NAME);
        });
    }

    private function indexExists(): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'yeekee_rounds')
            ->where('index_name', self::INDEX_NAME)
            ->exists();
    }
};
