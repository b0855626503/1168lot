<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('members', 'wallet_address')) {
            Schema::table('members', function (Blueprint $table) {
                $table->string('wallet_address', 255)->nullable()->after('wallet_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('members', 'wallet_address')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn('wallet_address');
            });
        }
    }
};
