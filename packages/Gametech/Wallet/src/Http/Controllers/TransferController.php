<?php

namespace Gametech\Wallet\Http\Controllers;



use Gametech\Core\Repositories\ConfigRepository;
use Gametech\Game\Repositories\GameRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BillRepository;
use Gametech\Promotion\Repositories\PromotionRepository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class TransferController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;


    protected $memberRepository;

    protected $configRepository;

    protected $gameUserRepository;

    protected $promotionRepository;

    protected $billRepository;

    protected $gameRepository;


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
        MemberRepository $memberRepo,
        ConfigRepository $configRepo,
        GameUserRepository $gameUserRepo,
        PromotionRepository $promotionRepo,
        GameRepository $gameRepo,
        BillRepository $billRepo
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
        $promotions = [];
        $config = core()->getConfigData();
        if($config->pro_onoff == 'Y'){
            $pro_limit = $this->memberRepository->getPro($this->id());
            if ($pro_limit > 0) {
                $promotions = $this->promotionRepository->loadPromotion($this->id());
            }
        }


        $games = $this->loadGame();
        $games = $games->map(function ($items){
            $item = (object)$items;
            return [
                'code' => $item->code,
                'name' => $item->name,
                'image' =>  Storage::url('game_img/'.$item->filepic),
                'balance' => $item->game_user['balance']
            ];

        });


        $profile = $this->user()->load('bank');

        return view($this->_config['view'], compact('profile','promotions'))->with('games',$games);
    }

    public function loadGame()
    {
        return collect($this->gameRepository->getGameUserById($this->id(),false)->toArray())->whereNotNull('game_user');

    }



    public function check(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'game' => 'required|integer',
            'promotion' => 'integer'
        ]);


        $game_id = $request->input('game');
        $promotion_id = $request->input('promotion');
        $amount = $request->input('amount');
        $balance = $this->user()->balance;

        if(!$this->checkCondition($amount,$balance)){
            return redirect()->route('customer.transfer.game.index');
        }

        $getdata = $this->gameUserRepository->getOneUser($this->id(),$game_id);
        if($getdata['success'] === false){
            session()->flash('error', $getdata['msg']);
            return redirect()->route('customer.transfer.game.index');
        }

        $game = $getdata['data'];

        $item = $this->checkPro($game,$this->id(),$promotion_id,$amount,$balance);

        $param = [ 'game' => $item['game_code'] , 'id' => $this->id() , 'promotion' => $item['pro_code'] , 'amount' => $amount , 'datetime' => now()];


        session()->flash('gametoken', Crypt::encryptString(json_encode($param)));

        return view($this->_config['view'])->with('item',$item);

    }

    public function confirm(Request $request)
    {

        if(!$request->has('gametoken')){
            session()->flash('error', 'พบข้อผิดพลาดบางประการ โปรดทำรายการใหม่อีกครั้ง');
            return redirect()->route('customer.transfer.game.index');
        }


        $encrypted = $request->input('gametoken');

        try {
            $gamedata = Crypt::decryptString($encrypted);
        } catch (DecryptException $e) {
            session()->flash('error', 'พบข้อผิดพลาดบางประการ โปรดทำรายการใหม่อีกครั้ง');
            return redirect()->route('customer.transfer.game.index');
        }


        $gamedata = json_decode($gamedata,true);


        $user_id = $gamedata['id'];

        if($user_id !== $this->id()){
            session()->flash('error', 'ทำรายการไม่ถูกต้อง โปรดทำรายการใหม่อีกครั้ง');
            return redirect()->route('customer.transfer.game.index');
        }

        $game_id = $gamedata['game'];
        $promotion_id = $gamedata['promotion'];
        $amount = $gamedata['amount'];
        $balance = $this->user()->balance;

        if(!$this->checkCondition($amount,$balance)){
            return redirect()->route('customer.transfer.game.index');
        }

        $getdata = $this->gameUserRepository->getOneUser($this->id(),$game_id);
        if($getdata['success'] === false){
            session()->flash('error', $getdata['msg']);
            return redirect()->route('customer.transfer.game.index');
        }

        $game = $getdata['data'];

        $item = $this->checkPro($game,$this->id(),$promotion_id,$amount,$balance);


        $response = $this->billRepository->transferGame($item);
        if($response['success'] === false){
            session()->flash('error', $response['msg']);
            return redirect()->route('customer.transfer.game.index');
        }

        $bills = $response['data'];
//        dd(collect($bills));
        $bills = collect($bills)->only('code','date_create');
        $bills = $bills->merge([
            'invoice' => '#BL'.Str::of($bills['code'])->padLeft(8,0),
            'game_name' => $item['game_name'],
            'game_pic' => $item['game_pic'],
            'pro_code' => $item['pro_code'],
            'pro_name' => $item['pro_name'],
            'amount' => $item['amount'],
            'bonus' => $item['bonus'],
            'total' => $item['total'],
            'date_create' => core()->formatDate($bills['date_create'],'d/m/Y H:i:s')
        ]);

//        dd($bills);

        session()->flash('bills', $bills);
        return redirect()->route('customer.transfer.game.complete');
    }

    public function complete()
    {
        if (! $item = session('bills')) {
            return redirect()->route('customer.transfer.game.index');
        }


        return view($this->_config['view'], compact('item'));
    }

    public function checkCondition($amount,$balance)
    {
        $config = core()->getConfigData();

        if ($config['pro_onoff'] == 'N') {
            session()->flash('error', 'ขณะนี้ การรับโปรโมชั่น ยังไม่เปิดให้บริการ ขออภัยในความไม่สะดวก');
            return false;
        }

        if ($amount < 1) {
            session()->flash('error', 'กรอกจำนวนเงินไม่ถูกต้อง โปรดใช้ตัวเลข และไม่น้อยกว่า 0');
            return false;

        } elseif ($balance < $amount) {

            session()->flash('error', 'กรอกจำนวนเงินที่โยก มากกว่ายอด Wallet ที่มีอยู่');
            return false;

        } elseif ($amount < $config['mintransfer']) {

            session()->flash('error', 'ยอดเงินขั้นต่ำในการโยกเงินเข้าเกม คือ : '.$config['mintransfer']);
            return false;

        } elseif ($amount > $config['maxtransfer_time']) {

            session()->flash('error', 'ยอดเงินสูงสุดในการโยกเงินเข้าเกม คือ : '.$config['maxtransfer_time']);
            return false;

        }

      return true;

    }


    public function checkPro($game, $id, $promotion_id, $amount, $balance)
    {
        $promotion = [
            'pro_code' => 0,
            'pro_name' => '',
            'bonus' => 0,
            'total' => 0,
        ];

        $datenow = now();
        $today = now()->toDateString();

        if(!is_null($promotion_id)) {

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
                            'total' => 0,
                        ];
                        break;

                }
            }
        }

        return [
            'member_code' => $id,
            'member_balance' => core()->currency($balance),
            'game_code' => $game->game->code,
            'game_name' => $game->game->name,
            'game_pic' => Storage::url('game_img/'.$game->game->filepic),
            'user_code' => $game->code,
            'user_name' => $game->user_name,
            'pro_code' => $promotion['pro_code'],
            'pro_name' => $promotion['pro_name'],
            'game_balance' => core()->currency($game->balance),
            'amount' => core()->currency($amount),
            'bonus' => core()->currency($promotion['bonus']),
            'total' => core()->currency($promotion['total'])
        ];

    }


}
