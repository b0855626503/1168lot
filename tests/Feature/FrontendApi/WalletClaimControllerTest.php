<?php

namespace Tests\Feature\FrontendApi;

use App\Events\MemberBalanceUpdated;
use App\Events\RealtimeMemberActivityUpdated;
use Gametech\Core\Core;
use Gametech\FrontendApi\Http\Controllers\Api\V1\WalletController;
use Gametech\Member\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class WalletClaimControllerTest extends TestCase
{
    public function test_claim_moves_bonus_to_main_wallet_when_freecredit_is_disabled(): void
    {
        Event::fake([
            MemberBalanceUpdated::class,
            RealtimeMemberActivityUpdated::class,
        ]);

        $member = $this->customer([
            'balance' => 0,
            'balance_free' => 0,
            'bonus' => 120,
            'cashback' => 10,
            'ic' => 5,
            'faststart' => 30,
        ]);

        $this->mockCoreConfig('N', 0);

        $creditLogRepository = Mockery::mock();
        $creditLogRepository->shouldReceive('tranBonus')
            ->once()
            ->with(['member_code' => 9001], 'BONUS')
            ->andReturn(true);
        $this->app->instance('Gametech\Member\Repositories\MemberCreditLogRepository', $creditLogRepository);

        $memberRepository = Mockery::mock();
        $memberRepository->shouldReceive('findOrFail')
            ->once()
            ->with(9001)
            ->andReturn($this->customer([
                'balance' => 120,
                'balance_free' => 0,
                'bonus' => 0,
                'cashback' => 10,
                'ic' => 5,
                'faststart' => 30,
            ]));
        $this->app->instance('Gametech\Member\Repositories\MemberRepository', $memberRepository);

        $request = Request::create('/api/v1/wallet/claim', 'POST', [
            'source' => 'bonus',
        ]);
        $request->attributes->set('frontend_language', 'th');
        $request->setUserResolver(static fn () => $member);

        $response = TestResponse::fromBaseResponse(
            app(WalletController::class)->claim($request)
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.source', 'bonus');
        $response->assertJsonPath('data.type', 'bonus');
        $response->assertJsonPath('data.legacy_type', 'BONUS');
        $response->assertJsonPath('data.claimed_amount', 120);
        $response->assertJsonPath('data.target_wallet', 'balance');
        $response->assertJsonPath('data.profile.balance', 120);
        $response->assertJsonPath('data.profile.bonus', 0);

        Event::assertDispatched(MemberBalanceUpdated::class, function (MemberBalanceUpdated $event): bool {
            return $event->memberCode === 9001
                && $event->balance === 120.0
                && $event->amount === 120.0
                && $event->reason === 'wallet_claim';
        });

        Event::assertDispatched(RealtimeMemberActivityUpdated::class, function (RealtimeMemberActivityUpdated $event): bool {
            return $event->memberCode === 9001
                && $event->method === 'wallet'
                && $event->event === 'claim'
                && ($event->data['legacy_type'] ?? '') === 'BONUS'
                && (float) ($event->data['amount'] ?? 0) === 120.0
                && ($event->data['target_wallet'] ?? '') === 'balance';
        });
    }

    public function test_claim_moves_faststart_to_free_wallet_when_freecredit_is_enabled(): void
    {
        $member = $this->customer([
            'balance' => 0,
            'balance_free' => 0,
            'bonus' => 0,
            'cashback' => 0,
            'ic' => 0,
            'faststart' => 45,
        ]);

        $this->mockCoreConfig('Y', 0);

        $creditFreeLogRepository = Mockery::mock();
        $creditFreeLogRepository->shouldReceive('tranBonus')
            ->once()
            ->with(['member_code' => 9001], 'FASTSTART')
            ->andReturn(true);
        $this->app->instance('Gametech\Member\Repositories\MemberCreditFreeLogRepository', $creditFreeLogRepository);

        $memberRepository = Mockery::mock();
        $memberRepository->shouldReceive('findOrFail')
            ->once()
            ->with(9001)
            ->andReturn($this->customer([
                'balance' => 0,
                'balance_free' => 45,
                'bonus' => 0,
                'cashback' => 0,
                'ic' => 0,
                'faststart' => 0,
            ]));
        $this->app->instance('Gametech\Member\Repositories\MemberRepository', $memberRepository);

        $request = Request::create('/api/v1/wallet/claim', 'POST', [
            'source' => 'faststart',
        ]);
        $request->attributes->set('frontend_language', 'th');
        $request->setUserResolver(static fn () => $member);

        $response = TestResponse::fromBaseResponse(
            app(WalletController::class)->claim($request)
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.source', 'faststart');
        $response->assertJsonPath('data.type', 'faststart');
        $response->assertJsonPath('data.legacy_type', 'FASTSTART');
        $response->assertJsonPath('data.claimed_amount', 45);
        $response->assertJsonPath('data.target_wallet', 'balance_free');
        $response->assertJsonPath('data.profile.balance_free', 45);
        $response->assertJsonPath('data.profile.faststart', 0);
    }

    public function test_claim_rejects_invalid_type(): void
    {
        $member = $this->customer();

        $request = Request::create('/api/v1/wallet/claim', 'POST', [
            'source' => 'unknown',
        ]);
        $request->attributes->set('frontend_language', 'th');
        $request->setUserResolver(static fn () => $member);

        $response = TestResponse::fromBaseResponse(
            app(WalletController::class)->claim($request)
        );

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'ไม่รองรับประเภทโบนัสที่ร้องขอ');
    }

    public function test_claim_supports_legacy_type_parameter_when_source_is_missing(): void
    {
        $member = $this->customer([
            'bonus' => 50,
        ]);

        $this->mockCoreConfig('N', 0);

        $creditLogRepository = Mockery::mock();
        $creditLogRepository->shouldReceive('tranBonus')
            ->once()
            ->with(['member_code' => 9001], 'BONUS')
            ->andReturn(true);
        $this->app->instance('Gametech\Member\Repositories\MemberCreditLogRepository', $creditLogRepository);

        $memberRepository = Mockery::mock();
        $memberRepository->shouldReceive('findOrFail')
            ->once()
            ->with(9001)
            ->andReturn($this->customer([
                'balance' => 50,
                'bonus' => 0,
            ]));
        $this->app->instance('Gametech\Member\Repositories\MemberRepository', $memberRepository);

        $request = Request::create('/api/v1/wallet/claim', 'POST', [
            'type' => 'bonus',
        ]);
        $request->attributes->set('frontend_language', 'th');
        $request->setUserResolver(static fn () => $member);

        $response = TestResponse::fromBaseResponse(
            app(WalletController::class)->claim($request)
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.source', 'bonus');
        $response->assertJsonPath('data.type', 'bonus');
    }

    private function customer(array $attributes = []): Member
    {
        $member = new Member;
        $member->code = 9001;
        $member->name = 'Wallet Member';
        $member->balance = 0;
        $member->balance_free = 0;
        $member->bonus = 0;
        $member->cashback = 0;
        $member->ic = 0;
        $member->faststart = 0;
        $member->exists = true;

        foreach ($attributes as $key => $value) {
            $member->{$key} = $value;
        }

        return $member;
    }

    private function mockCoreConfig(string $freecreditOpen, int $proReset): void
    {
        $config = (object) [
            'freecredit_open' => $freecreditOpen,
            'pro_reset' => $proReset,
        ];

        $core = Mockery::mock(Core::class);
        $core->shouldReceive('getConfigData')
            ->atLeast()
            ->once()
            ->andReturn($config);

        $this->app->instance(Core::class, $core);
        $this->app->instance('core', $core);
    }
}
