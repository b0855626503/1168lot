<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMemberTransfer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('members_transfer')) {
            Schema::create('members_transfer', function (Blueprint $table) {
                $table->integer('code', true);
                $table->integer('member_code')->default(0)->index('member_code');
                $table->string('user_name', 100)->default('');
                $table->integer('to_member_code')->default(0)->index('to_member_code');
                $table->string('to_user_name', 100)->default('');
                $table->decimal('amount', 10)->default(0.00);
                $table->enum('enable', ['Y', 'N'])->default('Y');
                $table->string('user_create', 100)->default('');
                $table->timestamp('date_create')->nullable();
                $table->string('user_update', 100)->default('');
                $table->timestamp('date_update')->nullable();
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
        Schema::dropIfExists('members_transfer');
    }
}
