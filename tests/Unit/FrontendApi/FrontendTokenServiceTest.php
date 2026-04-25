<?php

namespace Tests\Unit\FrontendApi;

use Gametech\FrontendApi\Services\FrontendTokenService;
use Gametech\Member\Models\Member;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FrontendTokenServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_new_login_invalidates_previous_frontend_token_for_same_member(): void
    {
        $service = new FrontendTokenService;
        $member = new Member;
        $member->code = 10001;

        $firstToken = $service->issue($member)['token'];
        $secondToken = $service->issue($member)['token'];

        $this->assertNull($service->decode($firstToken));
        $this->assertIsArray($service->decode($secondToken));
    }

    public function test_logout_blacklists_token_and_clears_active_frontend_token(): void
    {
        $service = new FrontendTokenService;
        $member = new Member;
        $member->code = 10001;

        $issued = $service->issue($member);

        $this->assertIsArray($service->decode($issued['token']));

        $service->blacklist($issued['payload']);

        $this->assertNull($service->decode($issued['token']));
    }
}
