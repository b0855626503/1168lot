<?php

namespace Gametech\Wallet\Http\Controllers;


use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\WithdrawFreeRepository;
use Gametech\Payment\Repositories\WithdrawSeamlessFreeRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;


class CreditWithdrawController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected $memberRepository;

    protected $withdrawFreeRepository;

    protected $withdrawSeamlessFreeRepository;


    /**
     * Create a new Repository instance.
     *
     * @param MemberRepository $memberRepo
     * @param WithdrawFreeRepository $withdrawFreeRepo
     */
    public function __construct
    (
        MemberRepository               $memberRepo,
        WithdrawFreeRepository         $withdrawFreeRepo,
        WithdrawSeamlessFreeRepository $withdrawSeamlessFreeRepo
    )
    {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->memberRepository = $memberRepo;

        $this->withdrawFreeRepository = $withdrawFreeRepo;

        $this->withdrawSeamlessFreeRepository = $withdrawSeamlessFreeRepo;
    }

    public function index()
    {
        $config = core()->getConfigData();
        if ($config->seamless == 'Y') {

            $profile = $this->memberRepository->sumWithdrawSeamlessFree($this->id(), now()->toDateString());
            $user = app('Gametech\Game\Repositories\GameUserFreeRepository')->findOneByField('member_code', $this->id());
            if($user) {
                if ($user->amount_balance > 0) {
                    $pro = true;
                } else {
                    $pro = false;
                }

                $turnpro = $user->amount_balance;
            }else{
                $pro = false;
                $turnpro = 0;
            }

        } else {
            $profile = $this->memberRepository->sumWithdrawFree($this->id(), now()->toDateString());
            $pro = false;
            $turnpro = 0;
        }


        return view($this->_config['view'], compact('profile', 'pro' , 'turnpro'));
    }


    public function store(Request $request): RedirectResponse
    {
        $datenow = now();
        $today = $datenow->toDateString();

        $request->validate([
            'amount' => 'required|numeric'
        ]);

        $config = core()->getConfigData();


        $member = $this->user();
        $id = $this->id();
        if ($config->seamless == 'Y') {
            $withdraw_today = $this->memberRepository->sumWithdrawSeamlessFree($id, $today)->withdrawSeamlessFree_amount_sum;
        } else {
            $withdraw_today = $this->memberRepository->sumWithdrawFree($id, $today)->withdraw_free_amount_sum;
        }
        $withdraw = (is_null($withdraw_today) ? 0 : $withdraw_today);


        $amount = floatval($request->input('amount'));
        $balance = $member['balance_free'];

        if ($amount < 1) {

            session()->flash('error', 'พบข้อผิดพลาด คุณป้อนจำนวนไม่ถูกต้อง');
            return redirect()->back();

        } elseif ($balance < $amount) {

            session()->flash('error', 'ไม่สามารถดำเนินการได้ จำนวนเงินไม่เพียงพอ');
            return redirect()->back();

        } elseif ($amount < $config['free_minwithdraw']) {

            session()->flash('error', 'ไม่สามารถดำเนินการได้ ยอดถอนขั้นต่ำ ' . core()->currency($config['free_minwithdraw']) . ' บาท');
            return redirect()->back();

        } elseif (($amount + $withdraw) > $config['free_maxwithdraw']) {

            session()->flash('error', 'ไม่สามารถดำเนินการได้ ยอดถอนสูงสุด ' . core()->currency($config['free_maxwithdraw']) . ' บาท');
            return redirect()->back();

        } else {

            if ($config->seamless == 'Y') {

                $chk = $this->withdrawSeamlessFreeRepository->findOneWhere(['member_code' => $id, 'status' => 0, 'enable' => 'Y']);
                if ($chk) {
                    session()->flash('error', 'ไม่สามารถแจ้งถอนซ้ำได้ เนื่องจากคุณได้แจ้งถอนไปแล้ว โปรดรอทีมงาน ตรวจสอบ');
                    return redirect()->back();
                }

                $response = $this->withdrawSeamlessFreeRepository->withdrawSeamless($id, $amount);
                if ($response['success'] === true) {
                    session()->flash('success', $response['msg']);
                } else {
                    session()->flash('error', $response['msg']);
                }

            } else {
                if($config->multigame_open == 'Y'){

                    $chk = $this->withdrawFreeRepository->findOneWhere(['member_code' => $id, 'status' => 0, 'enable' => 'Y']);
                    if ($chk) {
                        session()->flash('error', 'ไม่สามารถแจ้งถอนซ้ำได้ เนื่องจากคุณได้แจ้งถอนไปแล้ว โปรดรอทีมงาน ตรวจสอบ');
                        return redirect()->back();
                    }

                    $response = $this->withdrawFreeRepository->withdraw($id, $amount);
                    if ($response) {
                        session()->flash('success', 'คุณทำรายการแจ้งถอนเงินฟรีเครดิต สำเร็จแล้ว');
                    } else {
                        session()->flash('error', 'พบข้อผิดพลาด ไม่สามารถทำรายการได้');

                    }
                }else{

                    $chk = $this->withdrawFreeRepository->findOneWhere(['member_code' => $id, 'status' => 0, 'enable' => 'Y']);
                    if ($chk) {
                        session()->flash('error', 'ไม่สามารถแจ้งถอนซ้ำได้ เนื่องจากคุณได้แจ้งถอนไปแล้ว โปรดรอทีมงาน ตรวจสอบ');
                        return redirect()->back();
                    }
                    $response = $this->withdrawFreeRepository->withdrawSingle($id, $amount);
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


}
