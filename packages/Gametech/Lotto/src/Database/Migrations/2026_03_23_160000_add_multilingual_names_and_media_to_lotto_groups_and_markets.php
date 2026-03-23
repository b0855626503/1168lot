<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addColumnsIfMissing('lotto_groups');
        $this->addColumnsIfMissing('lotto_markets');
    }

    public function down(): void
    {
        $this->dropColumnsIfExists('lotto_groups');
        $this->dropColumnsIfExists('lotto_markets');
    }

    private function addColumnsIfMissing(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $columnsToAdd = [];

        foreach (['name_en', 'name_kh', 'name_laos', 'logo', 'icon'] as $column) {
            if (! Schema::hasColumn($table, $column)) {
                $columnsToAdd[] = $column;
            }
        }

        if (empty($columnsToAdd)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columnsToAdd): void {
            foreach ($columnsToAdd as $column) {
                $blueprint->string($column)->nullable();
            }
        });
    }

    private function dropColumnsIfExists(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $columnsToDrop = [];

        foreach (['name_en', 'name_kh', 'name_laos', 'logo', 'icon'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if (empty($columnsToDrop)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columnsToDrop): void {
            foreach ($columnsToDrop as $column) {
                $blueprint->dropColumn($column);
            }
        });
    }
};
