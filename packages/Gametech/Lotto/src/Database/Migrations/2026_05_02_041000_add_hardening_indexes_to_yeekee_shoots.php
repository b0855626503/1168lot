<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('yeekee_shoots')) {
            return;
        }

        Schema::table('yeekee_shoots', function (Blueprint $table): void {
            if (! $this->hasIndex('yeekee_shoots', 'yeekee_shoots_round_submitted_idx')) {
                $table->index(['yeekee_round_id', 'submitted_at'], 'yeekee_shoots_round_submitted_idx');
            }

            if (! $this->hasIndex('yeekee_shoots', 'yeekee_shoots_round_created_idx')) {
                $table->index(['yeekee_round_id', 'created_at'], 'yeekee_shoots_round_created_idx');
            }

            if (! $this->hasIndex('yeekee_shoots', 'yeekee_shoots_round_member_submitted_idx')) {
                $table->index(['yeekee_round_id', 'member_id', 'submitted_at'], 'yeekee_shoots_round_member_submitted_idx');
            }

            if (! $this->hasIndex('yeekee_shoots', 'yeekee_shoots_round_id_idx')) {
                $table->index(['yeekee_round_id', 'id'], 'yeekee_shoots_round_id_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('yeekee_shoots')) {
            return;
        }

        Schema::table('yeekee_shoots', function (Blueprint $table): void {
            if ($this->hasIndex('yeekee_shoots', 'yeekee_shoots_round_id_idx')) {
                $table->dropIndex('yeekee_shoots_round_id_idx');
            }

            if ($this->hasIndex('yeekee_shoots', 'yeekee_shoots_round_member_submitted_idx')) {
                $table->dropIndex('yeekee_shoots_round_member_submitted_idx');
            }

            if ($this->hasIndex('yeekee_shoots', 'yeekee_shoots_round_created_idx')) {
                $table->dropIndex('yeekee_shoots_round_created_idx');
            }

            if ($this->hasIndex('yeekee_shoots', 'yeekee_shoots_round_submitted_idx')) {
                $table->dropIndex('yeekee_shoots_round_submitted_idx');
            }
        });
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        $databaseName = (string) config('database.connections.'.config('database.default').'.database');
        if ($databaseName === '') {
            return false;
        }

        $row = DB::table('information_schema.statistics')
            ->where('table_schema', $databaseName)
            ->where('table_name', $tableName)
            ->where('index_name', $indexName)
            ->first();

        return $row !== null;
    }
};
