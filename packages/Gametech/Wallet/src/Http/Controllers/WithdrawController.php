<?php

namespace Gametech\Wallet\Http\Controllers;

use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\WithdrawRepository;
use Gametech\Payment\Repositories\WithdrawSeamlessRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;

class WithdrawController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected $memberRepository;

    protected $withdrawRepository;

    protected $withdrawSeamlessRepository;

    /**
     * Create a new Repository instance.
     */
    public function __construct(
        MemberRepository $memberRepo,
        WithdrawRepository $withdrawRepo,
        WithdrawSeamlessRepository $withdrawSeamlessRepo
    ) {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->memberRepository = $memberRepo;

        $this->withdrawRepository = $withdrawRepo;

        $this->withdrawSeamlessRepository = $withdrawSeamlessRepo;
    }

    public function index()
    {
        $config = core()->getConfigData();
        if ($config->seamless == 'Y') {
            $profile = $this->memberRepository->sumWithdrawSeamless($this->id(), now()->toDateString());
            $user = app('Gametech\Game\Repositories\GameUserRepository')->findOneByField('member_code', $this->id());
            if ($user->amount_balance > 0) {
                $pro = true;
            } else {
                $pro = false;
            }

            if ($config->wallet_withdraw_all == 'Y') {
                $pro = true;
            }
            $turnpro = $user->amount_balance;
            $limit = $user->withdraw_limit_amount;

        } else {
            $profile = $this->memberRepository->sumWithdraw($this->id(), now()->toDateString());
            if ($config->multigame_open == 'Y') {

                $pro = false;
                $turnpro = 0;
                $limit = 0;
            } else {

                $user = app('Gametech\Game\Repositories\GameUserRepository')->findOneByField('member_code', $this->id());
                if ($user->amount_balance > 0) {
                    $pro = true;
                } else {
                    $pro = false;
                }

                if ($config->wallet_withdraw_all == 'Y') {
                    $pro = true;
                }
                $turnpro = $user->amount_balance;
                $limit = $user->withdraw_limit_amount;
            }
        }

        return view($this->_config['view'], compact('profile', 'pro', 'turnpro', 'limit'));
    }

    public function store_(Request $request): RedirectResponse
    {
        $datenow = now();
        $today = $datenow->toDateString();

        $request->validate([
            'amount' => 'required|numeric',
        ]);

        $config = core()->getConfigData();

        $member = $this->user();
        $id = $this->id();

        if (is_null($member->bank)) {
            session()->flash('error', 'พบข้อผิดพลาด ธนาคารที่ถูกระบุ ไม่ถูกต้อง');

            return redirect()->back();

        }

        if ($config->seamless == 'Y') {
            $withdraw_today = $this->memberRepository->sumWithdrawSeamless($id, $today)->withdrawSeamless_amount_sum;
        } else {
            $withdraw_today = $this->memberRepository->sumWithdraw($id, $today)->withdraw_amount_sum;
        }

        dd($withdraw_today);
        $withdraw = (is_null($withdraw_today) ? 0 : $withdraw_today);

        $amount = floatval($request->input('amount'));
        $balance = $member['balance'];

        if ($amount < 1) {

            session()->flash('error', Lang::get('app.withdraw.credit_wrong'));

            return redirect()->back();

        } elseif ($balance < $amount) {

            session()->flash('error', Lang::get('app.withdraw.credit_over'));

            return redirect()->back();

        } elseif ($amount < $config['minwithdraw']) {

            session()->flash('error', Lang::get('app.withdraw.min').core()->currency($config['minwithdraw']).' บาท');

            return redirect()->back();

        } elseif (($amount + $withdraw) > $config['maxwithdraw_day']) {

            session()->flash('error', Lang::get('app.withdraw.over').core()->currency($config['maxwithdraw_day']).' บาท / วัน');

            return redirect()->back();

        } else {

            if ($config->seamless == 'Y') {

                $game = core()->getGame('transfer');
                if (! isset($game)) {

                    $res = app('Gametech\Game\Repositories\GameUserRepository')->checkOutStandings($game->id, $member->user_name);
                    if ($res['success'] === true) {
                        if ($res['amount'] > 0) {
                            session()->flash('error', Lang::get('app.withdraw.sport'));

                            return redirect()->back();
                        }
                    }

                }

                $chk = $this->withdrawSeamlessRepository->findOneWhere(['member_code' => $id, 'status' => 0, 'enable' => 'Y']);
                if ($chk) {
                    session()->flash('error', Lang::get('app.withdraw.dup2'));

                    return redirect()->back();
                }

                $response = $this->withdrawSeamlessRepository->withdrawSeamless($id, $amount);
                if ($response['success'] === true) {
                    session()->flash('success', $response['msg']);
                } else {
                    session()->flash('error', $response['msg']);
                }
            } elseif ($config->multigame_open == 'Y') {

                $chk = $this->withdrawRepository->findOneWhere(['member_code' => $id, 'status' => 0, 'enable' => 'Y']);
                if ($chk) {
                    session()->flash('error', 'ไม่สามารถแจ้งถอนซ้ำได้ เนื่องจากคุณได้แจ้งถอนไปแล้ว โปรดรอทีมงาน ตรวจสอบ');

                    return redirect()->back();
                }

                $response = $this->withdrawRepository->withdraw($id, $amount);
                if ($response) {
                    session()->flash('success', 'คุณทำรายการแจ้งถอนเงิน สำเร็จแล้ว');
                } else {
                    session()->flash('error', 'พบข้อผิดพลาด ไม่สามารถทำรายการได้');
                }
            } else {

                $chk = $this->withdrawRepository->findOneWhere(['member_code' => $id, 'status' => 0, 'enable' => 'Y']);
                if ($chk) {
                    session()->flash('error', 'ไม่สามารถแจ้งถอนซ้ำได้ เนื่องจากคุณได้แจ้งถอนไปแล้ว โปรดรอทีมงาน ตรวจสอบ');

                    return redirect()->back();
                }

                $response = $this->withdrawRepository->withdrawSingle($id, $amount);
                if ($response['success'] === true) {
                    session()->flash('success', 'คุณทำรายการแจ้งถอนเงิน สำเร็จแล้ว');
                } else {
                    session()->flash('error', $response['msg']);
                }
            }

            return redirect()->back();

        }
    }

    public function store(Request $request): RedirectResponse
    {
        $datenow = now();
        $today = $datenow->toDateString();

        $request->validate([
            'amount' => 'required|numeric',
        ]);

        $config = core()->getConfigData();

        $member = $this->user();
        $id = $this->id();

        $lock = Cache::lock($this->id().':transfer:game', 30, $this->id());
        if (! $lock->get()) {
            session()->flash('error', 'รออีก 30 วินาที ค่อยทำรายการใหม่นะ');

            return redirect()->back();
        }

        //        if (Cache::has('transfer_'.$id)) {
        //            session()->flash('error', 'โปรดรอสักครู่ แล้วจึงทำรายการใหม่');
        //            return redirect()->back();
        //        }
        //
        //        Cache::put('transfer_'.$id, 'lock', now()->addSeconds(10));

        if ($config->seamless == 'Y') {
            $withdraw_today = $this->memberRepository->sumWithdrawSeamless($id, $today)->withdraw_seamless_amount_sum;
        } else {
            $withdraw_today = $this->memberRepository->sumWithdraw($id, $today)->withdraw_amount_sum;
        }

        //        dd($withdraw_today);
        $withdraw = (is_null($withdraw_today) ? 0 : $withdraw_today);

        $amount = (floatval($request->input('amount')));
        $balance = $member['balance'];

        if ($member->maxwithdraw_day == 0) {
            $maxwithdraw = $config->maxwithdraw_day;
        } else {
            $maxwithdraw = $member->maxwithdraw_day;
        }

        $today_wd = ($maxwithdraw - $withdraw);

        if ($amount < 1) {

            session()->flash('error', Lang::get('app.withdraw.credit_wrong'));

            return redirect()->back();

        } elseif ($balance < $amount) {

            session()->flash('error', Lang::get('app.withdraw.credit_over'));

            return redirect()->back();

        } elseif ($amount < $config['minwithdraw']) {

            session()->flash('error', Lang::get('app.withdraw.min').core()->currency($config['minwithdraw']));

            return redirect()->back();

        } elseif (($amount + $withdraw) > $maxwithdraw) {

            session()->flash('error', Lang::get('app.withdraw.over').core()->currency($maxwithdraw).' / วัน วงเงินถอนคงเหลือ '.$today_wd);

            return redirect()->back();

        } else {

            if ($config->seamless == 'Y') {

                $chk = $this->withdrawSeamlessRepository->findOneWhere(['member_code' => $id, 'status' => 0, 'enable' => 'Y']);
                if ($chk) {
                    session()->flash('error', Lang::get('app.withdraw.dup2'));

                    return redirect()->back();
                }

                $user = app('Gametech\Game\Repositories\GameUserRepository')->findOneByField('member_code', $this->id());
                if ($user->amount_balance > 0) {
                    $pro = true;
                } else {
                    $pro = false;
                }

                if ($config->wallet_withdraw_all == 'Y') {
                    $pro = true;
                }

                if ($pro) {
                    $amount = ($balance);
                    //                    if($amount != floor($balance)){
                    //                        session()->flash('error', Lang::get('app.withdraw.all'));
                    //                        return redirect()->back();
                    //                    }
                }

                if (($amount + $withdraw) > $maxwithdraw) {

                    session()->flash('error', Lang::get('app.withdraw.over').core()->currency($maxwithdraw).' / วัน วงเงินถอนคงเหลือ '.$today_wd);

                    return redirect()->back();

                }

                $response = $this->withdrawSeamlessRepository->withdrawSeamless($id, $amount);
                if ($response['success'] === true) {
                    session()->flash('success', $response['msg']);
                } else {
                    session()->flash('error', $response['msg']);
                }

            } else {
                if ($config->multigame_open == 'Y') {

                    $chk = $this->withdrawRepository->findOneWhere(['member_code' => $id, 'status' => 0, 'enable' => 'Y']);
                    if ($chk) {
                        session()->flash('error', 'ไม่สามารถแจ้งถอนซ้ำได้ เนื่องจากคุณได้แจ้งถอนไปแล้ว โปรดรอทีมงาน ตรวจสอบ');

                        return redirect()->back();
                    }

                    $response = $this->withdrawRepository->withdraw($id, $amount);
                    if ($response) {
                        session()->flash('success', 'คุณทำรายการแจ้งถอนเงินเครดิต สำเร็จแล้ว');
                    } else {
                        session()->flash('error', 'พบข้อผิดพลาด ไม่สามารถทำรายการได้');

                    }
                } else {

                    $chk = $this->withdrawRepository->findOneWhere(['member_code' => $id, 'status' => 0, 'enable' => 'Y']);
                    if ($chk) {
                        session()->flash('error', 'ไม่สามารถแจ้งถอนซ้ำได้ เนื่องจากคุณได้แจ้งถอนไปแล้ว โปรดรอทีมงาน ตรวจสอบ');

                        return redirect()->back();
                    }

                    $user = app('Gametech\Game\Repositories\GameUserRepository')->findOneByField('member_code', $this->id());
                    if ($user->amount_balance > 0) {
                        $pro = true;
                    } else {
                        $pro = false;
                    }

                    if ($config->wallet_withdraw_all == 'Y') {
                        $pro = true;
                    }

                    if ($pro) {
                        $amount = ($balance);
                        //                    if($amount != floor($balance)){
                        //                        session()->flash('error', Lang::get('app.withdraw.all'));
                        //                        return redirect()->back();
                        //                    }
                    }

                    if (($amount + $withdraw) > $maxwithdraw) {

                        return $this->sendError(Lang::get('app.withdraw.over').core()->currency($maxwithdraw).' / วัน วงเงินถอนคงเหลือ '.$today_wd, 200);

                    }

                    $response = $this->withdrawRepository->withdrawSingle($id, $amount);
                    if ($response['success'] === true) {
                        session()->flash('success', 'คุณทำรายการแจ้งถอนเงิน สำเร็จแล้ว');
                    } else {
                        session()->flash('error', $response['msg']);
                    }

                }

            }

            return redirect()->back();

        }
    }

    public function store_api(Request $request)
    {
        $datenow = now();
        $today = $datenow->toDateString();

        $request->validate([
            'amount' => 'required|numeric',
        ]);

        $config = core()->getConfigData();

        if($config->withdraw_status === 'N'){
            return $this->sendError('ขณะนี้ ไม่สามารถ แจ้งถอนเงินได้ ชั่วคราว ขออภัยในความไม่สะดวก', 200);
        }

        $member = $this->user();
        $id = $this->id();

        //        $lock = Cache::lock($this->id().':transfer:game', 30, $this->id());
        //        if (! $lock->get()) {
        //            //            session()->flash('error', 'รออีก 30 วินาที ค่อยทำรายการใหม่นะ');
        //            return $this->sendError('รออีก 30 วินาที ค่อยทำรายการใหม่นะ', 200);
        //        }

        //        if (Cache::has('transfer_'.$id)) {
        //            session()->flash('error', 'โปรดรอสักครู่ แล้วจึงทำรายการใหม่');
        //            return redirect()->back();
        //        }
        //
        //        Cache::put('transfer_'.$id, 'lock', now()->addSeconds(10));

        if ($config->seamless == 'Y') {
            $withdraw_today = $this->memberRepository->sumWithdrawSeamless($id, $today)->withdraw_seamless_amount_sum;
        } else {
            $withdraw_today = $this->memberRepository->sumWithdraw($id, $today)->withdraw_amount_sum;
        }

        //        dd($withdraw_today);
        $withdraw = (is_null($withdraw_today) ? 0 : $withdraw_today);

        $amount = (floatval($request->input('amount')));
        $balance = $member['balance'];

        if ($member->maxwithdraw_day == 0) {
            $maxwithdraw = $config->maxwithdraw_day;
        } else {
            $maxwithdraw = $member->maxwithdraw_day;
        }

        $today_wd = ($maxwithdraw - $withdraw);

        if ($amount < 1) {

            //            session()->flash('error', Lang::get('app.withdraw.credit_wrong'));
            //            return redirect()->back();
            return $this->sendError(Lang::get('app.withdraw.credit_wrong'), 200);

        } elseif ($balance < $amount) {

            //            session()->flash('error', Lang::get('app.withdraw.credit_over'));
            //            return redirect()->back();
            return $this->sendError(Lang::get('app.withdraw.credit_over'), 200);

        } elseif ($amount < $config['minwithdraw']) {

            //            session()->flash('error', Lang::get('app.withdraw.min'). core()->currency($config['minwithdraw']));
            //            return redirect()->back();
            return $this->sendError(Lang::get('app.withdraw.min').core()->currency($config['minwithdraw']), 200);

        } elseif (($amount + $withdraw) > $maxwithdraw) {

            //            session()->flash('error', Lang::get('app.withdraw.over') . core()->currency($config['maxwithdraw_day']).' / วัน วงเงินถอนคงเหลือ '.$today_wd);
            //            return redirect()->back();
            return $this->sendError(Lang::get('app.withdraw.over').core()->currency($maxwithdraw).' / วัน วงเงินถอนคงเหลือ '.$today_wd, 200);
        } else {

            if ($config->seamless == 'Y') {

                $chk = $this->withdrawSeamlessRepository->findOneWhere(['member_code' => $id, 'status' => 0, 'enable' => 'Y']);
                if ($chk) {
                    //                    session()->flash('error', Lang::get('app.withdraw.dup2'));
                    //                    return redirect()->back();
                    return $this->sendError(Lang::get('app.withdraw.dup2'), 200);
                }

                $user = app('Gametech\Game\Repositories\GameUserRepository')->findOneByField('member_code', $this->id());
                if ($user->amount_balance > 0) {
                    $pro = true;
                } else {
                    $pro = false;
                }

                if ($config->wallet_withdraw_all == 'Y') {
                    $pro = true;
                }

                if ($pro) {
                    $amount = ($balance);
                    //                    if($amount != floor($balance)){
                    //                        session()->flash('error', Lang::get('app.withdraw.all'));
                    //                        return redirect()->back();
                    //                    }
                }

                $response = $this->withdrawSeamlessRepository->withdrawSeamless($id, $amount);
                if ($response['success'] === true) {
                    //                    session()->flash('success', $response['msg']);
                    return $this->sendSuccess($response['msg']);
                } else {
                    //                    session()->flash('error', $response['msg']);
                    return $this->sendError($response['msg'], 200);
                }

            } else {
                if ($config->multigame_open == 'Y') {

                    $chk = $this->withdrawRepository->findOneWhere(['member_code' => $id, 'status' => 0, 'enable' => 'Y']);
                    if ($chk) {
                        //                        session()->flash('error', 'ไม่สามารถแจ้งถอนซ้ำได้ เนื่องจากคุณได้แจ้งถอนไปแล้ว โปรดรอทีมงาน ตรวจสอบ');
                        //                        return redirect()->back();
                        return $this->sendError(Lang::get('app.withdraw.dup2'), 200);
                    }

                    $response = $this->withdrawRepository->withdraw($id, $amount);
                    if ($response) {
                        return $this->sendSuccess($response['msg']);
                        //                        session()->flash('success', 'คุณทำรายการแจ้งถอนเงินเครดิต สำเร็จแล้ว');
                    } else {
                        //                        session()->flash('error', 'พบข้อผิดพลาด ไม่สามารถทำรายการได้');

                        return $this->sendError($response['msg'], 200);

                    }
                } else {

                    $chk = $this->withdrawRepository->findOneWhere(['member_code' => $id, 'status' => 0, 'enable' => 'Y']);
                    if ($chk) {
                        //                        session()->flash('error', 'ไม่สามารถแจ้งถอนซ้ำได้ เนื่องจากคุณได้แจ้งถอนไปแล้ว โปรดรอทีมงาน ตรวจสอบ');
                        //                        return redirect()->back();
                        return $this->sendError(Lang::get('app.withdraw.dup2'), 200);
                    }

                    //                    $user = app('Gametech\Game\Repositories\GameUserRepository')->getOneUser($this->id(),1,true);
                    $user = app('Gametech\Game\Repositories\GameUserRepository')->findOneByField('member_code', $this->id());
                    if ($user->amount_balance > 0 || $user->pro_code > 0) {
                        $pro = true;
                    } else {
                        $pro = false;
                    }

                    if ($config->wallet_withdraw_all === 'Y') {
                        $pro = true;
                    }

                    if ($pro) {
                        $amount = ($user->balance);
                        //                    if($amount != floor($balance)){
                        //                        session()->flash('error', Lang::get('app.withdraw.all'));
                        //                        return redirect()->back();
                        //                    }
                    }

                    if (($amount + $withdraw) > $maxwithdraw) {

                        return $this->sendError(Lang::get('app.withdraw.over').core()->currency($maxwithdraw).' / วัน วงเงินถอนคงเหลือ '.$today_wd, 200);

                    }

                    $response = $this->withdrawRepository->withdrawSingle($id, $amount);
                    if ($response['success'] === true) {
                        //                        session()->flash('success', 'คุณทำรายการแจ้งถอนเงิน สำเร็จแล้ว');
                        return $this->sendSuccess($response['msg']);
                    } else {
                        //                        session()->flash('error', $response['msg']);
                        return $this->sendError($response['msg'], 200);
                    }

                }

            }

            return $this->sendError('ผิดพลาด');

        }
    }
}
