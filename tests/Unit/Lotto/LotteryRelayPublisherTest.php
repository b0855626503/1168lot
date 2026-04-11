<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Services\Relay\LotteryRelayPublisher;
use Gametech\Lotto\Services\Relay\LotteryRelayRuntime;
use Gametech\Lotto\Services\Relay\LotteryRelayStream;
use Gametech\Lotto\Services\Relay\LotteryRelayTypeRegistry;
use Mockery;
use Tests\TestCase;

class LotteryRelayPublisherTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_publishes_ready_signal_once_for_new_checksum(): void
    {
        config()->set('lottery_result_relay.enabled', true);
        config()->set('lottery_result_relay.mode', 'primary');
        config()->set('lottery_result_relay.stream_connection', 'lotto');
        config()->set('lottery_result_relay.stream_key', 'lotto:stream:results');
        config()->set('lottery_result_relay.published_marker_prefix', 'lotto:relay:published');
        config()->set('lottery_result_relay.latest_marker_prefix', 'lotto:relay:latest');

        $stream = Mockery::mock(LotteryRelayStream::class);
        $stream->shouldReceive('get')
            ->once()
            ->with('lotto', 'lotto:relay:published:dji:2026-04-11')
            ->andReturn(null);
        $stream->shouldReceive('publish')
            ->once()
            ->withArgs(function ($connection, $streamKey, $payload, $maxLen): bool {
                return $connection === 'lotto'
                    && $streamKey === 'lotto:stream:results'
                    && $payload['event'] === 'lottery.ready'
                    && $payload['type'] === 'dji'
                    && $payload['date'] === '2026-04-11'
                    && $payload['checksum'] === 'abc123';
            })
            ->andReturn('1712800000000-0');
        $stream->shouldReceive('set')
            ->once()
            ->with('lotto', 'lotto:relay:published:dji:2026-04-11', 'abc123');
        $stream->shouldReceive('set')
            ->once()
            ->withArgs(function ($connection, $key, $value): bool {
                return $connection === 'lotto'
                    && $key === 'lotto:relay:latest:dji'
                    && str_contains($value, '"stream_id":"1712800000000-0"');
            });

        $publisher = new LotteryRelayPublisher(
            new LotteryRelayRuntime,
            new LotteryRelayTypeRegistry,
            $stream
        );

        $draw = new LottoDraw([
            'id' => 77,
            'market_id' => 9,
            'result_fetch_status' => 'APPLIED',
            'result_hash' => 'abc123',
        ]);
        $draw->draw_date = now()->setDate(2026, 4, 11);
        $draw->setRelation('market', new LotteryMarket(['code' => 'downjone-stock']));

        $this->assertSame('1712800000000-0', $publisher->publishIfReady($draw));
    }

    public function test_skips_publish_when_checksum_marker_matches(): void
    {
        config()->set('lottery_result_relay.enabled', true);
        config()->set('lottery_result_relay.mode', 'primary');
        config()->set('lottery_result_relay.stream_connection', 'lotto');
        config()->set('lottery_result_relay.published_marker_prefix', 'lotto:relay:published');

        $stream = Mockery::mock(LotteryRelayStream::class);
        $stream->shouldReceive('get')
            ->once()
            ->with('lotto', 'lotto:relay:published:dji:2026-04-11')
            ->andReturn('abc123');
        $stream->shouldReceive('publish')->never();
        $stream->shouldReceive('set')->never();

        $publisher = new LotteryRelayPublisher(
            new LotteryRelayRuntime,
            new LotteryRelayTypeRegistry,
            $stream
        );

        $draw = new LottoDraw([
            'id' => 77,
            'market_id' => 9,
            'result_fetch_status' => 'APPLIED',
            'result_hash' => 'abc123',
        ]);
        $draw->draw_date = now()->setDate(2026, 4, 11);
        $draw->setRelation('market', new LotteryMarket(['code' => 'downjone-stock']));

        $this->assertNull($publisher->publishIfReady($draw));
    }
}
