<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotto_result_archive_legacy_results', function (Blueprint $table): void {
            $table->id();

            $table->string('type', 100);
            $table->string('name_th')->nullable();
            $table->date('request_date')->nullable();
            $table->unsignedInteger('page')->nullable();

            $table->unsignedBigInteger('source_result_id')->nullable();
            $table->string('lottos_name', 100);
            $table->string('lottos_th')->nullable();
            $table->dateTime('lottos_date')->nullable();
            $table->string('lottos_date_raw', 50)->nullable();
            $table->string('lottos_time', 20)->nullable();
            $table->string('lottos_number', 50)->nullable();
            $table->string('lottos_under', 50)->nullable();

            $table->string('market_code', 100)->nullable();
            $table->unsignedBigInteger('market_id')->nullable();

            $table->text('source_url')->nullable();
            $table->dateTime('fetched_at')->nullable();
            $table->enum('fetch_status', ['success', 'not_found', 'failed'])->default('success');
            $table->text('last_error')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->json('payload_json')->nullable();

            $table->string('unique_key');

            $table->timestamps();

            $table->unique('unique_key');
            $table->index(['type', 'request_date'], 'lrlar_type_date');
            $table->index('request_date', 'lrlar_request_date');
            $table->index(['lottos_name', 'request_date'], 'lrlar_name_date');
            $table->index('fetched_at', 'lrlar_fetched_at');
            $table->index('fetch_status', 'lrlar_fetch_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotto_result_archive_legacy_results');
    }
};
