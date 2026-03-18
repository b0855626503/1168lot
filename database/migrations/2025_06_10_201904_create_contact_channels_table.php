<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('contact_channels')) {
            Schema::create('contact_channels', function (Blueprint $table) {
                $table->integer('code', true);
                $table->enum('type', ['line', 'telegram', 'email']);
                $table->string('label'); // เช่น "สอบถามโปร"
                $table->string('link');  // เช่น https://line.me/ti/p/yourlineid
                $table->integer('sort')->default(0);
                $table->enum('enable', ['Y', 'N'])->default('Y');
                $table->string('user_create', 100)->default('');
                $table->string('user_update', 100)->default('');
                $table->timestamp('date_create')->nullable();
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
        Schema::dropIfExists('contact_channels');
    }
}
