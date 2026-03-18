<?php

namespace Gametech\Wallet\Http\Controllers;


use Gametech\Member\Repositories\MemberRepository;
use Gametech\Member\Repositories\MemberTransferRepository;
use Illuminate\Http\Request;


class MoneyController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    private $memberTransferRepository;

    private $memberRepository;


    /**
     * Create a new Repository instance.
     *
     * @param MemberTransferRepository $memberTransferRepos
     * @param MemberRepository $memberRepo
     */
    public function __construct
    (
        MemberTransferRepository $memberTransferRepos,
        MemberRepository $memberRepo
    )
    {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->memberTransferRepository = $memberTransferRepos;

        $this->memberRepository = $memberRepo;
    }

    public function index()
    {
        $profile = $this->user();
        $user = app('Gametech\Game\Repositories\GameUserRepository')->findOneByField('member_code',$this->id());
        $turnpro = $user->amount_balance;

        return view($this->_config['view'], compact('profile','turnpro'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'to_member_code' => 'required'
        ]);

        $amount = floatval($request->input('amount'));
        $to = $request->input('to_member_code');

        $chk = $this->memberRepository->findOneByField('tel',$to);
        if(!$chk){
            session()->flash('error', 'ไม่พบข้อมูลสมาชิก');
            return redirect()->back();
        }

        $response = $this->memberTransferRepository->moneyTransfer($this->id(),$chk->code,$amount);
        if ($response['success'] === true) {
            session()->flash('success', $response['message']);
        } else {
            session()->flash('error', $response['message']);
        }

        return redirect()->back();
    }


}
