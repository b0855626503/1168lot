<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('wallet_transactions')) {
            return;
        }

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('member_id')->comment('members.code');
            $table->string('scope', 32)->comment('MEMBER, MEMBER_FREE, GAME, GAME_FREE');
            $table->unsignedBigInteger('game_user_id')->nullable()->comment('game_user.id or game_user_free.id');
            $table->string('direction', 16)->comment('CREDIT, DEBIT');
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('balance_before', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->string('ref_type', 32)->comment('DEPOSIT, WITHDRAW, GAME_TRANSFER, ADJUST, PROMO ...');
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('ref_code', 64)->nullable();
            $table->string('group_code', 64)->nullable();
            $table->unsignedBigInteger('related_txn_id')->nullable();
            $table->string('status', 16)->default('SUCCESS')->comment('SUCCESS, PENDING, FAILED, REVERSED');
            $table->string('description', 255)->nullable();
            $table->json('meta')->nullable();
            $table->string('created_by_type', 16)->nullable()->comment('member, admin, system');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'scope', 'created_at'], 'wallet_member_scope_created_idx');
            $table->index(['ref_type', 'ref_id'], 'wallet_ref_type_id_idx');
            $table->index(['ref_code', 'member_id'], 'wallet_ref_code_member_idx');
            $table->index('member_id');
            $table->index('scope');
            $table->index('game_user_id');
            $table->index('direction');
            $table->index('ref_type');
            $table->index('ref_id');
            $table->index('ref_code');
            $table->index('group_code');
            $table->index('related_txn_id');
            $table->index('status');
            $table->index('created_by_type');
            $table->index('created_by_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('wallet_transactions');
    }
};

