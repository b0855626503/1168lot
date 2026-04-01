<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_group_packages')) {
            return;
        }

        Schema::table('lotto_group_packages', function (Blueprint $table): void {
            if (! Schema::hasColumn('lotto_group_packages', 'image')) {
                $table->string('image')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotto_group_packages')) {
            return;
        }

        Schema::table('lotto_group_packages', function (Blueprint $table): void {
            if (Schema::hasColumn('lotto_group_packages', 'image')) {
                $table->dropColumn('image');
            }
        });
    }
};
