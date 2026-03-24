<?php

namespace App\Services\Online;

use Illuminate\Support\Facades\Redis;

class MemberOnlineService
{
    private const REDIS_CONNECTION = 'default';
    private const ONLINE_ZSET_KEY = 'online:members:zset';

    public function touch(int $memberCode): void
    {
        if ($memberCode <= 0) {
            return;
        }

        Redis::connection(self::REDIS_CONNECTION)
            ->zadd(self::ONLINE_ZSET_KEY, time(), (string) $memberCode);
    }

    public function countActive(int $windowSeconds = 45): int
    {
        $windowSeconds = max(10, $windowSeconds);
        $this->cleanup(max($windowSeconds * 2, 60));

        return (int) Redis::connection(self::REDIS_CONNECTION)
            ->zcount(self::ONLINE_ZSET_KEY, (string) (time() - $windowSeconds), '+inf');
    }

    public function cleanup(int $staleAfterSeconds = 120): void
    {
        $staleAfterSeconds = max(30, $staleAfterSeconds);

        Redis::connection(self::REDIS_CONNECTION)
            ->zremrangebyscore(self::ONLINE_ZSET_KEY, '-inf', (string) (time() - $staleAfterSeconds));
    }
}
