<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lotto_result_archives', function (Blueprint $table) {
            $table->id();
            $table->string('market_code', 50);
            $table->date('draw_date');
            $table->string('draw_key', 50);
            $table->json('result_set');
            $table->string('result_hash', 64);
            $table->unsignedBigInteger('source_draw_id')->nullable();
            $table->foreign('source_draw_id')
                ->references('id')
                ->on('lotto_draws')
                ->onDelete('set null');
            $table->string('source_type', 30)->default('internal_mirror');
            $table->unsignedInteger('correction_count')->default(0);
            $table->json('previous_result_set')->nullable();
            $table->json('source_info_json')->nullable();
            $table->dateTime('corrected_at')->nullable();
            $table->timestamps();

            $table->unique(['market_code', 'draw_date', 'draw_key'], 'lotto_result_archives_unique');
            $table->index(['market_code', 'draw_date'], 'lotto_result_archives_market_date');
            $table->index('source_draw_id', 'lotto_result_archives_source_draw_id');
            $table->index('source_type', 'lotto_result_archives_source_type');
        });

        Schema::create('lotto_result_archive_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('archive_id')->nullable();
            $table->foreign('archive_id')
                ->references('id')
                ->on('lotto_result_archives')
                ->onDelete('set null');
            $table->string('market_code', 50);
            $table->date('draw_date');
            $table->string('draw_key', 50);
            $table->string('action', 30);
            $table->string('run_id', 64);
            $table->string('status', 20);
            $table->json('old_result_set')->nullable();
            $table->json('new_result_set')->nullable();
            $table->json('changed_keys')->nullable();
            $table->json('source_info_json')->nullable();
            $table->text('error_message')->nullable();
            $table->json('trace_json')->nullable();
            $table->dateTime('created_at');

            $table->index('archive_id', 'lotto_result_archive_logs_archive_id');
            $table->index(['market_code', 'draw_date', 'draw_key'], 'lotto_result_archive_logs_identity');
            $table->index('action', 'lotto_result_archive_logs_action');
            $table->index('run_id', 'lotto_result_archive_logs_run_id');
            $table->index('created_at', 'lotto_result_archive_logs_created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('lotto_result_archive_logs');
        Schema::dropIfExists('lotto_result_archives');
    }
};
