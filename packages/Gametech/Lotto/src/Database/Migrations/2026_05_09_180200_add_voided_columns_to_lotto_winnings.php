<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_winnings')) {
            return;
        }

        Schema::table('lotto_winnings', function (Blueprint $table): void {
            if (! Schema::hasColumn('lotto_winnings', 'voided_by_correction_id')) {
                $table->unsignedBigInteger('voided_by_correction_id')->nullable()->after('credited_at');
                $table->index('voided_by_correction_id', 'lotto_winnings_voided_by_correction_idx');
            }

            if (! Schema::hasColumn('lotto_winnings', 'voided_at')) {
                $table->dateTime('voided_at')->nullable()->after('voided_by_correction_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotto_winnings')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasColumn('lotto_winnings', 'voided_at')) {
            Schema::table('lotto_winnings', function (Blueprint $table): void {
                $table->dropColumn('voided_at');
            });
        }

        if (Schema::hasColumn('lotto_winnings', 'voided_by_correction_id')) {
            Schema::table('lotto_winnings', function (Blueprint $table): void {
                $table->dropIndex('lotto_winnings_voided_by_correction_idx');
                $table->dropColumn('voided_by_correction_id');
            });
        }
    }
};
