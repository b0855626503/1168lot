<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_payment', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_payment', 'deposit_status')) {
                $table->string('deposit_status', 20)->default('NEW')->index();
            }
            if (!Schema::hasColumn('bank_payment', 'deposit_started_at')) {
                $table->dateTime('deposit_started_at')->nullable();
            }
            if (!Schema::hasColumn('bank_payment', 'deposited_at')) {
                $table->dateTime('deposited_at')->nullable();
            }
            if (!Schema::hasColumn('bank_payment', 'finalized_at')) {
                $table->dateTime('finalized_at')->nullable();
            }
            if (!Schema::hasColumn('bank_payment', 'deposit_attempt')) {
                $table->unsignedInteger('deposit_attempt')->default(0);
            }
            if (!Schema::hasColumn('bank_payment', 'deposit_last_error')) {
                $table->text('deposit_last_error')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_payment', function (Blueprint $table) {
            if (Schema::hasColumn('bank_payment', 'deposit_status')) {
                $table->dropIndex(['deposit_status']);
                $table->dropColumn('deposit_status');
            }
            if (Schema::hasColumn('bank_payment', 'deposit_started_at')) {
                $table->dropColumn('deposit_started_at');
            }
            if (Schema::hasColumn('bank_payment', 'deposited_at')) {
                $table->dropColumn('deposited_at');
            }
            if (Schema::hasColumn('bank_payment', 'finalized_at')) {
                $table->dropColumn('finalized_at');
            }
            if (Schema::hasColumn('bank_payment', 'deposit_attempt')) {
                $table->dropColumn('deposit_attempt');
            }
            if (Schema::hasColumn('bank_payment', 'deposit_last_error')) {
                $table->dropColumn('deposit_last_error');
            }
        });
    }
};
