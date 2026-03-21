<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Services\MemberMarketPolicyService;
use PHPUnit\Framework\TestCase;

class MemberMarketPolicyServiceTest extends TestCase
{
    public function test_is_valid_rollout_mode_accepts_supported_values(): void
    {
        $service = new MemberMarketPolicyService();

        $this->assertTrue($service->isValidRolloutMode(MemberMarketPolicyService::ROLLOUT_NEW_ONLY));
        $this->assertTrue($service->isValidRolloutMode(MemberMarketPolicyService::ROLLOUT_ALL));
        $this->assertTrue($service->isValidRolloutMode(MemberMarketPolicyService::ROLLOUT_SELECTED));
    }

    public function test_is_valid_rollout_mode_rejects_unknown_value(): void
    {
        $service = new MemberMarketPolicyService();

        $this->assertFalse($service->isValidRolloutMode('legacy_allow_all'));
    }
}

