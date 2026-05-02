<?php

namespace Tests\Unit\Lotto;

use Tests\TestCase;

class LottoCloneAutoPullConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('LOTTERY_RESULT_CLONE_AUTO_PULL_ENABLED');
        putenv('LOTTERY_RESULT_CLONE_AUTO_PULL_GROUP_IDS');
        unset($_ENV['LOTTERY_RESULT_CLONE_AUTO_PULL_ENABLED'], $_ENV['LOTTERY_RESULT_CLONE_AUTO_PULL_GROUP_IDS']);
        unset($_SERVER['LOTTERY_RESULT_CLONE_AUTO_PULL_ENABLED'], $_SERVER['LOTTERY_RESULT_CLONE_AUTO_PULL_GROUP_IDS']);

        parent::tearDown();
    }

    public function test_clone_auto_pull_default_is_disabled(): void
    {
        putenv('LOTTERY_RESULT_CLONE_AUTO_PULL_ENABLED');
        $_ENV['LOTTERY_RESULT_CLONE_AUTO_PULL_ENABLED'] = '';
        $_SERVER['LOTTERY_RESULT_CLONE_AUTO_PULL_ENABLED'] = '';

        $config = require base_path('config/lotto_auto_result.php');

        $this->assertFalse((bool) $config['clone_auto_pull']['enabled']);
    }

    public function test_clone_auto_pull_group_ids_are_parsed_from_env(): void
    {
        putenv('LOTTERY_RESULT_CLONE_AUTO_PULL_GROUP_IDS=11, 0, 12,abc,13');
        $_ENV['LOTTERY_RESULT_CLONE_AUTO_PULL_GROUP_IDS'] = '11, 0, 12,abc,13';
        $_SERVER['LOTTERY_RESULT_CLONE_AUTO_PULL_GROUP_IDS'] = '11, 0, 12,abc,13';

        $config = require base_path('config/lotto_auto_result.php');

        $this->assertSame([11, 12, 13], $config['clone_auto_pull']['group_ids']);
    }
}
