<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_groups') || Schema::hasColumn('lotto_groups', 'description')) {
            return;
        }

        Schema::table('lotto_groups', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotto_groups') || ! Schema::hasColumn('lotto_groups', 'description')) {
            return;
        }

        Schema::table('lotto_groups', function (Blueprint $table): void {
            $table->dropColumn('description');
        });
    }
};
