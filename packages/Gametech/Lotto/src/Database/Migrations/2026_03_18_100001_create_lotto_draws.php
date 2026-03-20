<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up()
    {
        Schema::create('lotto_draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained('lotto_markets')->onDelete('cascade');
            $table->date('draw_date');
            $table->dateTime('open_at');
            $table->dateTime('close_at');
            $table->dateTime('result_at')->nullable();
            $table->enum('status', ['draft', 'open', 'closed', 'resulted'])->default('draft');
            $table->json('result_number')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['market_id', 'draw_date']);
            $table->index('status');
        });
        Schema::create('lotto_draw_bet_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw_id')->constrained('lotto_draws')->onDelete('cascade');
            $table->string('bet_type');
            $table->boolean('is_enabled')->default(true);
            $table->decimal('min_bet', 10, 2);
            $table->decimal('max_bet', 12, 2);
            $table->decimal('max_per_number', 12, 2);
            $table->unique(['draw_id', 'bet_type']);
        });
    }
    public function down()
    {
        Schema::dropIfExists('lotto_draw_bet_settings');
        Schema::dropIfExists('lotto_draws');
    }
};
