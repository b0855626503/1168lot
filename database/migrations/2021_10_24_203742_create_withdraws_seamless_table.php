<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWithdrawsSeamlessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('withdraws_seamless')) {
            Schema::create('withdraws_seamless', function (Blueprint $table) {
                $table->integer('code', true);
                $table->integer('member_code')->default(0);
                $table->string('member_user', 100);
                $table->integer('account_code')->default(0);
                $table->string('bankout', 100);
                $table->integer('bankm_code')->default(0);
                $table->decimal('amount', 11);
                $table->decimal('balance', 10)->default(0.00);
                $table->decimal('amount_balance', 10)->default(0.00);
                $table->decimal('amount_limit', 10)->default(0.00);
                $table->decimal('amount_limit_rate', 10)->default(0.00);
                $table->date('date_record')->default('0000-00-00');
                $table->time('timedept');
                $table->enum('ck_deposit', ['N', 'Y'])->default('N');
                $table->enum('check_status', ['N', 'Y'])->default('N');
                $table->enum('ck_withdraw', ['N', 'Y'])->default('N');
                $table->enum('ck_balance', ['N', 'Y'])->default('N');
                $table->decimal('oldcredit', 11)->nullable();
                $table->decimal('aftercredit', 11);
                $table->decimal('fee', 11);
                $table->text('remark');
                $table->string('ckb_user', 255)->default('');
                $table->timestamp('ckb_date')->nullable();
                $table->string('ip', 50)->default('');
                $table->string('ip_admin', 100)->default('');
                $table->string('remark_admin', 255)->default('');
                $table->integer('emp_approve')->default(0);
                $table->timestamp('date_approve')->nullable();
                $table->string('user_create', 100)->default('');
                $table->string('user_update', 100)->default('');
                $table->timestamp('date_create')->nullable();
                $table->timestamp('date_update')->nullable();
                $table->enum('enable', ['Y', 'N'])->default('Y');
                $table->integer('status')->default(0);
                $table->integer('ck_step2')->default(0);
                $table->date('date_bank')->default('0000-00-00');
                $table->string('time_bank', 10)->default('');
                $table->string('status_bank', 50);
                $table->string('status_withdraw', 1)->default('W');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('withdraws_seamless');
    }
}
