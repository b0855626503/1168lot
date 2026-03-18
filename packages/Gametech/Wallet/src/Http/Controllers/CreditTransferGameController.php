<?php

namespace Gametech\Wallet\Http\Controllers;


use Gametech\Core\Repositories\ConfigRepository;
use Gametech\Game\Repositories\GameRepository;
use Gametech\Game\Repositories\GameUserFreeRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BillFreeRepository;
use Gametech\Promotion\Repositories\PromotionRepository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class CreditTransferGameController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;


    protected $memberRepository;

    protected $configRepository;

    protected $gameUserFreeRepository;

    protected $promotionRepository;

    protected $billFreeRepository;

    protected $gameRepository;


    /**
     * Create a new Repository instance.
     *
     * @param MemberRepository $memberRepo
     * @param ConfigRepository $configRepo
     * @param GameUserFreeRepository $gameUserFreeRepo
     * @param PromotionRepository $promotionRepo
     * @param GameRepository $gameRepo
     * @param BillFreeRepository $billFreeRepo
     */
    public function __construct
    (
        MemberRepository $memberRepo,
        ConfigRepository $configRepo,
        GameUserFreeRepository $gameUserFreeRepo,
        PromotionRepository $promotionRepo,
        GameRepository $gameRepo,
        BillFreeRepository $billFreeRepo
    )
    {
        $this->middleware('customer');

        $this->_config = request('_config');


        $this->memberRepository = $memberRepo;

        $this->configRepository = $configRepo;

        $this->gameUserFreeRepository = $gameUserFreeRepo;

        $this->promotionRepository = $promotionRepo;

        $this->billFreeRepository = $billFreeRepo;

        $this->gameRepository = $gameRepo;
    }

    public function index()
    {

        $promotions = [];

        $games = $this->loadGame();

        $games = $games->map(function ($items) {
            $item = (object)$items;
            return [
                'code' => $item->code,
                'name' => $item->name,
                'image' => Storage::url('game_img/' . $item->filepic),
                'balance' => $item->game_user_free['balance']
            ];

        });


        $profile = $this->user()->load('bank');

        return view($this->_config['view'], compact('profile', 'promotions'))->with('games', $games);
    }

    public function loadGame(): Collection
    {
        return collect($this->gameRepository->getGameUserFreeById($this->id(), false)->toArray())->whereNotNull('game_user_free');
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
        $balance = $this->user()->balance_free;


        $getdata = $this->gameUserFreeRepository->getOneUser($this->id(), $game_id);
        if ($getdata['success'] === false) {
            session()->flash('error', $getdata['msg']);
            return redirect()->route('customer.credit.transfer.game.index');
        }

        $game = $getdata['data'];

        if (!$this->checkCondition($amount, $balance, $game, $this->user())) {
            return redirect()->route('customer.credit.transfer.game.index');
        }


        $item = $this->checkPro($game, $this->id(), $promotion_id, $amount, $balance);

        $param = ['game' => $item['game_code'], 'id' => $this->id(), 'promotion' => $item['pro_code'], 'amount' => $amount, 'datetime' => now()];


        session()->flash('gametoken', Crypt::encryptString(json_encode($param)));

        return view($this->_config['view'])->with('item', $item);

    }

    public function confirm(Request $request): RedirectResponse
    {

        if (!$request->has('gametoken')) {
            session()->flash('error', 'พบข้อผิดพลาดบางประการ โปรดทำรายการใหม่อีกครั้ง');
            return redirect()->route('customer.credit.transfer.game.index');
        }

//        $lock = Cache::lock('transfer_credit_out', 5);
//
//        if ($lock->get()){
//            $lock->release();
//            session()->flash('error', 'รออีก 5 วินาที ค่อยทำการโยกใหม่นะ');
//            return redirect()->route('customer.credit.transfer.game.index');
//        }

        $encrypted = $request->input('gametoken');

        try {
            $gamedata = Crypt::decryptString($encrypted);
        } catch (DecryptException $e) {
            session()->flash('error', 'พบข้อผิดพลาดบางประการ โปรดทำรายการใหม่อีกครั้ง');
            return redirect()->route('customer.credit.transfer.game.index');
        }


        $gamedata = json_decode($gamedata, true);


        $user_id = $gamedata['id'];

        if ($user_id !== $this->id()) {
            session()->flash('error', 'ทำรายการไม่ถูกต้อง โปรดทำรายการใหม่อีกครั้ง');
            return redirect()->route('customer.credit.transfer.game.index');
        }

        $game_id = $gamedata['game'];
        $promotion_id = $gamedata['promotion'];
        $amount = $gamedata['amount'];
        $balance = $this->user()->balance_free;


        $getdata = $this->gameUserFreeRepository->getOneUser($this->id(), $game_id);
        if ($getdata['success'] === false) {
            session()->flash('error', $getdata['msg']);
            return redirect()->route('customer.credit.transfer.game.index');
        }

        $game = $getdata['data'];

        if (!$this->checkCondition($amount, $balance, $game, $this->user())) {
            return redirect()->route('customer.credit.transfer.game.index');
        }

        $item = $this->checkPro($game, $this->id(), $promotion_id, $amount, $balance);


        if (Cache::has('transfer_'.$user_id)) {
            session()->flash('error', 'รออีก 10 วินาที ค่อยทำการโยกใหม่นะ');
            return redirect()->route('customer.credit.transfer.game.index');
        }

        Cache::put('transfer_'.$user_id, 'lock', now()->addSeconds(10));

        $response = $this->billFreeRepository->transferGame($item);
        if ($response['success'] === false) {
            session()->flash('error', $response['msg']);
            return redirect()->route('customer.credit.transfer.game.index');
        }

        $bills = $response['data'];

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
            'date_create' => core()->formatDate($bills['date_create'], 'd/m/y H:i:s')
        ]);



        session()->flash('bills', $bills);
        return redirect()->route('customer.credit.transfer.game.complete');
    }

    public function complete()
    {
        if (!$item = session('bills')) {
            return redirect()->route('customer.credit.transfer.game.index');
        }


        return view($this->_config['view'], compact('item'));
    }

    public function checkCondition($amount, $balance, $game_user, $member): bool
    {

        $config = core()->getConfigData();


        if ($amount < 1) {
            session()->flash('error', 'กรอกจำนวนเงินไม่ถูกต้อง โปรดใช้ตัวเลข และไม่น้อยกว่า 0');
            return false;

        } elseif ($balance < $amount) {

            session()->flash('error', 'กรอกจำนวนเงินที่โยกเข้า มากกว่ายอด Cashback ที่มีอยู่');
            return false;

        } elseif ($amount < $config['free_mintransfer']) {

            session()->flash('error', 'ยอดเงินขั้นต่ำในการโยกเงินเข้าเกม คือ : ' . $config['free_mintransfer']);
            return false;

        } elseif ($amount > $config['free_maxtransfer']) {

            session()->flash('error', 'ยอดเงินสูงสุดในการโยกเงินเข้าเกม คือ : ' . $config['free_maxtransfer']);
            return false;

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
            'game_balance' => $game->balance,
            'amount' => $amount,
            'bonus' => $promotion['bonus'],
            'total' => $promotion['total'],
            'wallet' => Storage::url('game_img/wallet.png'),
        ];

    }


}
