<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up()
    {
        Schema::create('lotto_number_exposures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw_id')->constrained('lotto_draws')->onDelete('cascade');
            $table->string('bet_type');
            $table->string('number');
            $table->decimal('sold_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['draw_id', 'bet_type', 'number']);
            $table->index(['draw_id', 'bet_type']);
        });

        Schema::create('lotto_number_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw_id')->constrained('lotto_draws')->onDelete('cascade');
            $table->string('bet_type');
            $table->string('number');
            $table->enum('mode', ['block', 'limit_future'])->default('block');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('blocked_by')->nullable();
            $table->dateTime('blocked_at');
            $table->timestamps();

            $table->unique(['draw_id', 'bet_type', 'number']);
            $table->index(['draw_id', 'blocked_at']);
        });

        Schema::create('lotto_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('member_id');
            $table->foreignId('draw_id')->constrained('lotto_draws')->onDelete('cascade');
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['active', 'cancelled', 'resulted'])->default('active');
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->timestamps();
            $table->foreign('member_id')->references('code')->on('members')->onDelete('cascade');
            $table->index(['member_id', 'draw_id']);
            $table->index(['draw_id', 'status']);
        });
        Schema::create('lotto_ticket_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('lotto_tickets')->onDelete('cascade');
            $table->string('bet_type');
            $table->string('number');
            $table->decimal('amount', 10, 2);
            $table->decimal('payout_at_time', 8, 2);
            $table->string('result_status')->nullable();
            $table->decimal('win_amount', 12, 2)->nullable();
            $table->timestamps();

            $table->index('ticket_id');
        });
    }
    public function down()
    {
        Schema::dropIfExists('lotto_ticket_items');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_number_blocks');
        Schema::dropIfExists('lotto_number_exposures');
    }
};
