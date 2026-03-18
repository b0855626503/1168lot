<?php

namespace Gametech\Wallet\Http\Controllers;


use Gametech\Core\Repositories\ConfigRepository;
use Gametech\Game\Repositories\GameRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BillRepository;
use Gametech\Promotion\Repositories\PromotionRepository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class TransferWalletController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;


    private $memberRepository;

    private $configRepository;

    private $gameUserRepository;

    private $promotionRepository;

    private $billRepository;

    private $gameRepository;


    /**
     * Create a new Repository instance.
     *
     * @param MemberRepository $memberRepo
     * @param ConfigRepository $configRepo
     * @param GameUserRepository $gameUserRepo
     * @param PromotionRepository $promotionRepo
     * @param GameRepository $gameRepo
     * @param BillRepository $billRepo
     */
    public function __construct
    (
        MemberRepository    $memberRepo,
        ConfigRepository    $configRepo,
        GameUserRepository  $gameUserRepo,
        PromotionRepository $promotionRepo,
        GameRepository      $gameRepo,
        BillRepository      $billRepo
    )
    {
        $this->middleware('customer');

        $this->_config = request('_config');


        $this->memberRepository = $memberRepo;

        $this->configRepository = $configRepo;

        $this->gameUserRepository = $gameUserRepo;

        $this->promotionRepository = $promotionRepo;

        $this->billRepository = $billRepo;

        $this->gameRepository = $gameRepo;
    }

    public function index()
    {
        $pro = false;
        $promotions = [];
        $config = core()->getConfigData();
        if (($config->pro_onoff == 'Y')) {
            if ($config->pro_wallet == 'Y') {
                $pro = true;
            } elseif ($this->user()->promotion == 'Y') {
                $pro = true;
            }
        }
        $pro = false;

        if ($pro) {
            $pro_limit = $this->memberRepository->getPro($this->id());
            if ($pro_limit > 0) {
                $promotions = $this->promotionRepository->loadPromotion($this->id());
            }
        }


        $games = $this->loadGame();
        $games = $games->map(function ($items) {
            $item = (object)$items;
            return [
                'code' => $item->code,
                'name' => $item->name,
                'image' => Storage::url('game_img/' . $item->filepic),
                'balance' => $item->game_user['balance']
            ];

        });


        $profile = $this->user()->load('bank');

        return view($this->_config['view'], compact('profile', 'promotions'))->with('games', $games);
    }

    public function loadGame(): Collection
    {
        return collect($this->gameRepository->getGameUserById($this->id(), false)->toArray())->whereNotNull('game_user');
    }


    public function check(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'game' => 'required|integer'
        ]);


        $game_id = $request->input('game');
        $promotion_id = $request->input('promotion');
        if ($promotion_id == '') {
            $promotion_id = null;
        }
        $amount = $request->input('amount');
        $balance = $this->user()->balance;


        $getdata = $this->gameUserRepository->getOneUser($this->id(), $game_id);
        if ($getdata['success'] === false) {
            session()->flash('error', $getdata['msg']);
            return redirect()->route('customer.transfer.wallet.index');
        }

        $game = $getdata['data'];

        if (!$this->checkCondition($amount, $balance, $game, $this->user())) {
            return redirect()->route('customer.transfer.wallet.index');
        }


        $item = $this->checkPro($game, $this->id(), $promotion_id, $amount, $balance);

        $param = ['game' => $item['game_code'], 'id' => $this->id(), 'promotion' => $item['pro_code'], 'amount' => $amount, 'datetime' => now()];

        $request->session()->put('gametoken', Crypt::encryptString(json_encode($param)));
