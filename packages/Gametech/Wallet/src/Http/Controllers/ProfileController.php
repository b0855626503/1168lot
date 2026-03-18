<?php

namespace Gametech\Wallet\Http\Controllers;

use Gametech\API\Models\GameListProxy;
use Gametech\Game\Repositories\GameRepository;
use Gametech\Game\Repositories\GameSeamlessRepository;
use Gametech\Game\Repositories\GameTypeRepository;
use Gametech\Game\Repositories\GameUserFreeRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Prettus\Validator\Exceptions\ValidatorException;

class ProfileController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected $gameRepository;

    protected $memberRepository;

    protected $gameUserRepository;

    protected $gameUserFreeRepository;

    protected $gameTypeRepository;

    protected $gameSeamlessRepository;

    protected $bankPaymentRepository;

    /**
     * Create a new Repository instance.
     */
    public function __construct(
        MemberRepository       $memberRepo,
        GameRepository         $gameRepo,
        GameUserRepository     $gameUserRepo,
        GameUserFreeRepository $gameUserFreeRepo,
        GameTypeRepository     $gameTypeRepo,
        GameSeamlessRepository $gameSeamlessRepo,
        BankPaymentRepository  $bankPaymentRepo
    )
    {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->gameRepository = $gameRepo;

        $this->gameUserRepository = $gameUserRepo;

        $this->gameUserFreeRepository = $gameUserFreeRepo;

        $this->memberRepository = $memberRepo;

        $this->gameTypeRepository = $gameTypeRepo;

        $this->gameSeamlessRepository = $gameSeamlessRepo;

        $this->bankPaymentRepository = $bankPaymentRepo;

    }

    /** @noinspection PhpUndefinedMethodInspection */
    public function index()
    {
        $games = $this->loadGame();
        $games = $games->map(function ($items) {
            $item = (object)$items;

            return [
                'pass' => true,
                'code' => $item->code,
                'name' => $item->name,
                'image' => Storage::url('game_img/' . $item->filepic),
                'balance' => $item->game_user['balance'],
                'user_code' => $item->game_user['code'],
            ];

        });

        $gamesfree = $this->loadGameFree();
        $gamesfree = $gamesfree->map(function ($items) {
            $item = (object)$items;

            return [
                'pass' => true,
                'code' => $item->code,
                'name' => $item->name,
                'image' => Storage::url('game_img/' . $item->filepic),
                'balance' => $item->game_user_free['balance'],
                'user_code' => $item->game_user_free['code'],
            ];

        });

        $profile = $this->user()->load('bank');

        return view($this->_config['view'], compact('profile', 'games', 'gamesfree'));
    }

    public function loadGame(): Collection
    {
        return collect($this->gameRepository->getGameUserById($this->id(), false)->toArray())->whereNotNull('game_user');

    }

    public function loadGameFree(): Collection
    {
        return collect($this->gameRepository->getGameUserFreeById($this->id(), false)->toArray())->whereNotNull('game_user_free');

    }

    public function changemain()
    {
        return view($this->_config['view']);
    }

    public function changepass(Request $request)
    {
        $request->validate([
            'currentPassword' => 'required|min:6|password:customer',
            'newPassword' => 'required|min:6',
        ]);

        $mydata = $request->all();

        $data['user_pass'] = $mydata['newPassword'];
        $data['password'] = Hash::make($mydata['newPassword']);

        try {
            $this->memberRepository->update($data, $this->id());
            session()->flash('success', 'คุณทำรายการแจ้งถอนเงิน สำเร็จแล้ว');
        } catch (ValidatorException $e) {
        }

        return redirect()->back();

    }

    public function changepass_api(Request $request)
    {
        $request->validate([
            // ตรวจรหัสผ่านปัจจุบันด้วยกฎที่ถูกต้อง
            'password_current'       => 'required|min:6|current_password:customer',
            // บังคับอย่างน้อย 8 ตัวอักษร + ต้อง "ยืนยัน" + ต้องต่างจากรหัสเดิม
            'password'               => [
                'required',
                'string',
                'min:6',
                'different:password_current',
            ],
            'password_confirmation'  => 'required|same:password',
        ], [
            // แปลข้อความผิดพลาดแบบอ่านง่าย (ทางเลือก)
            'password.different' => __('app.profile.password_must_be_different'),
            'password_confirmation.same' => __('app.profile.new_password_confirm_mismatch'),
        ]);

        $mydata = $request->only(['password']);

        // ❗ เลี่ยงการเก็บ plaintext password
        // ถ้าจำเป็นจริง ๆ (legacy) ควรเก็บเฉพาะในระบบที่แยกส่วน จำกัดสิทธิ์ และมีเหตุผลทางธุรกิจที่ตรวจสอบได้
         $data['user_pass'] = $mydata['password']; // ⚠️ หลีกเลี่ยง

        $data['password'] = Hash::make($mydata['password']);

        try {
            $this->memberRepository->update($data, $this->id());

            // ควร return รูปแบบสอดคล้องกับฝั่งหน้าเว็บ
            return response()->json([
                'success' => true,
                'message' => __('app.status.success'),
            ]);
        } catch (\Throwable $e) {
            // log รายละเอียดจริงด้วย logger ของคุณ
            // report($e);
            return response()->json([
                'success' => false,
                'message' => __('app.status.fail'),
            ], 200);
        }
    }


    public function resetgamepass(Request $request)
    {
        $games = $this->loadGame();
        $games = collect($games)->map(function ($items) {

            return (object)$items;

        });

        $user = collect($this->user()->toArray());
        $user_pass = 'Bb' . rand(100000, 999999);
        $game_err = [];
        foreach ($games as $i => $item) {

            $result = $this->gameUserRepository->changeGamePass($item->code, $item->game_user['code'], [
                'user_pass' => $user_pass,
                'user_name' => $item->game_user['user_name'],
                'name' => $user['name'],
                'firstname' => $user['firstname'],
                'lastname' => $user['lastname'],
                'gender' => $user['gender'],
                'birth_day' => $user['birth_day'],
                'date_regis' => $user['date_regis'],
            ]);

            if ($result['success'] !== true) {
                $game_err[] = $item->name;
            }

        }

        if (empty($game_err)) {
            $return['success'] = true;
            $msg = 'เกมทั้งหมด ได้ทำการเปลี่ยนรหัสผ่านแล้ว';
        } else {
            $return['success'] = true;
            $msg = 'เกมบางรายการ ได้ทำการเปลี่ยนรหัสผ่านแล้ว พบข้อผิดพลาดบางประการของเกม ' . implode(', ', $game_err);
        }

        return $this->sendSuccess($msg);
    }

    public function resetgamefreepass(Request $request)
    {
        $games = $this->loadGameFree();
        $games = collect($games)->map(function ($items) {

            return (object)$items;

        });

        $user = collect($this->user()->toArray());
        $user_pass = 'Cb' . rand(100000, 999999);
        $game_err = [];
        foreach ($games as $i => $item) {

            $result = $this->gameUserFreeRepository->changeGamePass($item->code, $item->game_user_free['code'], [
                'user_pass' => $user_pass,
                'user_name' => $item->game_user_free['user_name'],
                'name' => $user['name'],
                'firstname' => $user['firstname'],
                'lastname' => $user['lastname'],
                'gender' => $user['gender'],
                'birth_day' => $user['birth_day'],
                'date_regis' => $user['date_regis'],
            ]);

            if ($result['success'] !== true) {
                $game_err[] = $item->name;
            }

        }

        if (empty($game_err)) {
            $return['success'] = true;
            $msg = 'เกมทั้งหมด ได้ทำการเปลี่ยนรหัสผ่านแล้ว';
        } else {
            $return['success'] = true;
            $msg = 'เกมบางรายการ ได้ทำการเปลี่ยนรหัสผ่านแล้ว พบข้อผิดพลาดบางประการของเกม ' . implode(', ', $game_err);
        }

        return $this->sendSuccess($msg);
    }

    public function view(Request $request)
    {
        $id = $request->input('id');
        $result = $this->gameUserRepository->getOneUser($this->id(), $id, false);

        $result = collect($result['data']->toArray())->only(['user_name', 'user_pass', 'game']);
        //        $result = $this->gameUserRepository->with('game')->findOneWhere(['game_code' => $id , 'member_code' => $this->id()] , ['user_name as user','user_pass as pass']);

        return $this->sendResponseNew($result, 'complete');
    }

    public function viewfree(Request $request)
    {
        $id = $request->input('id');
        $result = $this->gameUserFreeRepository->getOneUser($this->id(), $id, false);

        $result = collect($result['data']->toArray())->only(['user_name', 'user_pass', 'game']);

        return $this->sendResponseNew($result, 'complete');
    }

    public function change(Request $request)
    {
        $game = $request->input('id');
        $password = $request->input('password');
        $game_user_list = $this->gameUserRepository->getOneUser($this->id(), $game, false);
        if ($game_user_list['success'] !== true) {
            $msg = 'ไม่พบข้อมูลรหัสเกมของ สมาชิก';

            return $this->sendSuccess($msg);
        }

        $game_user = $game_user_list['data'];

        $user = collect($this->user()->toArray());
        $user_pass = $password;

        $result = $this->gameUserRepository->changeGamePass($game, $game_user['code'], [
            'user_pass' => $user_pass,
            'user_name' => $game_user['user_name'],
            'name' => $user['name'],
            'firstname' => $user['firstname'],
            'lastname' => $user['lastname'],
            'gender' => $user['gender'],
            'birth_day' => $user['birth_day'],
            'date_regis' => $user['date_regis'],
        ]);

        if ($result['success'] === true) {
            $msg = $game_user['game']['name'] . ' ' . $result['msg'];
        } else {
            $msg = 'ไม่สามารถเปลี่ยนรหัสผ่านเกมได้ โปรดติดต่อทีมงาน';
        }

        return $this->sendSuccess($msg);
    }

    public function changefree(Request $request)
    {
        $game = $request->input('id');
        $game_user_list = $this->gameUserFreeRepository->getOneUser($this->id(), $game, false);
        if ($game_user_list['success'] !== true) {
            $msg = 'ไม่พบข้อมูลรหัสเกมของ สมาชิก';

            return $this->sendSuccess($msg);
        }

        $game_user = $game_user_list['data'];

        $user = collect($this->user()->toArray());
        $user_pass = 'Bb' . rand(100000, 999999);

        $result = $this->gameUserFreeRepository->changeGamePass($game, $game_user['code'], [
            'user_pass' => $user_pass,
            'user_name' => $game_user['user_name'],
            'name' => $user['name'],
            'firstname' => $user['firstname'],
            'lastname' => $user['lastname'],
            'gender' => $user['gender'],
            'birth_day' => $user['birth_day'],
            'date_regis' => $user['date_regis'],
        ]);

        if ($result['success'] === true) {
            $msg = $game_user['game']['name'] . ' ได้ทำการเปลี่ยนรหัสผ่านแล้ว';
        } else {
            $msg = 'ไม่สามารถเปลี่ยนรหัสผ่านเกมได้ โปรดติดต่อทีมงาน';
        }

        return $this->sendSuccess($msg);
    }

    public function game(Request $request)
    {
        $config = collect(core()->getConfigData());

        if ($config['seamless'] == 'Y') {
            $games = [];
            $gameTypes = $this->gameTypeRepository->findWhere(['enable' => 'Y']);
            foreach ($gameTypes as $type) {
                $games[$type->id] = $this->gameSeamlessRepository->orderBy('sort')->findWhere(['game_type' => $type->id, 'status_open' => 'Y', 'enable' => 'Y']);
            }

            return view($this->_config['view'], compact('games'));
        } else {
            $gamelist = core()->getGame();
            $result = $this->gameUserRepository->getOneUser($this->id(), $gamelist->code, false);
            if ($gamelist->gamelist == 'Y') {
                return redirect()->route('customer.game.list', ['id' => $gamelist->id]);
            } else {

                $game = collect($result['data']->toArray())->only(['user_name', 'user_pass', 'game']);
            }

            return view($this->_config['view'], compact('game'));
        }

    }

    public function gameListfree($id, Request $request)
    {
        $games = [];
        $user = $this->user();
        $game_name = $this->gameSeamlessRepository->findOneByField('id', $id);
        $result = $this->gameUserRepository->getGameList($id, $game_name->method);
        $lists = $this->gameSeamlessRepository->orderBy('name')->findWhere(['enable' => 'Y', 'status_open' => 'Y', 'cashback' => 'Y', 'game_type' => $game_name->game_type]);
        //        $game_name = $this->gameSeamlessRepository->findOneByField('id', $id);
        //        dd($lists);
        //        dd($result);
        if ($result['success'] === true) {
            $games = $result['games'];
            $games = collect($games)->map(function ($items) use ($game_name) {
                $items['image'] = Str::of($items['img'])->replace(' ', '')->replace('http:', 'https:')->__toString();
                $items['method'] = $game_name->method;

                return (object)$items;

            });

            //            dd($games);

            return view($this->_config['view'], compact('games', 'game_name'))->with('id', $id)->with('lists', $lists);
        } else {
            return redirect()->route('customer.home.index');
        }
    }

    public function games(Request $request)
    {
        $config = collect(core()->getConfigData());

        if ($config['seamless'] == 'Y') {
            $games = [];
            $gameTypes = $this->gameTypeRepository->findWhere(['enable' => 'Y']);
            foreach ($gameTypes as $type) {
                $games[$type->id] = $this->gameSeamlessRepository->orderBy('sort')->findWhere(['game_type' => $type->id, 'status_open' => 'Y', 'enable' => 'Y', 'cashback' => 'Y']);
            }

            return view($this->_config['view'], compact('games'));
        } else {

            if ($config['multigame_open'] == 'Y') {
                $gamelist = core()->getGame();
                $result = $this->gameUserFreeRepository->getOneUser($this->id(), $gamelist->code, false);
                if ($gamelist->gamelist == 'Y') {
                    return redirect()->route('customer.game.list', ['id' => $gamelist->id]);
                } else {

                    $game = collect($result['data']->toArray())->only(['user_name', 'user_pass', 'game']);
                }
            } else {
                $gamelist = core()->getGame();
                //                dd($gamelist);
                $result = $this->gameUserFreeRepository->getOneUser($this->id(), $gamelist->code, false);
                $games = collect($result['data']->toArray())->only(['user_name', 'user_pass', 'game']);
                //                dd($games);
                $profile = $this->user();

                return view($this->_config['view'], compact('games', 'profile'));
            }

            return view($this->_config['view'], compact('game'));
        }

    }

    public function gameList__($id, Request $request)
    {
        $games = [];
        $user = $this->user();
        $game_name = $this->gameSeamlessRepository->findOneByField('id', $id);
        //        $result = $this->gameUserRepository->getGameList($id,$game_name->method);
        $lists = $this->gameSeamlessRepository->orderBy('name')->findWhere(['enable' => 'Y', 'status_open' => 'Y', 'game_type' => $game_name->game_type]);
        //        $game_name = $this->gameSeamlessRepository->findOneByField('id', $id);
        //        dd($lists);
        //        dd($result);
        $games = GameListProxy::where('product', strtoupper($id))->where('enable', true)->get()->toArray();
        $games = collect($games)->map(function ($items) use ($game_name) {
            $items['image'] = Str::of($items['img'])->replace(' ', '')->replace('http:', 'https:')->__toString();
            $items['method'] = $game_name->method;

            return (object)$items;

        });

        return view($this->_config['view'], compact('games', 'game_name'))->with('id', $id)->with('lists', $lists);

    }

    public function gameList($type, $id, $name, Request $request)
    {
        $games = [];
        $user = $this->user();
//        $game_name = $name;
        $games = $this->getGames($type,$id);
//        dd($games);

        $lists = $this->getProviders($type);
        $lists = $lists[$type];

//        dd($name);

        $mapNameById = array_column($lists, 'providerName', 'provider');
        $providerName = $mapNameById[$id] ?? null;

//        dd($providerName);
//        $row = collect($lists)->firstWhere('prefix', $name);
//        dd($row);
        $game_name = $providerName ?? null;



        $gameTypes = $this->gameTypeRepository->findWhere(['enable' => 'Y', 'status_open' => 'Y']);
        $gameTypes->map(function ($item) {
            $item->icon = Storage::url('icon_cat/'.$item->icon);

            return $item;
        });


        return view($this->_config['view'], compact('games', 'game_name'))->with('id', $id)->with('lists', $lists)->with('gameTypes', $gameTypes);
    }

    public function gameListLogin_(Request $request)
    {
        $id = $request->input('id');
        $game = $request->input('game');
        $method = $request->input('method');
        $games = [];
        $user = $this->user();

        if (Cache::has('login_' . $user->code)) {
            exit;
        }

        Cache::put('login_' . $user->code, 'lock', now()->addSeconds(5));

        $config = collect(core()->getConfigData());

        if ($config['seamless'] == 'Y') {

            $this->bankPaymentRepository->where('member_topup', $user->code)->where('pro_check', 'N')->update([
                'pro_check' => 'Y',
                'user_update' => $user->name,
            ]);

            $game = $this->gameRepository->findOneByField('id', $method);
            $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $user->code, 'game_code' => $game->code]);

            //            if($method == 'seamless'){
            //                $game = $this->gameRepository->findOneByField('id',$method);
            //                $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $user->code , 'method'], $user->code);
            //            }else{
            //                $gameuser = $this->gameUserRepository->findOneByField('member_code', $user->code);
            //            }

            //            if (!$gameuser) {
            //                return $this->sendError('ไม่พบข้อมูลสมาชิก', 200);
            //            }
            if (!$gameuser) {
                $game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y', 'id' => $method]);
                $member = app('Gametech\Member\Repositories\MemberRepository')->find($user->code);
                if ($method == 'seamless') {
                    $gameid = 'PGSOFT';
                } else {
                    $gameid = 'MSPORT';
                }
                $res = $this->gameUserRepository->addGameUser($game->code, $member->code, ['username' => $member->user_name, 'product_id' => $gameid, 'user_create' => $member->user_name]);
                if ($res['success'] !== true) {
                    return $this->sendError('ไม่พบข้อมูลสมาชิก', 200);
                }
                $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $user->code, 'game_code' => $game->code]);

            }
            $result = $this->gameUserRepository->autoLoginSeamless($user->code, $id, $game);

        } else {

            $gameuser = $this->gameUserRepository->findOneByField('member_code', $user->code);
            if (!$gameuser) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 200);
            }
            $result = $this->gameUserRepository->autoLoginTransfer($user->code, $id, $game);
        }

        app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
            'ip' => request()->ip(),
            'credit_type' => 'D',
            'balance_before' => $user->balance,
            'balance_after' => $user->balance,
            'credit' => 0,
            'total' => 0,
            'credit_bonus' => 0,
            'credit_total' => 0,
            'credit_before' => $user->balance,
            'credit_after' => $user->balance,
            'pro_code' => 0,
            'bank_code' => 0,
            'auto' => 'N',
            'enable' => 'Y',
            'user_create' => 'System Auto',
            'user_update' => 'System Auto',
            'refer_code' => 0,
            'refer_table' => 'blank',
            'remark' => 'กดเข้าเกม ค่าย ' . $id . ' เกมรหัสที่ ' . $game,
            'kind' => 'OTHER',
            'amount' => 0,
            'amount_balance' => $gameuser->amount_balance,
            'withdraw_limit' => $gameuser->withdraw_limit,
            'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
            'method' => 'D',
            'member_code' => $user->code,
        ]);

        return $this->sendResponseNew($result, 'complete');
    }

    public function gameListLogin(Request $request)
    {
        //        dd($method);
        //        $method = 'transfer';
        $id = $request->input('id');
        $game = $request->input('game');
        $method = 'seamless';

        $pro = false;
        $url = '';
        $user = $this->user();

        //        if (Cache::has('login_' . $user->code)) {
        //            return view('wallet::customer.game.cannot');
        //        }
        //
        //        Cache::put('login_' . $user->code, 'lock', now()->addSeconds(5));
        //

        $this->bankPaymentRepository->where('member_topup', $user->code)->where('pro_check', 'N')->update([
            'pro_check' => 'Y',
            'user_update' => $user->name,
        ]);

        $game_s = GameListProxy::where('code', $game)->where('product', strtoupper($id))->first();

        //        $games = $this->gameRepository->findOneByField('id',$method);
        $games = $this->gameRepository->findOneWhere(['enable' => 'Y', 'status_open' => 'Y', 'id' => $method]);
        $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $user->code, 'game_code' => $games->code]);
        //        dd($gameuser);
        //        $gameuser = $this->gameUserRepository->findOneByField('member_code', $user->code);
        //            if (!$gameuser) {
        //                return $this->sendError('ไม่พบข้อมูลสมาชิก', 200);
        //            }
        if (!$gameuser) {
            //            dd('no user');
            $games = $this->gameRepository->findOneWhere(['enable' => 'Y', 'status_open' => 'Y', 'id' => $method]);
            $member = app('Gametech\Member\Repositories\MemberRepository')->find($user->code);
            //            if ($method == 'seamless') {
            $gameid = $id;
            //            } else {
            //                $gameid = 'MSPORT';
            //            }

            //            dd($gameid);
            $res = $this->gameUserRepository->addGameUser($games->code, $member->code, ['username' => $member->user_name, 'password' => $member->user_pass, 'product_id' => $gameid, 'user_create' => $member->user_name]);
            //            dd($res);
            //            $res = $this->gameUserRepository->addGameUser($game->code, $member->code, ['username' => $member->user_name, 'product_id' => 'PGSOFT', 'user_create' => $member->user_name]);
            if ($res['success'] !== true) {
                $url = '';
                //                return $this->sendError('ไม่พบข้อมูลสมาชิก', 200);
            }
            $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $user->code, 'game_code' => $games->code]);

        }
        if ($method == 'transfer') {
            $user = $this->user();
            $balance = $user->balance;
            //            dd($balance);
            $games1 = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y', 'id' => 'seamless']);
            $gameuser1 = $this->gameUserRepository->findOneWhere(['member_code' => $user->code, 'game_code' => $games1->code]);
            if ($gameuser1->amount_balance > 0) {
                return view('wallet::customer.game.cannot');
            }

            $game_user = $this->gameUserRepository->UserDepositTransfer(strtoupper($id), $games->code, $user->user_name, $balance);
            //            dd($game_user);
            if ($game_user['success'] === true) {
                $user->balance = 0;
                $user->save();

                app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
                    'ip' => request()->ip(),
                    'credit_type' => 'D',
                    'balance_before' => $balance,
                    'balance_after' => 0,
                    'credit' => 0,
                    'total' => $balance,
                    'credit_bonus' => 0,
                    'credit_total' => 0,
                    'credit_before' => $game_user['before'],
                    'credit_after' => $game_user['after'],
                    'pro_code' => 0,
                    'bank_code' => 0,
                    'auto' => 'N',
                    'enable' => 'Y',
                    'user_create' => 'System Auto',
                    'user_update' => 'System Auto',
                    'refer_code' => 0,
                    'refer_table' => 'blank',
                    'remark' => 'กดเข้าเกม ค่าย ' . $id . ' เกมรหัสที่ ' . $game . ' พร้อมโยกเงินเข้า จำนวน ' . $balance,
                    'kind' => 'OTHER',
                    'amount' => $balance,
                    'amount_balance' => $gameuser->amount_balance,
                    'withdraw_limit' => $gameuser->withdraw_limit,
                    'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
                    'method' => 'D',
                    'member_code' => $user->code,
                ]);

            }
        } else {

            $gamechk = $this->gameUserRepository->findOneWhere(['member_code' => $user->code, 'game_code' => 1]);
            if ($gamechk->pro_code > 0) {

                $promotion = app('Gametech\Promotion\Repositories\PromotionRepository')->whereIn('id', ['pro_newuser', 'pro_allbonus', 'pro_firstday'])->where('code', $gamechk->pro_code)->first();

                if ($promotion) {

                    $login = false;
                    $can = [];
                    if ($promotion->slot == 'Y') {
                        $can[] = 'สล๊อต';
                    }
                    if ($promotion->casino == 'Y') {
                        $can[] = 'คาสิโน';
                    }
                    if ($promotion->sport == 'Y') {
                        $can[] = 'กีฬา';
                    }
                    if ($promotion->huay == 'Y') {
                        $can[] = 'แทงหวย';
                    }

                    switch ($game_s->category) {
                        case 'EGAMES' :
                            if ($promotion->slot == 'Y') {
                                $login = true;
                            }
                            break;

                        case 'LIVECASINO' :
                            if ($promotion->casino == 'Y') {
                                $login = true;
                            }
                            break;

                        case 'SPORT' :
                            if ($promotion->sport == 'Y') {
                                $login = true;
                            }
                            break;

                        case 'huay' :
                            if ($promotion->huay == 'Y') {
                                $login = true;
                            }
                            break;

                        default:
                            $login = false;
                    }

                    if (!$login) {
                        $cannot = implode(',', $can);

                        //                        $result['success'] = false;
                        //                        $result['message'] = 'รับโปรไม่สามารถเข้าค่ายเกม นี้ได้';
                        return $this->sendError('เมื่อรับโปร จะสามารถเล่นได้ แค่เกมประเภท ' . $cannot, 200);
                    }

                }
            }

            app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
                'ip' => request()->ip(),
                'credit_type' => 'D',
                'balance_before' => $user->balance,
                'balance_after' => $user->balance,
                'credit' => 0,
                'total' => 0,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => $user->balance,
                'credit_after' => $user->balance,
                'pro_code' => 0,
                'bank_code' => 0,
                'auto' => 'N',
                'enable' => 'Y',
                'user_create' => 'System Auto',
                'user_update' => 'System Auto',
                'refer_code' => 0,
                'refer_table' => 'blank',
                'remark' => 'กดเข้าเกม ค่าย ' . $id . ' เกม ' . $game_s->name,
                'kind' => 'OTHER',
                'amount' => 0,
                'amount_balance' => $gameuser->amount_balance,
                'withdraw_limit' => $gameuser->withdraw_limit,
                'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
                'method' => 'D',
                'member_code' => $user->code,
            ]);
        }

        $result = $this->gameUserRepository->autoLoginSeamless($user->code, $id, $game);

        //        return view($this->_config['view']);
        //
        //        dd($result);

        //        if ($result['success'] === true) {
        //            $url = $result['url'];
        //        }

        //        dd($result);

        //        $pid = $result['game'];

        //        if ($url == '') {
        //            return view('wallet::customer.game.cannot');
        //        }
        //
        //        if($pid == 'PGSOFT2'){
        //            return view('wallet::customer.game.redirect2', compact('url'));
        //        }else{
        //        return view($this->_config['view'], compact('url'));
        //        }
        //
        //        if ($result['success'] === true) {
        //            return redirect()->away($result['url']);
        //        }

        return $this->sendResponseNew($result, 'complete');
    }

    public function gameFreeListLogin_(Request $request)
    {
        $id = $request->input('id');
        $game = $request->input('game');
        $games = [];
        $user = $this->user();

        if (Cache::has('loginfree_' . $user->code)) {
            exit;
        }

        Cache::put('loginfree_' . $user->code, 'lock', now()->addSeconds(5));

        $gameuser = $this->gameUserFreeRepository->findOneByField('member_code', $user->code);
        if (!$gameuser) {
            $game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y']);
            $member = app('Gametech\Member\Repositories\MemberRepository')->find($user->code);
            $res = $this->gameUserFreeRepository->addGameUser($game->code, $member->code, ['username' => $member->user_name, 'product_id' => 'PGSOFT', 'user_create' => $member->user_name]);
            if ($res['success'] !== true) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 200);
            }
            $gameuser = $this->gameUserFreeRepository->findOneByField('member_code', $user->code);

        }
        $result = $this->gameUserFreeRepository->autoLoginSeamless($user->code, $id, $game);

        app('Gametech\Member\Repositories\MemberCreditFreeLogRepository')->create([
            'ip' => request()->ip(),
            'credit_type' => 'D',
            'balance_before' => $user->balance_free,
            'balance_after' => $user->balance_free,
            'credit' => 0,
            'total' => 0,
            'credit_bonus' => 0,
            'credit_total' => 0,
            'credit_before' => $user->balance_free,
            'credit_after' => $user->balance_free,
            'pro_code' => 0,
            'bank_code' => 0,
            'auto' => 'N',
            'enable' => 'Y',
            'user_create' => 'System Auto',
            'user_update' => 'System Auto',
            'refer_code' => 0,
            'refer_table' => 'blank',
            'remark' => 'กดเข้าเกม ค่าย ' . $id . ' เกมรหัสที่ ' . $game,
            'kind' => 'OTHER',
            'amount' => 0,
            'amount_balance' => $gameuser->amount_balance,
            'withdraw_limit' => $gameuser->withdraw_limit,
            'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
            'method' => 'D',
            'member_code' => $user->code,
        ]);

        return $this->sendResponseNew($result, 'complete');
    }

    public function gameFreeListLogin(Request $request)
    {
        $id = $request->input('id');
        $game = $request->input('game');
        $method = 'seamless';
        $url = '';
        $user = $this->user();

        //        if (Cache::has('loginfree_' . $user->code)) {
        //            return view('wallet::customer.credit.game.cannot');
        //        }
        //
        //        Cache::put('loginfree_' . $user->code, 'lock', now()->addSeconds(5));

        $games = $this->gameRepository->findOneByField('id', $method);
        $gameuser = $this->gameUserFreeRepository->findOneWhere(['member_code' => $user->code, 'game_code' => $games->code]);
        //            if (!$gameuser) {
        //                return $this->sendError('ไม่พบข้อมูลสมาชิก', 200);
        //            }
        if (!$gameuser) {
            $game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y', 'id' => $method]);
            $member = app('Gametech\Member\Repositories\MemberRepository')->find($user->code);
            if ($method == 'seamless') {
                $gameid = 'PGSOFT';
            } else {
                $gameid = 'MSPORT';
            }
            $res = $this->gameUserFreeRepository->addGameUser($game->code, $member->code, ['username' => $member->user_name, 'product_id' => $gameid, 'user_create' => $member->user_name]);
            if ($res['success'] !== true) {
                $url = '';
            }
            $gameuser = $this->gameUserFreeRepository->findOneWhere(['member_code' => $user->code, 'game_code' => $games->code]);

        }

        if ($method == 'transfer') {
            $user = $this->user();
            $balance = $user->balance_free;
            //            dd($user->user_name);

            $games1 = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y', 'id' => 'seamless']);
            $gameuser1 = $this->gameUserFreeRepository->findOneWhere(['member_code' => $user->code, 'game_code' => $games1->code]);
            if ($gameuser1->amount_balance > 0) {
                return view('wallet::customer.credit.game.cannot');
            }

            $game_user = $this->gameUserFreeRepository->UserDepositTransfer(strtoupper($id), $games->code, $user->user_name, $balance);
            //            dd($game_user);
            if ($game_user['success'] === true) {

                $user->balance_free = 0;
                $user->save();

                app('Gametech\Member\Repositories\MemberCreditFreeLogRepository')->create([
                    'ip' => request()->ip(),
                    'credit_type' => 'D',
                    'balance_before' => $balance,
                    'balance_after' => 0,
                    'credit' => 0,
                    'total' => $balance,
                    'credit_bonus' => 0,
                    'credit_total' => 0,
                    'credit_before' => $game_user['before'],
                    'credit_after' => $game_user['after'],
                    'pro_code' => 0,
                    'bank_code' => 0,
                    'auto' => 'N',
                    'enable' => 'Y',
                    'user_create' => 'System Auto',
                    'user_update' => 'System Auto',
                    'refer_code' => 0,
                    'refer_table' => 'blank',
                    'remark' => 'กดเข้าเกม ค่าย ' . $id . ' เกมรหัสที่ ' . $game . ' พร้อมโยกเงินเข้า จำนวน ' . $balance,
                    'kind' => 'OTHER',
                    'amount' => $balance,
                    'amount_balance' => $gameuser->amount_balance,
                    'withdraw_limit' => $gameuser->withdraw_limit,
                    'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
                    'method' => 'D',
                    'member_code' => $user->code,
                ]);
            }
        } else {
            app('Gametech\Member\Repositories\MemberCreditFreeLogRepository')->create([
                'ip' => request()->ip(),
                'credit_type' => 'D',
                'balance_before' => $user->balance_free,
                'balance_after' => $user->balance_free,
                'credit' => 0,
                'total' => 0,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => $user->balance_free,
                'credit_after' => $user->balance_free,
                'pro_code' => 0,
                'bank_code' => 0,
                'auto' => 'N',
                'enable' => 'Y',
                'user_create' => 'System Auto',
                'user_update' => 'System Auto',
                'refer_code' => 0,
                'refer_table' => 'blank',
                'remark' => 'กดเข้าเกม ค่าย ' . $id . ' เกมรหัสที่ ' . $game,
                'kind' => 'OTHER',
                'amount' => 0,
                'amount_balance' => $gameuser->amount_balance,
                'withdraw_limit' => $gameuser->withdraw_limit,
                'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
                'method' => 'D',
                'member_code' => $user->code,
            ]);
        }

        $result = $this->gameUserFreeRepository->autoLoginSeamless($user->code, $id, $game);

        //        return view($this->_config['view']);
        //
        //        dd($result);

        //        if ($result['success'] === true) {
        //            $url = $result['url'];
        //        }

        //        dd($url);

        //        if ($url == '') {
        //            return view('wallet::customer.credit.game.cannot');
        //        }

        //        if ($result['success'] === true) {
        //            return redirect()->away($result['url']);
        //        }
        return $this->sendResponseNew($result, 'complete');
        //        return view($this->_config['view'], compact('url'));
    }

    public function gameRedirect($method, $id, $game)
    {
        //        dd($method);
        //        $method = 'transfer';

        $pro = false;
        $url = '';
        $user = $this->user();

        //        if (Cache::has('login_' . $user->code)) {
        //            return view('wallet::customer.game.cannot');
        //        }
        //
        //        Cache::put('login_' . $user->code, 'lock', now()->addSeconds(5));

        $this->bankPaymentRepository->where('member_topup', $user->code)->where('pro_check', 'N')->update([
            'pro_check' => 'Y',
            'user_update' => $user->name,
        ]);

        $game_s = GameListProxy::where('code', $game)->where('product', strtoupper($id))->first();

        //        $games = $this->gameRepository->findOneByField('id',$method);
        $games = $this->gameRepository->findOneWhere(['enable' => 'Y', 'status_open' => 'Y', 'id' => $method]);
//        dd($games);
        $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $user->code, 'game_code' => $games->code]);
