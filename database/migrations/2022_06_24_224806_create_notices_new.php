<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNoticesNew extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('notices_new')) {
            Schema::create('notices_new', function (Blueprint $table) {
                $table->integer('code', true);
                $table->string('route', 191)->unique();;
                $table->mediumText('message');
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
        Schema::dropIfExists('notices_new');
    }
}
