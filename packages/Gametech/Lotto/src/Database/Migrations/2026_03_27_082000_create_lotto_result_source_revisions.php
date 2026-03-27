<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLottoResultSourceRevisions extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lotto_result_source_revisions')) {
            return;
        }

        Schema::create('lotto_result_source_revisions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('source_id');
            $table->unsignedInteger('revision_no');
            $table->json('snapshot_json');
            $table->string('config_hash', 128);
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('reason')->nullable();
            $table->dateTime('created_at')->nullable();

            $table->unique(['source_id', 'revision_no'], 'lotto_result_source_revisions_source_revision_unique');
            $table->index(['config_hash'], 'lotto_result_source_revisions_config_hash_idx');
            $table->index(['changed_by'], 'lotto_result_source_revisions_changed_by_idx');
            $table->index(['created_at'], 'lotto_result_source_revisions_created_at_idx');

            if (Schema::hasTable('lotto_result_sources')) {
                $table->foreign('source_id')
                    ->references('id')
                    ->on('lotto_result_sources')
                    ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotto_result_source_revisions')) {
            return;
        }

        Schema::dropIfExists('lotto_result_source_revisions');
    }
}
