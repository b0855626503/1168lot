<?php

namespace App\Jobs;

use App\Helpers\TelegramBot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTelegramBot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $timeout = 10;
    public $backoff = [5, 30, 120];

    /**
     * @param array<string,mixed> $options
     */
    public function __construct(
        public string $endpoint,
        public string $message,
        public array $options = []
    ) {
    }

    public function handle(): void
    {
        $msg = mb_strimwidth($this->message, 0, 3900, '...');
        TelegramBot::Send($this->endpoint, $msg, $this->options);
    }
}

