<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lotto_market_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained('lotto_markets')->onDelete('cascade');
            $table->string('locale', 10);
            $table->string('title')->nullable();
            $table->text('summary')->nullable();
            $table->longText('rules_content')->nullable();
            $table->longText('schedule_content')->nullable();
            $table->longText('prize_content')->nullable();
            $table->longText('formula_content')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['market_id', 'locale']);
            $table->index('locale');
            $table->index('is_enabled');
        });
    }

    public function down()
    {
        Schema::dropIfExists('lotto_market_contents');
    }
};
