<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLottoFrontendThemeSettingsTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lotto_frontend_theme_settings')) {
            return;
        }

        Schema::create('lotto_frontend_theme_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('singleton_key', 32)->default('default');
            $table->string('preset_key', 32);
            $table->json('tokens');
            $table->json('custom_tokens')->nullable();
            $table->boolean('is_customized')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->string('updated_by', 191)->nullable();
            $table->timestamps();

            $table->unique('singleton_key', 'lotto_frontend_theme_settings_singleton_uniq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotto_frontend_theme_settings');
    }
}
