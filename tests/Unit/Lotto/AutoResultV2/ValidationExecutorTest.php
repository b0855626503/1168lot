<?php

namespace Tests\Unit\Lotto\AutoResultV2;

use Gametech\Lotto\Services\AutoResultV2\ConfigData\ValidationConfigData;
use Gametech\Lotto\Services\AutoResultV2\Executors\ValidationExecutor;
use PHPUnit\Framework\TestCase;

class ValidationExecutorTest extends TestCase
{
    public function test_validation_executor_accepts_no_result_marker(): void
    {
        $executor = new ValidationExecutor();
        $config = ValidationConfigData::fromArray([
            'required_fields' => ['first_prize', 'last_2_digits'],
        ]);

        $result = $executor->execute([
            'first_prize' => 'งดออกผล',
            'last_2_digits' => '',
        ], $config);

        $this->assertTrue((bool) ($result['valid'] ?? false));
        $this->assertTrue((bool) ($result['normalized']['no_result'] ?? false));
        $this->assertSame('งดออกผล', $result['normalized']['no_result_reason']);
    }
}
