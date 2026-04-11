<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Services\Relay\LotteryRelayStream;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class LotteryRelayStreamTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_publish_uses_xadd_wrapper_before_raw_command(): void
    {
        $redis = Mockery::mock();
        $redis->shouldReceive('xadd')
            ->once()
            ->with('lotto:stream:results', '*', ['event' => 'lottery.ready'], 1000, true)
            ->andReturn('1712800000000-0');
        $redis->shouldReceive('command')->never();

        Redis::shouldReceive('connection')
            ->once()
            ->with('lotto')
            ->andReturn($redis);

        $stream = new LotteryRelayStream;

        $this->assertSame(
            '1712800000000-0',
            $stream->publish('lotto', 'lotto:stream:results', ['event' => 'lottery.ready'], 1000)
        );
    }

    public function test_publish_falls_back_to_raw_command_when_wrapper_signature_is_incompatible(): void
    {
        $redis = Mockery::mock();
        $redis->shouldReceive('xadd')
            ->once()
            ->andThrow(new \ArgumentCountError('Redis::xadd() expects at most 6 arguments, 17 given'));
        $redis->shouldReceive('command')
            ->once()
            ->with('XADD', [
                'lotto:stream:results',
                'MAXLEN',
                '~',
                '1000',
                '*',
                'event',
                'lottery.ready',
                'type',
                'dji',
            ])
            ->andReturn('1712800000001-0');

        Redis::shouldReceive('connection')
            ->once()
            ->with('lotto')
            ->andReturn($redis);

        $stream = new LotteryRelayStream;

        $this->assertSame(
            '1712800000001-0',
            $stream->publish(
                'lotto',
                'lotto:stream:results',
                ['event' => 'lottery.ready', 'type' => 'dji'],
                1000
            )
        );
    }
}
