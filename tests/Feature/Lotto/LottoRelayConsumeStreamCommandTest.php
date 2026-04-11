<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Jobs\FetchRelayLotteryResultJob;
use Gametech\Lotto\Services\Relay\LotteryRelayStream;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class LottoRelayConsumeStreamCommandTest extends TestCase
{
    private array $loggedInfoMessages = [];

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockRelayLogger(): void
    {
        $this->loggedInfoMessages = [];

        Log::shouldReceive('channel')
            ->andReturnSelf();
        Log::shouldReceive('info')
            ->andReturnUsing(function (string $message, array $context = []): void {
                $this->loggedInfoMessages[] = [$message, $context];
            });
        Log::shouldReceive('warning')
            ->andReturnUsing(function (string $message, array $context = []): void {
                $this->loggedInfoMessages[] = [$message, $context];
            });
    }

    public function test_clone_mode_consumes_stream_and_dispatches_fetch_job(): void
    {
        config()->set('lottery_result_relay.enabled', true);
        config()->set('lottery_result_relay.mode', 'clone');
        config()->set('lottery_result_relay.stream_connection', 'lotto');
        config()->set('lottery_result_relay.stream_key', 'lotto:stream:results');
        config()->set('lottery_result_relay.consumer_group', 'relay-clone-a');
        config()->set('lottery_result_relay.consumer_name', 'clone-a-consumer');
        config()->set('lottery_result_relay.queue', 'lotto');
        config()->set('lottery_result_relay.log_channel', 'stack');

        Bus::fake();
        $this->mockRelayLogger();

        $stream = Mockery::mock(LotteryRelayStream::class);
        $stream->shouldReceive('ensureConsumerGroup')
            ->once()
            ->with('lotto', 'lotto:stream:results', 'relay-clone-a');
        $stream->shouldReceive('readGroup')
            ->once()
            ->with('lotto', 'lotto:stream:results', 'relay-clone-a', 'clone-a-consumer', 10, 5000)
            ->andReturn([[
                'id' => '1712800000000-0',
                'fields' => [
                    'event' => 'lottery.ready',
                    'event_id' => 'dji:2026-04-11:abcdef',
                    'type' => 'dji',
                    'date' => '2026-04-11',
                    'checksum' => 'abcdef123456',
                ],
            ]]);
        $stream->shouldReceive('ack')
            ->once()
            ->with('lotto', 'lotto:stream:results', 'relay-clone-a', '1712800000000-0');

        $this->app->instance(LotteryRelayStream::class, $stream);

        $exitCode = Artisan::call('lotto:relay:consume-stream');

        $this->assertSame(0, $exitCode);
        $this->assertContains(
            ['LOTTERY_RELAY_CONSUME_POLLING', [
                'stream_connection' => 'lotto',
                'stream_key' => 'lotto:stream:results',
                'consumer_group' => 'relay-clone-a',
                'consumer' => 'clone-a-consumer',
                'count' => 10,
                'block_ms' => 5000,
            ]],
            $this->loggedInfoMessages
        );
        $this->assertContains(
            ['LOTTERY_RELAY_CONSUMED', [
                'message_id' => '1712800000000-0',
                'event_id' => 'dji:2026-04-11:abcdef',
                'type' => 'dji',
                'date' => '2026-04-11',
                'checksum' => 'abcdef123456',
                'consumer_group' => 'relay-clone-a',
                'consumer' => 'clone-a-consumer',
                'stream_connection' => 'lotto',
                'stream_key' => 'lotto:stream:results',
            ]],
            $this->loggedInfoMessages
        );
        Bus::assertDispatched(FetchRelayLotteryResultJob::class, function (FetchRelayLotteryResultJob $job): bool {
            return $job->type === 'dji'
                && $job->date === '2026-04-11'
                && $job->eventId === 'dji:2026-04-11:abcdef'
                && $job->checksum === 'abcdef123456';
        });
    }

    public function test_clone_mode_logs_empty_poll_when_no_messages_arrive(): void
    {
        config()->set('lottery_result_relay.enabled', true);
        config()->set('lottery_result_relay.mode', 'clone');
        config()->set('lottery_result_relay.stream_connection', 'lotto');
        config()->set('lottery_result_relay.stream_key', 'lotto:stream:results');
        config()->set('lottery_result_relay.consumer_group', 'relay-clone-a');
        config()->set('lottery_result_relay.consumer_name', 'clone-a-consumer');
        config()->set('lottery_result_relay.log_channel', 'stack');

        Bus::fake();
        $this->mockRelayLogger();

        $stream = Mockery::mock(LotteryRelayStream::class);
        $stream->shouldReceive('ensureConsumerGroup')
            ->once()
            ->with('lotto', 'lotto:stream:results', 'relay-clone-a');
        $stream->shouldReceive('readGroup')
            ->once()
            ->with('lotto', 'lotto:stream:results', 'relay-clone-a', 'clone-a-consumer', 10, 5000)
            ->andReturn([]);
        $stream->shouldReceive('ack')->never();

        $this->app->instance(LotteryRelayStream::class, $stream);

        $exitCode = Artisan::call('lotto:relay:consume-stream');

        $this->assertSame(0, $exitCode);
        $this->assertContains(
            ['LOTTERY_RELAY_CONSUME_EMPTY', [
                'stream_connection' => 'lotto',
                'stream_key' => 'lotto:stream:results',
                'consumer_group' => 'relay-clone-a',
                'consumer' => 'clone-a-consumer',
            ]],
            $this->loggedInfoMessages
        );
        Bus::assertNothingDispatched();
    }
}
