<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('members', 'referral_code')) {
            Schema::table('members', function (Blueprint $table) {
                $table->string('referral_code', 8)->nullable()->after('upline_code');
            });
        }

        Schema::table('members', function (Blueprint $table) {
            $table->unique('referral_code', 'members_referral_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique('members_referral_code_unique');
        });

        if (Schema::hasColumn('members', 'referral_code')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn('referral_code');
            });
        }
    }
};