//                dd($gameuser);
        //        $gameuser = $this->gameUserRepository->findOneByField('member_code', $user->code);
        //            if (!$gameuser) {
        //                return $this->sendError('ไม่พบข้อมูลสมาชิก', 200);
        //            }
        if (!$gameuser) {
            //            dd('no user');
            $games = $this->gameRepository->findOneWhere(['enable' => 'Y', 'status_open' => 'Y', 'id' => $method]);
            $member = app('Gametech\Member\Repositories\MemberRepository')->find($user->code);
            //            if ($method == 'seamless') {
            $gameid = $id;
            //            } else {
            //                $gameid = 'MSPORT';
            //            }

            //            dd($gameid);
            $res = $this->gameUserRepository->addGameUser($games->code, $member->code, ['username' => $member->user_name, 'password' => $member->user_pass, 'product_id' => $gameid, 'user_create' => $member->user_name]);
//                        dd($res);
            //            $res = $this->gameUserRepository->addGameUser($game->code, $member->code, ['username' => $member->user_name, 'product_id' => 'PGSOFT', 'user_create' => $member->user_name]);
            if ($res['success'] !== true) {
                $url = '';
                //                return $this->sendError('ไม่พบข้อมูลสมาชิก', 200);
            }
            $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $user->code, 'game_code' => $games->code]);

        }
        if ($method == 'transfer') {
            $user = $this->user();
            $balance = $user->balance;
            //            dd($balance);
            $games1 = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y', 'id' => 'seamless']);
            $gameuser1 = $this->gameUserRepository->findOneWhere(['member_code' => $user->code, 'game_code' => $games1->code]);
            if ($gameuser1->amount_balance > 0) {
                return view('wallet::customer.game.cannot');
            }

            $game_user = $this->gameUserRepository->UserDepositTransfer(strtoupper($id), $games->code, $user->user_name, $balance);
            //            dd($game_user);
            if ($game_user['success'] === true) {
                $user->balance = 0;
                $user->save();

                app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
                    'ip' => request()->ip(),
                    'credit_type' => 'D',
                    'balance_before' => $balance,
                    'balance_after' => 0,
                    'credit' => 0,
                    'total' => $balance,
                    'credit_bonus' => 0,
                    'credit_total' => 0,
                    'credit_before' => $game_user['before'],
                    'credit_after' => $game_user['after'],
                    'pro_code' => 0,
                    'bank_code' => 0,
                    'auto' => 'N',
                    'enable' => 'Y',
                    'user_create' => 'System Auto',
                    'user_update' => 'System Auto',
                    'refer_code' => 0,
                    'refer_table' => 'blank',
                    'remark' => 'กดเข้าเกม ค่าย ' . $id . ' เกมรหัสที่ ' . $game . ' พร้อมโยกเงินเข้า จำนวน ' . $balance,
                    'kind' => 'OTHER',
                    'amount' => $balance,
                    'amount_balance' => $gameuser->amount_balance,
                    'withdraw_limit' => $gameuser->withdraw_limit,
                    'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
                    'method' => 'D',
                    'member_code' => $user->code,
                ]);

            }
        } else {

            $gamechk = $this->gameUserRepository->findOneWhere(['member_code' => $user->code, 'game_code' => $gameuser->game_code]);
            if ($gamechk && $gamechk->pro_code > 0) {

                $promotion = app('Gametech\Promotion\Repositories\PromotionRepository')->where('code', $gamechk->pro_code)->first();

                if ($promotion) {

                    $login = false;
                    $can = [];
                    if ($promotion->slot == 'Y') {
                        $can[] = 'สล๊อต';
                    }
                    if ($promotion->casino == 'Y') {
                        $can[] = 'คาสิโน';
                    }
                    if ($promotion->sport == 'Y') {
                        $can[] = 'กีฬา';
                    }
                    if ($promotion->huay == 'Y') {
                        $can[] = 'แทงหวย';
                    }
                    if ($promotion->lotto == 'Y') {
                        $can[] = 'ล๊อตโต';
                    }
                    if ($promotion->keno == 'Y') {
                        $can[] = 'คีโน่';
                    }
                    if ($promotion->card == 'Y') {
                        $can[] = 'เกมการ์ด';
                    }
                    if ($promotion->cock == 'Y') {
                        $can[] = 'ไก่ชน';
                    }
                    if ($promotion->poker == 'Y') {
                        $can[] = 'ไพ่โป๊กเกอร์';
                    }

                    switch ($game_s->category) {
                        case 'EGAMES' :
                            if ($promotion->slot == 'Y') {
                                $login = true;
                            }
                            break;

                        case 'LIVECASINO' :
                            if ($promotion->casino == 'Y') {
                                $login = true;
                            }
                            break;

                        case 'SPORT' :
                            if ($promotion->sport == 'Y') {
                                $login = true;
                            }
                            break;

                        case 'huay' :
                            if ($promotion->huay == 'Y') {
                                $login = true;
                            }
                            break;
                        case 'LOTTO' :
                            if ($promotion->lotto == 'Y') {
                                $login = true;
                            }
                            break;
                        case 'KENO' :
                            if ($promotion->keno == 'Y') {
                                $login = true;
                            }
                            break;
                        case 'CARD' :
                            if ($promotion->CARD == 'Y') {
                                $login = true;
                            }
                            break;
                        case 'COCK' :
                            if ($promotion->cock == 'Y') {
                                $login = true;
                            }
                            break;
                        case 'POKER' :
                            if ($promotion->poker == 'Y') {
                                $login = true;
                            }
                            break;

                        default:
                            $login = false;
                    }

                    if (!$login) {
                        $cannot = implode(',', $can);

                        return view('wallet::customer.game.procannot', compact('cannot'));
                    }

                }
            }

            app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
                'ip' => request()->ip(),
                'credit_type' => 'D',
                'balance_before' => $user->balance,
                'balance_after' => $user->balance,
                'credit' => 0,
                'total' => 0,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => $user->balance,
                'credit_after' => $user->balance,
                'pro_code' => 0,
                'bank_code' => 0,
                'auto' => 'N',
                'enable' => 'Y',
                'user_create' => 'System Auto',
                'user_update' => 'System Auto',
                'refer_code' => 0,
                'refer_table' => 'blank',
                'remark' => 'กดเข้าเกม ค่าย ' . $id . ' เกม ' . $game_s->name,
                'kind' => 'OTHER',
                'amount' => 0,
                'amount_balance' => $gameuser->amount_balance,
                'withdraw_limit' => $gameuser->withdraw_limit,
                'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
                'method' => 'D',
                'member_code' => $user->code,
            ]);
        }

        $result = $this->gameUserRepository->autoLoginSeamless($user->code, $id, $game);

        //        return view($this->_config['view']);
        //
        //        dd($result);

        if ($result['success'] === true) {
            $url = $result['url'];
        }

        //        dd($result);

        //        $pid = $result['game'];

        if ($url == '') {
            return view('wallet::customer.game.cannot');
        }

        //
        //        if($pid == 'PGSOFT2'){
        //            return view('wallet::customer.game.redirect2', compact('url'));
        //        }else{
        return view($this->_config['view'], compact('url'));
        //        }
        //
        //        if ($result['success'] === true) {
        //            return redirect()->away($result['url']);
        //        }

    }

    public function gameRedirectSingleAmb($type, $provider, $id)
    {
        //        dd($method);
        //        $method = 'transfer';

        $pro = false;
        $url = '';
        $user = $this->user();

        $games = core()->getGame();
//
//        $lists = $this->getProviders($type);
//        $lists = $lists[$type];

//        $mapNameById = array_column($lists, 'providerType', 'provider');
//
//        $gameType = $mapNameById[$provider] ?? null;
//
////        dd($provider);


        $gamechk = $this->gameUserRepository->findOneWhere(['member_code' => $user->code, 'game_code' => $games->code]);
        if ($gamechk && $gamechk->pro_code > 0) {

            $promotion = app('Gametech\Promotion\Repositories\PromotionRepository')->where('code', $gamechk->pro_code)->first();
            if ($promotion) {

                $login = false;
                $can = [];
                if ($promotion->slot == 'Y') {
                    $can[] = 'สล๊อต';
                }
                if ($promotion->casino == 'Y') {
                    $can[] = 'คาสิโน';
                }
                if ($promotion->sport == 'Y') {
                    $can[] = 'กีฬา';
                }
                if ($promotion->huay == 'Y') {
                    $can[] = 'แทงหวย';
                }
                if ($promotion->lotto == 'Y') {
                    $can[] = 'ล๊อตโต';
                }
                if ($promotion->keno == 'Y') {
                    $can[] = 'คีโน่';
                }
                if ($promotion->card == 'Y') {
                    $can[] = 'เกมการ์ด';
                }
                if ($promotion->cock == 'Y') {
                    $can[] = 'ไก่ชน';
                }
                if ($promotion->poker == 'Y') {
                    $can[] = 'ไพ่โป๊กเกอร์';
                }
                if ($promotion->fish == 'Y') {
                    $can[] = 'ยิงปลา';
                }

                switch ($type) {
                    case 'slot' :
                        if ($promotion->slot == 'Y') {
                            $login = true;
                        }
                        break;

                    case 'casino' :
                        if ($promotion->casino == 'Y') {
                            $login = true;
                        }
                        break;

                    case 'sport' :
                        if ($promotion->sport == 'Y') {
                            $login = true;
                        }
                        break;

                    case 'huay' :
                        if ($promotion->huay == 'Y') {
                            $login = true;
                        }
                        break;
                    case 'lotto' :
                        if ($promotion->lotto == 'Y') {
                            $login = true;
                        }
                        break;
                    case 'KENO' :
                        if ($promotion->keno == 'Y') {
                            $login = true;
                        }
                        break;
                    case 'card' :
                        if ($promotion->CARD == 'Y') {
                            $login = true;
                        }
                        break;
                    case 'COCK' :
                        if ($promotion->cock == 'Y') {
                            $login = true;
                        }
                        break;
                    case 'poker' :
                        if ($promotion->poker == 'Y') {
                            $login = true;
                        }
                        break;
                    case 'fish' :
                        if ($promotion->fish == 'Y') {
                            $login = true;
                        }
                        break;

                    default:
                        $login = false;
                }


                if (! $login) {
                    $cannot = implode(',', $can);

                    return view('wallet::customer.game.procannot', compact('cannot'));
                }

            }
        }
//        dd($user);
        $host     = request()->getHost();
        $cacheP = "private:lobby:providers:{$host}";
        $cached_provider = Cache::get($cacheP);
        $pname = $cached_provider['mapById'][$provider] ?? null;

        $cacheKey = "private:{$provider}:gamelist:{$host}"; // อันเดียวกับที่ Kickoff เขียนไว้
        $cached = Cache::get($cacheKey);
        $gcode = (string) $id;
        $game = $cached['mapByCode'][$gcode] ?? null;


//        dd($user);
        app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
            'ip' => request()->ip(),
            'credit_type' => 'D',
            'balance_before' => $gamechk->balance,
            'balance_after' => $gamechk->balance,
            'credit' => 0,
            'total' => 0,
            'credit_bonus' => 0,
            'credit_total' => 0,
            'credit_before' => $gamechk->balance,
            'credit_after' => $gamechk->balance,
            'gameuser_code' => $gamechk->code,
            'game_code' => $games->code,
            'pro_code' => 0,
            'bank_code' => 0,
            'auto' => 'N',
            'enable' => 'Y',
            'user_create' => 'System Auto',
            'user_update' => 'System Auto',
            'refer_code' => 0,
            'refer_table' => 'blank',
            'remark' => 'กดเข้าเกม ค่าย ' . ($pname['name'] ?? $provider). ' เกม ' . ($game['name'] ?? $id),
            'kind' => 'OTHER',
            'amount' => 0,
            'amount_balance' => $gamechk->amount_balance,
            'withdraw_limit' => $gamechk->withdraw_limit,
            'withdraw_limit_amount' => $gamechk->withdraw_limit_amount,
            'method' => 'D',
            'member_code' => $user->code,
        ]);

        $result = $this->gameUserRepository->autoLoginSingle($user->code, $type, $id, $provider);

        if ($result['success'] === true) {
            $url = $result['url'];

        }

        if ($url == '') {
            return view('wallet::customer.game.cannot');
        }

        return view($this->_config['view'], compact('url'));

    }


    public function gameCreditRedirect($method, $id, $game)
    {
        $url = '';
        $user = $this->user();

        //        if (Cache::has('loginfree_' . $user->code)) {
        //            return view('wallet::customer.credit.game.cannot');
        //        }
        //
        //        Cache::put('loginfree_' . $user->code, 'lock', now()->addSeconds(5));

        $games = $this->gameRepository->findOneByField('id', $method);
        $gameuser = $this->gameUserFreeRepository->findOneWhere(['member_code' => $user->code, 'game_code' => $games->code]);
        //            if (!$gameuser) {
        //                return $this->sendError('ไม่พบข้อมูลสมาชิก', 200);
        //            }
        if (!$gameuser) {
            $game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y', 'id' => $method]);
            $member = app('Gametech\Member\Repositories\MemberRepository')->find($user->code);
            if ($method == 'seamless') {
                $gameid = 'PGSOFT';
            } else {
                $gameid = 'MSPORT';
            }
            $res = $this->gameUserFreeRepository->addGameUser($game->code, $member->code, ['username' => $member->user_name, 'product_id' => $gameid, 'user_create' => $member->user_name]);
            if ($res['success'] !== true) {
                $url = '';
            }
            $gameuser = $this->gameUserFreeRepository->findOneWhere(['member_code' => $user->code, 'game_code' => $games->code]);

        }

        if ($method == 'transfer') {
            $user = $this->user();
            $balance = $user->balance_free;
            //            dd($user->user_name);

            $games1 = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y', 'id' => 'seamless']);
            $gameuser1 = $this->gameUserFreeRepository->findOneWhere(['member_code' => $user->code, 'game_code' => $games1->code]);
            if ($gameuser1->amount_balance > 0) {
                return view('wallet::customer.credit.game.cannot');
            }

            $game_user = $this->gameUserFreeRepository->UserDepositTransfer(strtoupper($id), $games->code, $user->user_name, $balance);
            //            dd($game_user);
            if ($game_user['success'] === true) {

                $user->balance_free = 0;
                $user->save();

                app('Gametech\Member\Repositories\MemberCreditFreeLogRepository')->create([
                    'ip' => request()->ip(),
                    'credit_type' => 'D',
                    'balance_before' => $balance,
                    'balance_after' => 0,
                    'credit' => 0,
                    'total' => $balance,
                    'credit_bonus' => 0,
                    'credit_total' => 0,
                    'credit_before' => $game_user['before'],
                    'credit_after' => $game_user['after'],
                    'pro_code' => 0,
                    'bank_code' => 0,
                    'auto' => 'N',
                    'enable' => 'Y',
                    'user_create' => 'System Auto',
                    'user_update' => 'System Auto',
                    'refer_code' => 0,
                    'refer_table' => 'blank',
                    'remark' => 'กดเข้าเกม ค่าย ' . $id . ' เกมรหัสที่ ' . $game . ' พร้อมโยกเงินเข้า จำนวน ' . $balance,
                    'kind' => 'OTHER',
                    'amount' => $balance,
                    'amount_balance' => $gameuser->amount_balance,
                    'withdraw_limit' => $gameuser->withdraw_limit,
                    'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
                    'method' => 'D',
                    'member_code' => $user->code,
                ]);
            }
        } else {
            app('Gametech\Member\Repositories\MemberCreditFreeLogRepository')->create([
                'ip' => request()->ip(),
                'credit_type' => 'D',
                'balance_before' => $user->balance_free,
                'balance_after' => $user->balance_free,
                'credit' => 0,
                'total' => 0,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => $user->balance_free,
                'credit_after' => $user->balance_free,
                'pro_code' => 0,
                'bank_code' => 0,
                'auto' => 'N',
                'enable' => 'Y',
                'user_create' => 'System Auto',
                'user_update' => 'System Auto',
                'refer_code' => 0,
                'refer_table' => 'blank',
                'remark' => 'กดเข้าเกม ค่าย ' . $id . ' เกมรหัสที่ ' . $game,
                'kind' => 'OTHER',
                'amount' => 0,
                'amount_balance' => $gameuser->amount_balance,
                'withdraw_limit' => $gameuser->withdraw_limit,
                'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
                'method' => 'D',
                'member_code' => $user->code,
            ]);
        }

        $result = $this->gameUserFreeRepository->autoLoginSeamless($user->code, $id, $game);

        //        return view($this->_config['view']);
        //
        //        dd($result);

        if ($result['success'] === true) {
            $url = $result['url'];
        }

        //        dd($url);

        if ($url == '') {
            return view('wallet::customer.credit.game.cannot');
        }

        //        if ($result['success'] === true) {
        //            return redirect()->away($result['url']);
        //        }

        return view($this->_config['view'], compact('url'));
    }

    public function gameListLoginGet($id, $game, Request $request)
    {

        //        dd($id);
        $games = [];
        $user = $this->user();

        if (Cache::has('login_' . $user->code)) {
            exit;
        }

        Cache::put('login_' . $user->code, 'lock', now()->addSeconds(5));

        $config = collect(core()->getConfigData());

        if ($config['seamless'] == 'Y') {

            $this->bankPaymentRepository->where('member_topup', $user->code)->where('pro_check', 'N')->update([
                'pro_check' => 'Y',
                'user_update' => $user->name,
            ]);

            $gameuser = $this->gameUserRepository->findOneByField('member_code', $user->code);
            //            if (!$gameuser) {
            //                return $this->sendError('ไม่พบข้อมูลสมาชิก', 200);
            //            }
            if (!$gameuser) {
                $game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y']);
                $member = app('Gametech\Member\Repositories\MemberRepository')->find($user->code);
                $res = $this->gameUserRepository->addGameUser($game->code, $member->code, ['username' => $member->user_name, 'product_id' => 'PGSOFT', 'user_create' => $member->user_name]);
                if ($res['success'] !== true) {
                    return $this->sendError('ไม่พบข้อมูลสมาชิก', 200);
                }
                $gameuser = $this->gameUserRepository->findOneByField('member_code', $user->code);

            }
            $result = $this->gameUserRepository->autoLoginSeamless($user->code, $id, $game);

        } else {

            $gameuser = $this->gameUserRepository->findOneByField('member_code', $user->code);
            if (!$gameuser) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 200);
            }
            $result = $this->gameUserRepository->autoLoginTransfer($user->code, $id, $game);
        }

        app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
            'ip' => request()->ip(),
            'credit_type' => 'D',
            'balance_before' => $user->balance,
            'balance_after' => $user->balance,
            'credit' => 0,
            'total' => 0,
            'credit_bonus' => 0,
            'credit_total' => 0,
            'credit_before' => $user->balance,
            'credit_after' => $user->balance,
            'pro_code' => 0,
            'bank_code' => 0,
            'auto' => 'N',
            'enable' => 'Y',
            'user_create' => 'System Auto',
            'user_update' => 'System Auto',
            'refer_code' => 0,
            'refer_table' => 'blank',
            'remark' => 'กดเข้าเกม ค่าย ' . $id . ' เกมรหัสที่ ' . $game,
            'kind' => 'OTHER',
            'amount' => 0,
            'amount_balance' => $gameuser->amount_balance,
            'withdraw_limit' => $gameuser->withdraw_limit,
            'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
            'method' => 'D',
            'member_code' => $user->code,
        ]);

        //        dd($result);

        if ($result['success'] === true) {
            return redirect()->away($result['url']);
        }

        return $this->sendResponseNew($result, 'complete');
    }

    public function loginGameID($id, Request $request)
    {

        $games = $this->gameRepository->findOneByField('id', $id);

        $result = $this->gameUserRepository->getOneUser($this->id(), $games->code, false);

        $game = collect($result['data']->toArray())->only(['user_name', 'user_pass', 'game']);
        //        dd($game);
        $result = $this->gameUserRepository->autoLogin($game['game']['id'], $game['user_name'], $game['user_pass']);
        if ($result['success'] == 'true') {
            return redirect()->away($result['url']);
        }

        return redirect()->route('customer.home.index');

    }

    public function loginGameCreditID($id, Request $request)
    {

        $games = $this->gameRepository->findOneByField('id', $id);

        $result = $this->gameUserFreeRepository->getOneUser($this->id(), $games->code, false);

        $game = collect($result['data']->toArray())->only(['user_name', 'user_pass', 'game']);
        //        dd($game);
        $result = $this->gameUserFreeRepository->autoLogin($game['game']['id'], $game['user_name'], $game['user_pass']);
        if ($result['success'] == 'true') {
            return redirect()->away($result['url']);
        }

        return redirect()->route('customer.credit.game.index');

    }

    public function loginGame(Request $request)
    {
        $gamelist = core()->getGame();
        //        dd($gamelist);
        $result = $this->gameUserRepository->getOneUser($this->id(), $gamelist->code, false);

        $game = collect($result['data']->toArray())->only(['user_name', 'user_pass', 'game']);
        //        dd($game);
        $result = $this->gameUserRepository->autoLogin($game['game']['id'], $game['user_name'], $game['user_pass']);
        if ($result['success'] == 'true') {
            return redirect()->away($result['url']);
        }

        return redirect()->route('customer.game.index');

    }

    public function loginGameCredit(Request $request)
    {
        $gamelist = core()->getGame();
        //        dd($gamelist);
        $result = $this->gameUserFreeRepository->getOneUser($this->id(), $gamelist->code, false);

        $game = collect($result['data']->toArray())->only(['user_name', 'user_pass', 'game']);
        //        dd($game);
        $result = $this->gameUserFreeRepository->autoLogin($game['game']['id'], $game['user_name'], $game['user_pass']);
        if ($result['success'] == 'true') {
            return redirect()->away($result['url']);
        }

        return redirect()->route('customer.credit.game.index');

    }

    public function gameListLoginNew(Request $request)
    {
        $id = $request->input('id');
        $games = [];
        $user = $this->user();

        if (Cache::has('login_' . $user->code)) {
            exit;
        }

        Cache::put('login_' . $user->code, 'lock', now()->addSeconds(5));

        $result = $this->gameUserRepository->getGameList($id);
        if ($result['success'] === true) {
            $games = $result['games'];
            $games = collect($games)->map(function ($items) {
                $items['image'] = Str::of($items['img'])->replace(' ', '')->__toString();

                return (object)$items;

            });

            $first_value = collect(reset($games))->toArray();
            //            dd($first_value[0]->code);
            $game = $first_value[0]->code;

        } else {
            $first_value = [];
            $game = '';
        }

        $result = $this->gameUserRepository->autoLoginTransfer($user->code, $id, $game);

        return $this->sendResponseNew($result, 'complete');
    }

    public function changepro(Request $request)
    {
        $member = $this->memberRepository->find($this->id());
        if (!$member) {
            return $this->sendError('ไม่พบข้อมูลสมาชิก', 200);
        }

        $member->promotion = ($member->promotion == 'Y' ? 'N' : 'Y');
        $member->save();

        return $this->sendSuccess('อัพเดท การรับโปรโมชั่น เรียบร้อยแล้ว');
    }

    public function cats($id, Request $request)
    {
        $type = ['slot' => __('app.home.cat_slot'), 'casino' => __('app.home.cat_casino'), 'sport' => __('app.home.cat_sport'), 'huay' => 'แทงหวย', 'lotto' => __('app.home.cat_lotto'), 'fish' => __('app.home.cat_fish') , 'card' => __('app.home.cat_card') , 'poker' => __('app.home.cat_poker')];

        $name = $type[$id];
        $type = $this->gameTypeRepository->findOneByField('id', Str::upper($id));
        $gamess = $this->getProviders($id);

//        dd($games);
        $games = $gamess[$id];

        $gameTypes = $this->gameTypeRepository->findWhere(['enable' => 'Y', 'status_open' => 'Y']);
        $gameTypes->map(function ($item) {
            $item->icon = Storage::url('icon_cat/' . $item->icon);

            return $item;
        });

        return view($this->_config['view'], compact('games', 'name', 'id', 'type', 'gameTypes'));
    }

    public function catsfree($id, Request $request)
    {
        $type = ['slot' => __('app.home.cat_slot'), 'casino' => __('app.home.cat_casino'), 'sport' => __('app.home.cat_sport')];

        $name = $type[$id];
        $type = $this->gameTypeRepository->findOneByField('id', Str::upper($id));
        $games = $this->gameSeamlessRepository->orderBy('sort')->findWhere(['game_type' => strtoupper($id), 'status_open' => 'Y', 'enable' => 'Y', 'cashback' => 'Y']);
        $games = collect($games)->map(function ($items) {
            $items['filepic'] = Storage::url('game_img/' . $items->filepic . '?v=' . date('ymd'));

            return (object)$items;

        });

        return view($this->_config['view'], compact('games', 'name', 'id', 'type'));
    }


    public function getProviders($type = null)
    {
        $user = $this->id();
        $game = core()->getGame();
        $response = app('Gametech\Game\Repositories\GameUserRepository')
            ->providerListSingle($game->id, $user);

        if ($response['success'] === true) {
            $grouped = [];

            foreach ($response['provider'] as $item) {
                if(is_null($item['position']))continue;
                // --- บังคับ/ปรับประเภทตาม prefix และ normalize type ---
                $prefix = strtoupper($item['prefix'] ?? '');
                $types  = $item['gameType'] ?? null;

                // 1) บังคับตาม prefix
                if ($prefix === 'KM') {
                    $types = 'card';
                } elseif (in_array($prefix, ['KP', 'MPOKER'], true)) {
                    $types = 'poker';
                }

                // 2) normalize ชื่อ type เดิม
                if ($types === 'fishing or table_game') {
                    $types = 'fish';
                }

                // ตัดเคสว่างจริง ๆ หลัง override/normalize แล้ว
                if (empty($types)) {
                    continue;
                }

                // อัปเดตกลับเข้า item เพื่อให้ downstream ใช้ type เดียวกัน
                $item['gameType'] = $types;

                // จัดกลุ่ม
                $grouped[$types][] = $item;
            }

            // ระบุ $type → ส่งเฉพาะ group นั้น (หลัง sort)
            if ($type) {
                $target = $grouped[$type] ?? [];
                $sorted = $this->sortProvidersByRules($type, $target);
                $result = [
                    $type => $this->transformProviders($sorted),
                ];
            } else {
                // ไม่ระบุ type → ส่งทุก group แยก key (หลัง sort ราย group)
                $result = [];
                foreach ($grouped as $groupType => $items) {
                    $sorted = $this->sortProvidersByRules($groupType, $items);
                    $result[$groupType] = $this->transformProviders($sorted);
                }
            }

            return $result;
        }

        return null;
    }

    /**
     * กฎการจัดเรียง:
     * - ถ้า group = slot → lobbyId 31 มาก่อนเสมอ
     * - ที่เหลือเรียงตาม position (ASC), ถ้าเท่ากันผูกด้วย lobbyId (ASC)
     * - หากไม่มี position → ดันไปท้ายสุด
     */
    private function sortProvidersByRules(string $groupType, array $items): array
    {
        usort($items, function ($a, $b) use ($groupType) {
            $isSlot = strtolower($groupType) === 'slot';

            $aLobby = (int)($a['lobbyId'] ?? 0);
            $bLobby = (int)($b['lobbyId'] ?? 0);

            if ($isSlot) {
                if ($aLobby === 31 && $bLobby !== 31) return -1;
                if ($bLobby === 31 && $aLobby !== 31) return 1;
            }

            $pa = $a['position'] ?? PHP_INT_MAX;
            $pb = $b['position'] ?? PHP_INT_MAX;

            // กันกรณี position เป็น string ตัวเลข
            if (is_string($pa) && ctype_digit($pa)) $pa = (int)$pa;
            if (is_string($pb) && ctype_digit($pb)) $pb = (int)$pb;

            if ($pa === $pb) {
                return $aLobby <=> $bLobby;
            }
            return $pa <=> $pb;
        });

        return $items;
    }


    private function transformProviders(array $items): array
    {
        return array_map(function ($item) {
            return [
                'provider' => $item['lobbyId'],
                'providerTier' => 'vvip',
                'providerName' => $item['lobbyName'],
                'providerType' => $item['gameType'],
                'logoURL' => 'https://frontgame.sgp1.digitaloceanspaces.com/2022theme/provider/' . strtolower($item['prefix']) . '.jpg',
                'logoTransparentURL' => 'https://frontgame.sgp1.digitaloceanspaces.com/2022theme/provider/' . strtolower($item['prefix']) . '.jpg',
                'status' => $item['maintainance'] === false ? 'ACTIVE' : 'INACTIVE',
                'detailStatus' => 'Y',
                'gameList' => $item['gameList'],
                'maintainance' => $item['maintainance'],
                'endMaintenance' => core()->formatDate($item['endMaintenance'],'Y-m-d H:i'),
                'prefix' => $item['prefix'],
            ];
        }, $items);
    }

    public function getGames($type, $provider)
    {

        $game = core()->getGame();

        if ($provider === 25) {
            $oldtype = 'fish';
        } elseif (in_array($provider, [53, 107], true)) {
            $oldtype = 'casino';
        }else{
            $oldtype = $type;
        }

        $response = app('Gametech\Game\Repositories\GameUserRepository')->gameListSingle($game->id, $oldtype, $provider);
        $gamelist = Arr::get($response, 'games', Arr::get($response, 'result', []));

// กันเคส API เพี้ยน type ไม่ใช่ array
        if (!is_array($gamelist)) {
            $gamelist = [];
        }

//        $gamelist = $games['games'];
        $transformedList = array_map(function ($item) use ($type, $provider) {
            return [
                "id" => $item["gameCode"],
                "provider" => $provider, // ปรับจาก $item["product"] ถ้าอยาก map อัตโนมัติ
                "providerLogo" => [
                    "logoURL" => "",
                    "logoMobileURL" => "",
                    "logoTransparentURL" => ""
                ],
                "gameName" => $item["gameName"],
                "gameCategory" => 'kickoff',
                "gameType" => $type,
                "image" => [
                    "vertical" => $item["gameImage"],
                    "horizontal" => $item["gameImage1"],
                    "banner" => ""
                ],
                "status" => 'ACTIVE',
                "rtp" => round(mt_rand(96000, 98000) / 1000, 8), // mock RTP
                "online" => rand(50, 100) // mock online player count
            ];
        }, $gamelist);

        return $transformedList;
    }
}
