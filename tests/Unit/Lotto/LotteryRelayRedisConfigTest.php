<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

class LotteryRelayRedisConfigTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_lotto_redis_connection_has_shared_prefix_override(): void
    {
        $content = file_get_contents($this->rootPath.'/config/database.php');

        $this->assertNotFalse($content);
        $this->assertStringContainsString("'prefix' => env('REDIS_LOTTO_PREFIX', 'lotto_relay:')", $content);
    }
}
