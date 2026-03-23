<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\WithdrawRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class WithdrawController extends BaseController
{
    private MemberRepository $memberRepository;
    private WithdrawRepository $withdrawRepository;
    private GameUserRepository $gameUserRepository;

    public function __construct(
        MemberRepository $memberRepository,
        WithdrawRepository $withdrawRepository,
        GameUserRepository $gameUserRepository
    ) {
        $this->memberRepository = $memberRepository;
        $this->withdrawRepository = $withdrawRepository;
        $this->gameUserRepository = $gameUserRepository;
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
        ]);

        $config = core()->getConfigData();
        if ((string) ($config->withdraw_status ?? 'Y') === 'N') {
            return $this->sendError('ขณะนี้ ไม่สามารถ แจ้งถอนเงินได้ ชั่วคราว ขออภัยในความไม่สะดวก', 200);
        }

        $member = $this->user();
        if (! $member || ! isset($member->code)) {
            return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
        }

        $id = (int) $member->code;
        $today = now()->toDateString();
        $amount = (float) $request->input('amount');

        $sum = $this->memberRepository->sumWithdrawSeamless($id, $today);
        $withdrawToday = (float) ($sum->withdraw_seamless_amount_sum ?? 0);

        $balance = (float) $member->balance;
        $maxwithdraw = ((float) $member->maxwithdraw_day > 0) ? (float) $member->maxwithdraw_day : (float) $config->maxwithdraw_day;
        $todayRemain = $maxwithdraw - $withdrawToday;

        if ($amount < 1) {
            return $this->sendError(Lang::get('app.withdraw.credit_wrong'), 200);
        }
        if ($balance < $amount) {
            return $this->sendError(Lang::get('app.withdraw.credit_over'), 200);
        }
        if ($amount < (float) $config->minwithdraw) {
            return $this->sendError(Lang::get('app.withdraw.min') . core()->currency($config->minwithdraw), 200);
        }
        if (($amount + $withdrawToday) > $maxwithdraw) {
            return $this->sendError(Lang::get('app.withdraw.over') . core()->currency($maxwithdraw) . ' / วัน วงเงินถอนคงเหลือ ' . $todayRemain, 200);
        }

        try {
            $chk = $this->withdrawRepository->findOneWhere([
                'member_code' => $id,
                'status' => 0,
                'enable' => 'Y',
            ]);
            if ($chk) {
                return $this->sendError(Lang::get('app.withdraw.dup2'), 200);
            }

            if ((string) $config->seamless === 'Y') {
                $gameUser = $this->gameUserRepository->findOneByField('member_code', $id);
                $forceAll = ((string) ($config->wallet_withdraw_all ?? 'N') === 'Y') || ((float) ($gameUser->amount_balance ?? 0) > 0);
                if ($forceAll) {
                    $amount = $balance;
                }
            }

            $response = $this->withdrawRepository->withdrawSeamless($id, $amount);

            return ($response['success'] ?? false)
                ? $this->sendSuccess('คุณทำรายการแจ้งถอนเงิน สำเร็จแล้ว')
                : $this->sendError((string) ($response['msg'] ?? 'ไม่สามารถทำรายการได้'), 200);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถทำรายการถอนเงินได้ในขณะนี้', 422);
        }
    }
}
