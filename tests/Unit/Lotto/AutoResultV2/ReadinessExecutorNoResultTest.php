<?php

namespace Tests\Unit\Lotto\AutoResultV2;

use Gametech\Lotto\Services\AutoResultV2\ConfigData\ReadinessConfigData;
use Gametech\Lotto\Services\AutoResultV2\Executors\ReadinessExecutor;
use PHPUnit\Framework\TestCase;

class ReadinessExecutorNoResultTest extends TestCase
{
    public function test_readiness_is_ready_when_no_result_marker_exists(): void
    {
        $executor = new ReadinessExecutor();
        $config = ReadinessConfigData::fromArray([
            'minimum_required_keys' => ['first_prize', 'last_2_digits'],
        ]);

        $result = $executor->execute([
            'first_prize' => '',
            'last_2_digits' => '',
            'no_result' => true,
            'no_result_reason' => 'งดออกผล',
        ], $config, false);

        $this->assertTrue((bool) ($result['ready'] ?? false));
        $this->assertSame('READY', $result['state'] ?? null);
    }
}