//        session()->flash('gametoken', Crypt::encryptString(json_encode($param)));

        return view($this->_config['view'])->with('item', $item);

    }

    public function confirm(Request $request): RedirectResponse
    {

        if (!$request->session()->has('gametoken')) {
            session()->flash('error', 'พบข้อผิดพลาดบางประการ โปรดทำรายการใหม่อีกครั้ง');
            return redirect()->route('customer.transfer.wallet.index');
        }

//        $lock = Cache::lock('transfer_in');
//
//        try {
//            $lock->block(5);
//
//        } catch (LockTimeoutException $e) {
//            session()->flash('error', 'รออีก 5 วินาที ค่อยทำการโยกใหม่นะ');
//            return redirect()->route('customer.transfer.wallet.index');
//        } finally {
//            optional($lock)->release();
//        }


        $encrypted = $request->session()->get('gametoken');

        try {
            $gamedata = Crypt::decryptString($encrypted);
        } catch (DecryptException $e) {
            session()->flash('error', 'พบข้อผิดพลาดบางประการ โปรดทำรายการใหม่อีกครั้ง');
            return redirect()->route('customer.transfer.wallet.index');
        }


        $gamedata = json_decode($gamedata, true);


        $user_id = $gamedata['id'];

        if ($user_id !== $this->id()) {
            session()->flash('error', 'ทำรายการไม่ถูกต้อง โปรดทำรายการใหม่อีกครั้ง');
            return redirect()->route('customer.transfer.wallet.index');
        }

        $game_id = $gamedata['game'];
        $promotion_id = $gamedata['promotion'];
        $amount = $gamedata['amount'];
        $balance = $this->user()->balance;


        $getdata = $this->gameUserRepository->getOneUser($this->id(), $game_id);
        if ($getdata['success'] === false) {
            session()->flash('error', $getdata['msg']);
            return redirect()->route('customer.transfer.wallet.index');
        }

        $game = $getdata['data'];

        if (!$this->checkCondition($amount, $balance, $game, $this->user())) {
            return redirect()->route('customer.transfer.wallet.index');
        }

        $item = $this->checkPro($game, $this->id(), $promotion_id, $amount, $balance);


        if (Cache::has('transfer_' . $user_id)) {
            session()->flash('error', 'รออีก 30 วินาที ค่อยทำการโยกใหม่นะ');
            return redirect()->route('customer.transfer.wallet.index');
        }

        Cache::put('transfer_' . $user_id, 'lock', now()->addSeconds(30));

        $response = $this->billRepository->transferWallet($item);
        if ($response['success'] === false) {
            session()->flash('error', $response['msg']);
            return redirect()->route('customer.transfer.wallet.index');
        }

        $bills = $response['data'];
//        dd(collect($bills));
        $bills = collect($bills)->only('code', 'date_create', 'credit_before', 'credit_after', 'balance_after', 'balance_before');
        $bills = $bills->merge([
            'invoice' => '#BL' . Str::of($bills['code'])->padLeft(8, 0),
            'game_name' => $item['game_name'],
            'game_pic' => $item['game_pic'],
            'pro_code' => $item['pro_code'],
            'pro_name' => $item['pro_name'],
            'amount' => $item['amount'],
            'bonus' => $item['bonus'],
            'total' => $item['total'],
            'game_before' => $bills['credit_before'],
            'game_after' => $bills['credit_after'],
            'wallet_before' => $bills['balance_before'],
            'wallet_after' => $bills['balance_after'],
            'wallet' => $item['wallet'],
            'date_create' => core()->formatDate($bills['date_create'], 'd/m/Y H:i:s')
        ]);

//        dd($bills);


        session()->flash('bills', $bills);
        return redirect()->route('customer.transfer.wallet.complete');
    }

    public function complete()
    {
        if (!$item = session('bills')) {
            return redirect()->route('customer.transfer.wallet.index');
        }


        return view($this->_config['view'], compact('item'));
    }

    public function checkCondition($amount, $balance, $game_user, $member): bool
    {
        $pro = false;
        $config = core()->getConfigData();

        if ($config['pro_onoff'] == 'N') {
            session()->flash('error', 'ขณะนี้ การรับโปรโมชั่น ยังไม่เปิดให้บริการ ขออภัยในความไม่สะดวก');
            return false;
        }

        if ($config->pro_wallet == 'Y') {
            $pro = true;
        } elseif ($member->promotion == 'Y') {
            $pro = true;
        }


        if ($amount < 1) {
            session()->flash('error', 'กรอกจำนวนเงินไม่ถูกต้อง โปรดใช้ตัวเลข และไม่น้อยกว่า 0');
            return false;

        } elseif ($amount < $config['mintransferback']) {

            session()->flash('error', 'ยอดเงินขั้นต่ำในการโยกเงินออกเกม คือ : ' . $config['mintransferback']);
            return false;

        } elseif ($game_user->balance < $amount) {

            session()->flash('error', 'กรอกจำนวนเงินที่โยกออกเกม มากกว่ายอดที่มีอยู่');
            return false;
        }

//        if ($pro) {
//            $turn = DB::table('view_billlast')->where('member_code', $member->code)->where('game_code', $game_user->game->code);
//            if ($turn->exists()) {
//                if ($turn->first()->pro_code > 0) {
//                    if ($game_user->balance < $turn->first()->amount_balance) {
//                        session()->flash('error', 'ยังไม่สามารถ โยเงินออกจากเกมได้ เนื่องจากยังทำไม่ผ่านเงื่อนไขโปรโมชั่น');
//                        return false;
//                    }
//                }
//            }
//        }

        if ($pro) {
            if ($game_user->pro_code > 0) {
                if ($game_user->balance < $game_user->amount_balance) {
                    session()->flash('error', 'ยังไม่สามารถ โยกเงินออกจากเกมได้ เนื่องจากยังทำไม่ผ่านเงื่อนไขโปรโมชั่น');
                    return false;
                }
            }
        }

        return true;

    }


    public function checkPro($game, $id, $promotion_id, $amount, $balance): array
    {
        $promotion = [
            'pro_code' => 0,
            'pro_name' => '',
            'bonus' => 0,
            'total' => $amount,
        ];

        $datenow = now();
        $today = now()->toDateString();

        if (!is_null($promotion_id)) {

            $pro_limit = $this->memberRepository->getPro($id);

            if ($pro_limit >= $amount) {

                switch ($promotion_id) {
                    case 1:

                        if ($this->user()->status_pro == 0) {
                            $promotion = $this->promotionRepository->checkPromotion($promotion_id, $amount, $datenow);
                        }

                        break;

                    case 2:
                        if ($this->promotionRepository->checkProFirstDay($this->id()) == 0) {
                            $promotion = $this->promotionRepository->checkPromotion($promotion_id, $amount, $datenow);
                        }
                        break;

                    case 4:
                    case 7:
                        $promotion = $this->promotionRepository->checkPromotion($promotion_id, $amount, $datenow);
                        break;

                    case 5:
                        if ($this->promotionRepository->checkHotTime($today, '00:00', '00:01', $datenow)) {
                            $promotion = $this->promotionRepository->checkPromotion($promotion_id, $amount, $datenow);
                        }
                        break;

                    default:
                        $promotion = [
                            'pro_code' => 0,
                            'pro_name' => '',
                            'bonus' => 0,
                            'total' => $amount,
                        ];
                        break;

                }
            }
        }

        return [
            'member_code' => $id,
            'member_balance' => $balance,
            'game_code' => $game->game->code,
            'game_name' => $game->game->name,
            'game_pic' => Storage::url('game_img/' . $game->game->filepic),
            'user_code' => $game->code,
            'user_name' => $game->user_name,
            'pro_code' => $promotion['pro_code'],
            'pro_name' => $promotion['pro_name'],
            'withdraw_limit' => $game->withdraw_limit,
            'game_balance' => $game->balance,
            'amount' => $amount,
            'bonus' => $promotion['bonus'],
            'total' => $promotion['total'],
            'wallet' => Storage::url('game_img/wallet.png'),
        ];

    }

    public function bonus(Request $request)
    {
        $id = $request->input('id');
        $member = $this->user();
        $data['member_code'] = $member->code;
//        dd('here');

        $config = core()->getConfigData();

        if ($config->freecredit_open == 'Y') {

            if ($member->balance_free > $config->pro_reset) {
                return $this->sendError('ไม่สามารถทำรายการได้ โยกเข้าได้เมื่อยอดเครดิต น้อยกว่าหรือเท่ากับ ' . $config->pro_reset, 200);

            }

            $response = app('Gametech\Member\Repositories\MemberCreditFreeLogRepository')->tranBonus($data, $id);
            if ($response) {
                return $this->sendSuccess('ดำเนินการโยก เข้ากระเป๋าสำเร็จแล้ว');
            } else {
                return $this->sendError('ไม่สามารถทำรายการได้ โปรดลองใหม่ในภายหลัง', 200);
            }
        } else {

            if ($member->balance > $config->pro_reset) {
                return $this->sendError('ไม่สามารถทำรายการได้ โยกเข้าได้เมื่อยอดเครดิต น้อยกว่าหรือเท่ากับ ' . $config->pro_reset, 200);

            }

            $response = app('Gametech\Member\Repositories\MemberCreditLogRepository')->tranBonus($data, $id);
            if ($response) {
                return $this->sendSuccess('ดำเนินการโยก เข้ากระเป๋าสำเร็จแล้ว');
            } else {
                return $this->sendError('ไม่สามารถทำรายการได้ โปรดลองใหม่ในภายหลัง', 200);
            }
        }


    }


}
