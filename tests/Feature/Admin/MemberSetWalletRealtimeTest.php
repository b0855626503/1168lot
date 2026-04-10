<?php

namespace Tests\Feature\Admin;

use App\Events\MemberBalanceUpdated;
use App\Events\RealtimeMemberActivityUpdated;
use Gametech\Admin\Http\Controllers\MemberController;
use Gametech\Admin\Models\Admin;
use Gametech\Core\Core;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Fluent;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class MemberSetWalletRealtimeTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_set_wallet_broadcasts_credit_adjust_message_to_member_channel(): void
    {
        $this->mockCoreConfig([
            'maxsetcredit' => 5000,
            'seamless' => 'N',
            'multigame_open' => 'Y',
        ]);

        $admin = new Admin([
            'code' => 99,
            'name' => 'Staff',
            'surname' => 'One',
        ]);

        $guard = Mockery::mock();
        $guard->shouldReceive('user')->andReturn($admin);
        $guard->shouldReceive('id')->once()->andReturn(99);
        Auth::shouldReceive('guard')->with('admin')->andReturn($guard);

        $memberBefore = (object) ['code' => 1, 'balance' => 100.0];
        $memberAfter = (object) ['code' => 1, 'balance' => 600.0];

        $memberRepository = Mockery::mock('Gametech\Member\Repositories\MemberRepository');
        $memberRepository->shouldReceive('find')
            ->with(1)
            ->twice()
            ->andReturn($memberBefore, $memberAfter);

        $memberCreditLogRepository = Mockery::mock('Gametech\Member\Repositories\MemberCreditLogRepository');
        $memberCreditLogRepository->shouldReceive('setWallet')
            ->once()
            ->with(Mockery::on(function (array $payload): bool {
                return $payload['member_code'] === 1
                    && $payload['method'] === 'D'
                    && (float) $payload['amount'] === 500.0
                    && $payload['remark'] === 'โบนัสชดเชย';
            }))
            ->andReturnTrue();

        $broadcastFactory = Mockery::mock(BroadcastFactory::class);
        $broadcastFactory->shouldReceive('event')
            ->once()
            ->with(Mockery::on(function ($event): bool {
                return $event instanceof MemberBalanceUpdated
                    && $event->memberCode === 1
                    && (float) $event->balance === 600.0
                    && (float) $event->amount === 500.0
                    && $event->reason === 'admin_adjust_credit'
                    && $event->message === 'ทีมงานเพิ่มเครดิต 500.00 บาท หมายเหตุ: โบนัสชดเชย';
            }))
            ->andReturnSelf();
        $broadcastFactory->shouldReceive('event')
            ->once()
            ->with(Mockery::on(function ($event): bool {
                return $event instanceof RealtimeMemberActivityUpdated
                    && $event->memberCode === 1
                    && $event->method === 'adjust'
                    && $event->event === 'wallet.admin_adjusted'
                    && $event->message === 'ทีมงานเพิ่มเครดิต 500.00 บาท หมายเหตุ: โบนัสชดเชย'
                    && ($event->data['direction'] ?? null) === 'credit'
                    && ($event->data['remark'] ?? null) === 'โบนัสชดเชย'
                    && (float) ($event->data['balance'] ?? 0) === 600.0;
            }))
            ->andReturnSelf();
        $this->app->instance(BroadcastFactory::class, $broadcastFactory);

        $response = $this->setWallet($memberRepository, $memberCreditLogRepository, [
            'id' => 1,
            'amount' => 500,
            'type' => 'D',
            'remark' => 'โบนัสชดเชย',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }

    public function test_set_wallet_broadcasts_debit_adjust_message_to_member_channel(): void
    {
        $this->mockCoreConfig([
            'maxsetcredit' => 5000,
            'seamless' => 'N',
            'multigame_open' => 'Y',
        ]);

        $admin = new Admin([
            'code' => 44,
            'name' => 'Staff',
            'surname' => 'Two',
        ]);

        $guard = Mockery::mock();
        $guard->shouldReceive('user')->andReturn($admin);
        $guard->shouldReceive('id')->once()->andReturn(44);
        Auth::shouldReceive('guard')->with('admin')->andReturn($guard);

        $memberBefore = (object) ['code' => 1, 'balance' => 900.0];
        $memberAfter = (object) ['code' => 1, 'balance' => 400.0];

        $memberRepository = Mockery::mock('Gametech\Member\Repositories\MemberRepository');
        $memberRepository->shouldReceive('find')
            ->with(1)
            ->twice()
            ->andReturn($memberBefore, $memberAfter);

        $memberCreditLogRepository = Mockery::mock('Gametech\Member\Repositories\MemberCreditLogRepository');
        $memberCreditLogRepository->shouldReceive('setWallet')
            ->once()
            ->with(Mockery::on(function (array $payload): bool {
                return $payload['member_code'] === 1
                    && $payload['method'] === 'W'
                    && (float) $payload['amount'] === 500.0
                    && $payload['remark'] === 'ปรับยอดผิดพลาด';
            }))
            ->andReturnTrue();

        $broadcastFactory = Mockery::mock(BroadcastFactory::class);
        $broadcastFactory->shouldReceive('event')
            ->once()
            ->with(Mockery::on(function ($event): bool {
                return $event instanceof MemberBalanceUpdated
                    && $event->reason === 'admin_adjust_debit'
                    && $event->message === 'ทีมงานลดเครดิต 500.00 บาท หมายเหตุ: ปรับยอดผิดพลาด';
            }))
            ->andReturnSelf();
        $broadcastFactory->shouldReceive('event')
            ->once()
            ->with(Mockery::on(function ($event): bool {
                return $event instanceof RealtimeMemberActivityUpdated
                    && $event->message === 'ทีมงานลดเครดิต 500.00 บาท หมายเหตุ: ปรับยอดผิดพลาด'
                    && ($event->data['direction'] ?? null) === 'debit'
                    && ($event->data['reason'] ?? null) === 'admin_adjust_debit';
            }))
            ->andReturnSelf();
        $this->app->instance(BroadcastFactory::class, $broadcastFactory);

        $response = $this->setWallet($memberRepository, $memberCreditLogRepository, [
            'id' => 1,
            'amount' => 500,
            'type' => 'W',
            'remark' => 'ปรับยอดผิดพลาด',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function setWallet($memberRepository, $memberCreditLogRepository, array $payload): TestResponse
    {
        $request = Request::create('/admin/member/setwallet', 'POST', $payload);
        $this->app->instance('request', $request);

        $controller = new MemberController(
            Mockery::mock('Gametech\Game\Repositories\GameUserRepository'),
            Mockery::mock('Gametech\Game\Repositories\GameRepository'),
            $memberRepository,
            $memberCreditLogRepository,
            Mockery::mock('Gametech\Member\Repositories\MemberPointLogRepository'),
            Mockery::mock('Gametech\Member\Repositories\MemberDiamondLogRepository'),
            Mockery::mock('Gametech\Payment\Repositories\BankPaymentRepository'),
            Mockery::mock('Gametech\Member\Repositories\MemberRemarkRepository')
        );

        return TestResponse::fromBaseResponse($controller->setWallet($request));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function mockCoreConfig(array $config): void
    {
        $core = Mockery::mock(Core::class);
        $core->shouldReceive('getConfigData')
            ->andReturn(new Fluent($config));

        $this->app->instance(Core::class, $core);
    }
}
