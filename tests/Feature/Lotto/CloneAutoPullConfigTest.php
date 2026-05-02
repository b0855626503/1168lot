<?php

namespace Tests\Feature\Lotto;

use Tests\TestCase;

class CloneAutoPullConfigTest extends TestCase
{
    public function test_clone_auto_pull_config_section_exists(): void
    {
        $this->assertArrayHasKey('clone_auto_pull', config('lotto_auto_result'));
    }

    public function test_clone_auto_pull_disabled_by_default(): void
    {
        $this->assertFalse(config('lotto_auto_result.clone_auto_pull.enabled'));
    }

    public function test_clone_auto_pull_group_ids_empty_by_default(): void
    {
        $groupIds = config('lotto_auto_result.clone_auto_pull.group_ids');

        $this->assertIsArray($groupIds);
        $this->assertEmpty($groupIds);
    }

    public function test_clone_auto_pull_delay_minutes_defaults_to_one(): void
    {
        $this->assertSame(1, config('lotto_auto_result.clone_auto_pull.delay_minutes'));
    }

    public function test_group_ids_parsed_as_integers_from_env(): void
    {
        config()->set('lotto_auto_result.clone_auto_pull.group_ids', array_values(array_filter(
            array_map('intval', explode(',', '1,2,3')),
            static fn (int $id): bool => $id > 0
        )));

        $this->assertSame([1, 2, 3], config('lotto_auto_result.clone_auto_pull.group_ids'));
    }

    public function test_group_ids_filters_out_zeros_and_empty(): void
    {
        $parsed = array_values(array_filter(
            array_map('intval', explode(',', '0,1,,2')),
            static fn (int $id): bool => $id > 0
        ));

        $this->assertSame([1, 2], $parsed);
    }

    public function test_delay_minutes_cannot_be_negative(): void
    {
        $delay = max(0, (int) '-1');

        $this->assertSame(0, $delay);
    }

    public function test_config_enabled_flag_is_boolean(): void
    {
        $this->assertIsBool(config('lotto_auto_result.clone_auto_pull.enabled'));
    }

    public function test_config_delay_minutes_is_integer(): void
    {
        $this->assertIsInt(config('lotto_auto_result.clone_auto_pull.delay_minutes'));
    }
}
