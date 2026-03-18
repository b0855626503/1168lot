<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_delivery_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->unsignedBigInteger('campaign_id')->nullable()->index();
            $table->unsignedBigInteger('recipient_id')->nullable()->index();

            $table->string('provider', 30)->default('vonage')->index();

            // Vonage payload fields :contentReference[oaicite:4]{index=4}
            $table->string('message_id', 80)->index();           // messageId
            $table->string('msisdn', 20)->nullable()->index();   // recipient phone
            $table->string('to', 50)->nullable();               // senderId/from
            $table->string('network_code', 20)->nullable();     // network-code
            $table->string('status', 30)->nullable()->index();  // delivered/failed/...
            $table->string('err_code', 20)->nullable()->index();// err-code
            $table->string('scts', 20)->nullable();             // YYMMDDHHMM
            $table->string('api_key', 50)->nullable()->index(); // api-key
            $table->string('message_timestamp', 50)->nullable();// message-timestamp
            $table->string('price', 30)->nullable();

            $table->json('payload')->nullable();
            $table->timestamp('received_at')->nullable()->index();

            // processing status
            $table->string('process_status', 20)->default('pending')->index(); // pending|processed|ignored|failed
            $table->timestamp('processed_at')->nullable();
            $table->text('process_error')->nullable();

            $table->timestamps();

            // Idempotency key:
            // โดยทั่วไป DLR unique ได้ด้วย (provider, message_id, status, err_code, scts)
            $table->unique(['provider', 'message_id', 'status', 'err_code', 'scts'], 'sms_dlr_provider_msg_status_err_scts_uniq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_delivery_receipts');
    }
};
