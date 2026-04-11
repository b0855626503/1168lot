<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Jobs\FetchRelayLotteryResultJob;
use Gametech\Lotto\Services\Relay\LotteryRelayRuntime;
use Gametech\Lotto\Services\Relay\LotteryRelayStream;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class LottoRelayConsumeStreamCommand extends Command
{
    protected $signature = 'lotto:relay:consume-stream
        {--count=10 : Max messages to read in one pull}
        {--block-ms=5000 : Redis stream block timeout in milliseconds}
        {--consumer= : Override consumer name}
        {--once : Run one pull then exit}';

    protected $description = 'Consume lottery.ready events from Redis Streams and dispatch explicit lotto fetch jobs';

    public function handle(LotteryRelayRuntime $runtime, LotteryRelayStream $stream): int
    {
        if (! $runtime->shouldConsumeRelayStream()) {
            $this->line('Relay consumer skipped: runtime mode is not clone.');

            return self::SUCCESS;
        }

        $count = max(1, (int) $this->option('count'));
        $blockMs = max(0, (int) $this->option('block-ms'));
        $consumer = trim((string) ($this->option('consumer') ?: $runtime->consumerName()));
        $group = $runtime->consumerGroup();
        $connection = $runtime->streamConnection();
        $streamKey = $runtime->streamKey();

        $stream->ensureConsumerGroup($connection, $streamKey, $group);

        Log::channel($runtime->logChannel())->info('LOTTERY_RELAY_CONSUME_POLLING', [
            'stream_connection' => $connection,
            'stream_key' => $streamKey,
            'consumer_group' => $group,
            'consumer' => $consumer,
            'count' => $count,
            'block_ms' => $blockMs,
        ]);

        $messages = $stream->readGroup($connection, $streamKey, $group, $consumer, $count, $blockMs);

        if ($messages === []) {
            Log::channel($runtime->logChannel())->info('LOTTERY_RELAY_CONSUME_EMPTY', [
                'stream_connection' => $connection,
                'stream_key' => $streamKey,
                'consumer_group' => $group,
                'consumer' => $consumer,
            ]);
        }

        foreach ($messages as $message) {
            $fields = $message['fields'];
            $eventId = trim((string) ($fields['event_id'] ?? ''));
            $type = trim((string) ($fields['type'] ?? ''));
            $date = trim((string) ($fields['date'] ?? ''));
            $checksum = trim((string) ($fields['checksum'] ?? ''));

            if ($eventId === '' || $type === '' || $date === '' || $checksum === '') {
                Log::channel($runtime->logChannel())->warning('LOTTERY_RELAY_CONSUME_INVALID_MESSAGE', [
                    'message_id' => $message['id'],
                    'fields' => $fields,
                ]);
                $stream->ack($connection, $streamKey, $group, $message['id']);

                continue;
            }

            Bus::dispatch((new FetchRelayLotteryResultJob($type, $date, $eventId, $checksum))->onQueue($runtime->queue()));
            $stream->ack($connection, $streamKey, $group, $message['id']);

            Log::channel($runtime->logChannel())->info('LOTTERY_RELAY_CONSUMED', [
                'message_id' => $message['id'],
                'event_id' => $eventId,
                'type' => $type,
                'date' => $date,
                'checksum' => $checksum,
                'consumer_group' => $group,
                'consumer' => $consumer,
                'stream_connection' => $connection,
                'stream_key' => $streamKey,
            ]);
        }

        $this->line(sprintf('Consumed %d relay message(s).', count($messages)));

        return self::SUCCESS;
    }
}
