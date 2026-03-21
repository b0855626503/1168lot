<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('withdraws_free', 'idx_wdf_status_enable_create', ['status', 'enable', 'date_create']);
        $this->addIndexIfMissing('withdraws_free', 'idx_wdf_status_enable_approve', ['status', 'enable', 'date_approve']);
        $this->addIndexIfMissing('withdraws_seamless_free', 'idx_wdsf_status_enable_create', ['status', 'enable', 'date_create']);
        $this->addIndexIfMissing('withdraws_seamless_free', 'idx_wdsf_status_enable_approve', ['status', 'enable', 'date_approve']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('withdraws_free', 'idx_wdf_status_enable_create');
        $this->dropIndexIfExists('withdraws_free', 'idx_wdf_status_enable_approve');
        $this->dropIndexIfExists('withdraws_seamless_free', 'idx_wdsf_status_enable_create');
        $this->dropIndexIfExists('withdraws_seamless_free', 'idx_wdsf_status_enable_approve');
    }

    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if (! Schema::hasTable($table) || ! $this->hasColumns($table, $columns) || $this->hasIndex($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($columns, $indexName) {
            $tableBlueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! $this->hasIndex($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($indexName) {
            $tableBlueprint->dropIndex($indexName);
        });
    }

    private function hasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $database = DB::connection()->getDatabaseName();

        $result = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();

        return (bool) $result;
    }
};

