<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('check_case')) {
            Schema::create('check_case', function (Blueprint $table): void {
                $table->increments('code');
                $table->unsignedInteger('bank_code')->nullable();
                $table->string('txid', 100)->unique();
                $table->string('username', 50);
                $table->string('name', 100);
                $table->decimal('amount', 10, 2)->default(0.00);
                $table->decimal('payamount', 10, 2)->default(0.00);
                $table->string('status', 100);
                $table->string('detail', 100);
                $table->string('url', 100);
                $table->longText('qrcode')->nullable();
                $table->enum('enable', ['Y', 'N'])->default('Y');
                $table->string('user_create', 100)->default('');
                $table->string('user_update', 100)->default('');
                $table->timestamp('date_create')->nullable();
                $table->timestamp('date_update')->nullable();
                $table->timestamp('expired_date')->nullable();
                $table->unsignedTinyInteger('method')->default(1)->comment('1=Deposit, 2=Withdraw');
                $table->string('bankAccountNumber', 10)->nullable();
                $table->string('bankAccountName', 50)->nullable();
                $table->string('bankName', 10)->nullable();
                $table->string('promptpayNumber', 20)->nullable();
            });

            return;
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasColumn('check_case', 'code')) {
            DB::statement('ALTER TABLE check_case MODIFY code INT UNSIGNED NOT NULL AUTO_INCREMENT');
        }

        if (Schema::hasColumn('check_case', 'bank_code')) {
            DB::statement('ALTER TABLE check_case MODIFY bank_code INT UNSIGNED NULL');
        } else {
            DB::statement('ALTER TABLE check_case ADD bank_code INT UNSIGNED NULL AFTER code');
        }

        if (! Schema::hasColumn('check_case', 'expired_date')) {
            DB::statement('ALTER TABLE check_case ADD expired_date TIMESTAMP NULL');
        }
    }

    public function down(): void {}
};
