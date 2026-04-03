<?php

namespace Tests\Feature\FrontendApi;

use Gametech\FrontendApi\Http\Controllers\Api\V1\MemberController;
use Gametech\Member\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class MemberChangePasswordControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_change_password_updates_hashed_and_legacy_password_fields(): void
    {
        $member = new Member();
        $member->code = 4321;
        $member->exists = true;

        $repo = Mockery::mock();
        $repo->shouldReceive('update')
            ->once()
            ->withArgs(function (array $data, int $memberCode): bool {
                return $memberCode === 4321
                    && ($data['user_pass'] ?? null) === '654321'
                    && isset($data['password'])
                    && is_string($data['password'])
                    && Hash::check('654321', $data['password']);
            })
            ->andReturnTrue();

        $this->app->instance('Gametech\Member\Repositories\MemberRepository', $repo);

        $request = Request::create('/api/v1/member/change-password', 'POST', [
            'password' => '654321',
            'password_confirmation' => '654321',
        ]);
        $request->setUserResolver(static fn () => $member);

        $response = TestResponse::fromBaseResponse(
            app(MemberController::class)->changePassword($request)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('member_code', 4321);
        $response->assertJsonPath('message', 'เปลี่ยนรหัสผ่านสำเร็จ');
    }

    public function test_change_password_accepts_password_confirm_alias(): void
    {
        $member = new Member();
        $member->code = 5001;
        $member->exists = true;

        $repo = Mockery::mock();
        $repo->shouldReceive('update')
            ->once()
            ->withArgs(function (array $data, int $memberCode): bool {
                return $memberCode === 5001
                    && ($data['user_pass'] ?? null) === '777777'
                    && isset($data['password'])
                    && Hash::check('777777', $data['password']);
            })
            ->andReturnTrue();

        $this->app->instance('Gametech\Member\Repositories\MemberRepository', $repo);

        $request = Request::create('/api/v1/member/change-password', 'POST', [
            'password' => '777777',
            'password_confirm' => '777777',
        ]);
        $request->setUserResolver(static fn () => $member);

        $response = TestResponse::fromBaseResponse(
            app(MemberController::class)->changePassword($request)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function test_change_password_requires_authenticated_member(): void
    {
        $request = Request::create('/api/v1/member/change-password', 'POST', [
            'password' => '654321',
            'password_confirmation' => '654321',
        ]);
        $request->setUserResolver(static fn () => null);

        $response = TestResponse::fromBaseResponse(
            app(MemberController::class)->changePassword($request)
        );

        $response->assertStatus(401);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'ไม่พบข้อมูลสมาชิก');
    }

    public function test_change_password_validates_confirmation(): void
    {
        $member = new Member();
        $member->code = 4321;
        $member->exists = true;

        $repo = Mockery::mock();
        $repo->shouldNotReceive('update');
        $this->app->instance('Gametech\Member\Repositories\MemberRepository', $repo);

        $request = Request::create('/api/v1/member/change-password', 'POST', [
            'password' => '654321',
            'password_confirmation' => '000000',
        ]);
        $request->setUserResolver(static fn () => $member);

        $response = TestResponse::fromBaseResponse(
            app(MemberController::class)->changePassword($request)
        );

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }
}
