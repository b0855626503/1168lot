<?php

namespace Gametech\Game\Repositories;

use Gametech\API\Models\GameListProxy;
use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Str;
use Throwable;

/**
 * Class GameUserRepository
 * @package Gametech\Game\Repositories
 */
class GameUserRepository extends Repository
{
    protected $skipCache = true;

    protected $cacheMinutes = 0;

    protected $gameMethod;

    private $gameRepository;

    private $gameSeamlessRepository;

    /**
     * GameRepository constructor.
     * @param GameRepository $gameRepo
     * @param App $app
     */
    public function __construct
    (
        GameRepository         $gameRepo,
        GameSeamlessRepository $gameSeamlessRepo,
        App                    $app
    )
    {
        $this->gameRepository = $gameRepo;

        $this->gameSeamlessRepository = $gameSeamlessRepo;

        $this->gameMethod = 'game';

        parent::__construct($app);
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model(): string
    {
        return \Gametech\Game\Models\GameUser::class;

    }

    public function getOneUserNew($code, $game_code)
    {
        $return['success'] = false;

        $result = $this->find($code);
        $game = $this->gameRepository->find($game_code);
        $response = $this->checkBalance($game->id, $result->user_name);
        if ($response['success'] === true) {
            $return['success'] = true;
            $result->balance = $response['score'];
            $result->save();
        } else {
            $return['msg'] = 'ไม่สามารถตรวจสอบ ยอดเงินจากค่ายเกมได้';
        }

        $outstanding = ($response['outstanding'] ?? 0);
        if ($outstanding > 0) {
            $return['success'] = false;
            $return['msg'] = 'พบยอด Outstanding Credit';
        }


        $return['data'] = $result;
        return $return;

    }

    public function getGameList($product_id,$method='seamless')
    {

        $product_id = Str::upper($product_id);
        $game = core()->getGame($method);

        return $this->gameList($game->id, $product_id);

    }


    public function getOneUser($id, $game, $update = true): array
    {
        $return['new'] = false;
        $return['connect'] = true;
        $return['success'] = false;
        $return['msg'] = 'พบปัญหาบางประการ โปลดลองใหม่อีกครั้ง';

        $result = $this->with(['game' => function ($query) {
            $query->active()->open()->select('code', 'id', 'filepic', 'game_type', 'name', 'link_ios', 'link_android', 'link_web', 'autologin');
        }])->with(['promotion'])->where('enable', 'Y')->where('game_code', $game)->where('member_code', $id)->first();


        if (empty($result)) {
            $return['new'] = true;
            $return['success'] = false;
            $return['data'] = null;
            return $return;
        }

        if ($update) {

            $response = $this->checkBalance($result->game->id, $result->user_name);

            if ($response['success'] === true) {
                $return['connect'] = $response['connect'];
                $return['success'] = true;
                $return['msg'] = 'อัพเดท Wallet แล้ว';
                $result->balance = $response['score'];
                $result->save();


            } else {

                $return['connect'] = $response['connect'];
                $return['success'] = false;
                $return['msg'] = $response['msg'];
            }


        } else {

            $return['connect'] = true;
            $return['success'] = true;
            $return['msg'] = 'ไม่ได้อัพเดท Wallet';
        }


        $return['data'] = $result;
        return $return;

    }

    public function getUser($id, $getall = false, $getturn = false, $withdraw = false)
    {
        $config = core()->getConfigData();
        $results = $this->gameRepository->orderBy('sort')->findWhere(['status_open' => 'Y', 'enable' => 'Y', ['filepic', '<>', '']], array('code', 'id', 'filepic', 'game_type', 'name'));

        foreach ($results as $i => $result) {
            $score = 0;
            $game_user = $this->findOneWhere(['member_code' => $id, 'game_code' => $result->code, 'enable' => 'Y']);
            $game_id = preg_replace('/\d/', '', $result->id);
            $game = ucfirst($game_id);
            if (!is_null($game_user)) {
                if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game . 'Repository.php'))) {
                    $response = app('Gametech\Game\Repositories\Games\\' . $game . 'Repository', ['method' => $this->gameMethod])->viewBalance($game_user->user_name);
                    if ($response['success'] === true) {

                        $score = $response['score'];
                        $game_user->balance = $score;
                        $game_user->save();

                    } else {

                        $score = $game_user->balance;

                    }

                    $results[$i]->user_code = $game_user->code;
                    $results[$i]->user_name = $game_user->user_name;
                    $results[$i]->balance = number_format($score, 2);

                } else {

                    $results[$i]->user_code = $game_user->code;
                    $results[$i]->user_name = $game_user->user_name;
                    $results[$i]->balance = number_format($game_user->balance, 2);
                }

                if ($getturn) {
                    if ($game_user->pro_code > 0) {
                        if ($withdraw) {
                            $turn_balance = doubleval($game_user->amount_balance);
                            if ($score < $turn_balance) {
                                unset($results[$i]);
                            }
                        }
                    }
                    if ($score > $config->mintransfer_pro && $config->mintransfer_pro > 0) {
                        unset($results[$i]);
                    }

                }

            } else {
                if ($getall == false) {
                    unset($results[$i]);
                } else {
                    $results[$i]->user_code = '';
                    $results[$i]->user_name = '';
                    $results[$i]->balance = number_format(0, 2);

                }
            }
        }

        return $results;
    }

    public function addGameUser($game_code, $member_code, $data, $debug = false): array
    {
        $return['success'] = false;

        $games = $this->gameRepository->findOneByField('code', $game_code);
        $game_id = preg_replace('/\d/', '', $games->id);
        $game = ucfirst($game_id);
//        dd($game);

        if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game . 'Repository.php'))) {
            $result = app('Gametech\Game\Repositories\Games\\' . $game . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->addGameAccount($data);


            if ($debug) {
                return $result;
            }

            if ($result['success'] === true) {

                $param = [
                    'game_code' => $game_code,
                    'member_code' => $member_code,
                    'user_name' => $result['user_name'],
                    'user_pass' => $result['user_pass'],
                    'balance' => '0',
                    'enable' => 'Y',
                    'user_create' => $data['user_create'],
                    'user_update' => $data['user_create']
                ];

                try {

                    $result_add = $this->create($param);
                    if ($result_add->code) {
                        $data->game_user = $result['user_name'];
                        $data->save();
                        $return['success'] = true;
                        $return['data'] = $result_add;
                    } else {
                        $return['success'] = false;
                    }

                } catch (Throwable $e) {
                    report($e);
                    $return['success'] = false;
                }

            } else {
                $return['msg'] = $result['msg'];
            }
        }

        return $return;
    }

    public function changeGamePass($game_code, $id, $data, $debug = false): array
    {
        $return['success'] = false;

        $games = $this->gameRepository->findOneByField('code', $game_code);
        $game_id = preg_replace('/\d/', '', $games->id);
        $game = ucfirst($game_id);
        if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game . 'Repository.php'))) {
            $return = app('Gametech\Game\Repositories\Games\\' . $game . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->changePass($data);
            if ($return['success'] === true) {

                $game_user = $this->findOrFail($id);
                $game_user->user_pass = $data['user_pass'];
                $game_user->save();

            }
        }

        return $return;
    }

    public function UserDeposit($game_code, $user_name, $total, $update = true, $debug = false): array
    {
        $return['success'] = false;

        $games = $this->gameRepository->find($game_code);
        $game_id = preg_replace('/\d/', '', $games->id);
        $game = ucfirst($game_id);

        if ($debug) {
            return app('Gametech\Game\Repositories\Games\\' . $game . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->deposit($user_name, $total);


        }

        $user = $this->findOneWhere(['user_name' => $user_name, 'game_code' => $game_code]);
        if (!is_null($games) && !is_null($user)) {
            if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game . 'Repository.php'))) {
                $return = app('Gametech\Game\Repositories\Games\\' . $game . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->deposit($user_name, $total);
                if ($update) {

                    if ($return['success'] == true) {

                        $user->balance = $return['after'];
                        $user->save();

                    }
                }
            }
        }

        return $return;
    }

    public function UserWithdraw($game_code, $user_name, $total, $update = true, $debug = false): array
    {
        $return['success'] = false;

        $games = $this->gameRepository->find($game_code);
        $game_id = preg_replace('/\d/', '', $games->id);
        $game = ucfirst($game_id);

        if ($debug) {
            return app('Gametech\Game\Repositories\Games\\' . $game . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->withdraw($user_name, $total);

        }

        $user = $this->findOneWhere(['user_name' => $user_name, 'game_code' => $game_code]);
        if (!is_null($games) && !is_null($user)) {
            if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game . 'Repository.php'))) {

                $return = app('Gametech\Game\Repositories\Games\\' . $game . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->withdraw($user_name, $total);
                if ($update) {
                    if ($return['success'] == true) {
                        $user->balance = $return['after'];
                        $user->save();
                    }
                }
            }
        }

        return $return;
    }

    public function checkBalance($game_id, $user_name, $debug = false): array
    {
        $result['success'] = false;
        $result['msg'] = 'เกมดังกล่าว ยังไม่พร้อมให้บริการในขณะนี้';


        $game_id = preg_replace('/\d/', '', $game_id);
        $game_id = ucfirst($game_id);

        if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game_id . 'Repository.php'))) {
            return app('Gametech\Game\Repositories\Games\\' . $game_id . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->viewBalance($user_name);

        }


        return $result;
    }

    public function checkBalanceSeamless($game_id, $user_name, $product_id, $debug = false): array
    {
        $result['success'] = false;
        $result['msg'] = 'เกมดังกล่าว ยังไม่พร้อมให้บริการในขณะนี้';


        $game_id = preg_replace('/\d/', '', $game_id);
        $game_id = ucfirst($game_id);

        if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game_id . 'Repository.php'))) {
            return app('Gametech\Game\Repositories\Games\\' . $game_id . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->viewBalance($user_name, $product_id);

        }


        return $result;
    }

    public function autoLoginSeamless($member_code, $product_id, $game_code, $debug = false): array
    {
        $result['success'] = false;
        $result['msg'] = 'เกมดังกล่าว ยังไม่พร้อมให้บริการในขณะนี้';

        $games = $this->gameSeamlessRepository->findOneWhere(['id' => $product_id, 'enable' => 'Y']);
        $game = core()->getGame($games->method);
//        dd($game);
        $user = $this->findOneWhere(['member_code' => $member_code, 'game_code' => $game->code]);
//        dd($user);
        if (!is_null($games) && !is_null($user)) {
            if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . ucfirst($games->method) . 'Repository.php'))) {
                return app('Gametech\Game\Repositories\Games\\' . ucfirst($games->method) . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->login(['username' => $user->user_name, 'productId' => $product_id, 'gameCode' => $game_code]);

            }
        }


        return $result;
    }

    public function autoLoginTransfer($member_code, $product_id, $game_code, $debug = false): array
    {
        $result['success'] = false;
        $result['msg'] = 'เกมดังกล่าว ยังไม่พร้อมให้บริการในขณะนี้';
//        $game = core()->getGame();
        $game = $this->gameRepository->findOneWhere(['id' => $product_id, 'status_open' => 'Y', 'enable' => 'Y']);
        $user = $this->findOneWhere(['member_code' => $member_code, 'game_code' => $game->code]);
        $game_id = preg_replace('/\d/', '', $game->id);
        $game_id = ucfirst($game_id);
        if (!is_null($game) && !is_null($user)) {
            if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game_id . 'Repository.php'))) {
                return app('Gametech\Game\Repositories\Games\\' . $game_id . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->login(['username' => $user->user_name, 'productId' => $product_id, 'gameCode' => $game_code]);

            }
        }


        return $result;
    }


    public function autoLoginSingle($member_code, $product_id, $game_code, $provider, $debug = false): array
    {
        $result['success'] = false;
        $result['msg'] = 'เกมดังกล่าว ยังไม่พร้อมให้บริการในขณะนี้';
        $game = core()->getGame();
//        $game = $this->gameRepository->findOneWhere(['id' => $provider, 'status_open' => 'Y', 'enable' => 'Y']);
        $user = $this->findOneWhere(['member_code' => $member_code, 'game_code' => $game->code]);
        $game_id = preg_replace('/\d/', '', $game->id);
        $game_id = ucfirst($game_id);
        if (!is_null($game) && !is_null($user)) {
            if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game_id . 'Repository.php'))) {
                return app('Gametech\Game\Repositories\Games\\' . $game_id . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->login(['username' => $user->user_name, 'password' => $user->user_pass, 'productId' => $product_id, 'gameCode' => $game_code , 'gamelist' => $game->gamelist , 'provider' => $provider]);

            }
        }


        return $result;
    }

    public function autoLoginApi($member_code, $product_id, $method, $debug = false): array
    {
        $result['success'] = false;
        $result['msg'] = 'เกมดังกล่าว ยังไม่พร้อมให้บริการในขณะนี้';
//        $game = core()->getGame();
        $game = $this->gameRepository->findOneWhere(['id' => $method, 'status_open' => 'Y', 'enable' => 'Y']);
        $user = $this->findOneWhere(['member_code' => $member_code, 'game_code' => $game->code]);
        $game_id = preg_replace('/\d/', '', $game->id);
        $game_id = ucfirst($game_id);
        if (!is_null($game) && !is_null($user)) {
            if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game_id . 'Repository.php'))) {
                return app('Gametech\Game\Repositories\Games\\' . $game_id . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->login(['username' => $user->user_name, 'password' => $user->user_pass, 'productId' => $product_id]);

            }
        }


        return $result;
    }

    public function autoLogin($game_id, $user_name, $password, $debug = false): array
    {
        $result['success'] = false;
        $result['msg'] = 'เกมดังกล่าว ยังไม่พร้อมให้บริการในขณะนี้';
        $game_id = preg_replace('/\d/', '', $game_id);
        $game_id = ucfirst($game_id);

        if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game_id . 'Repository.php'))) {
            return app('Gametech\Game\Repositories\Games\\' . $game_id . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->login($user_name, $password);

        }


        return $result;
    }

    public function gameList($game_id, $product_id, $debug = false): array
    {
        $result['success'] = false;
        $result['msg'] = 'ไม่สามารถดึงข้อมูล รายการเกมได้';
        $game_id = preg_replace('/\d/', '', $game_id);
        $game_id = ucfirst($game_id);



//        $games = GameListProxy::where('product',$product_id)->where('enable',true)->get()->toArray();
//        dd($product_id);
        if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game_id . 'Repository.php'))) {
//           return app('Gametech\Game\Repositories\Games\\' . $game_id . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->gameList($product_id);
            $response = app('Gametech\Game\Repositories\Games\\' . $game_id . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->gameList($product_id);
            if($response['success']){
                $result['success'] = true;
                $result['games'] = GameListProxy::where('product',$product_id)->where('enable',true)->orderByDesc('click')->orderByDesc('rank')->get()->toArray();
            }else{
                $result['success'] = true;
                $result['games'] = GameListProxy::where('product',$product_id)->where('enable',true)->orderByDesc('click')->orderByDesc('rank')->get()->toArray();

            }
        }


        return $result;
    }


    public function gameListSingle($game_id, $product_id,$provider, $debug = false): array
    {
        $result['success'] = false;
        $result['msg'] = 'ไม่สามารถดึงข้อมูล รายการเกมได้';
        $game_id = preg_replace('/\d/', '', $game_id);
        $game_id = ucfirst($game_id);

//        dd($game_id);

//        $games = GameListProxy::where('product',$product_id)->where('enable',true)->get()->toArray();
//        dd($product_id);
        if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game_id . 'Repository.php'))) {
//           return app('Gametech\Game\Repositories\Games\\' . $game_id . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->gameList($product_id);
            $response = app('Gametech\Game\Repositories\Games\\' . $game_id . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->gameList($product_id,$provider);
            if($response['success']){
                $result['success'] = true;
                $result['msg'] = $response['msg'];
                $result['games'] = $response['games'];
            }
        }


        return $result;
    }


    public function providerListSingle($game_id,$member_code, $debug = false): array
    {
        $result['success'] = false;
        $result['msg'] = 'ไม่สามารถดึงข้อมูล รายการเกมได้';
        $game_id = preg_replace('/\d/', '', $game_id);
        $game_id = ucfirst($game_id);
        $user = $this->findOneWhere(['member_code' => $member_code]);

//        dd($game_id);


//        $games = GameListProxy::where('product',$product_id)->where('enable',true)->get()->toArray();
//        dd($product_id);
        if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game_id . 'Repository.php'))) {
//           return app('Gametech\Game\Repositories\Games\\' . $game_id . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->gameList($product_id);
            $response = app('Gametech\Game\Repositories\Games\\' . $game_id . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->providerList(['username' => $user->user_name , 'password' => $user->user_pass]);
            if($response['success']){
                $result['success'] = true;
                $result['msg'] = $response['msg'];
                $result['provider'] = $response['provider'];
            }
        }


        return $result;
    }


    public function providerListSinglePublic($game_id, $debug = false): array
    {
        $result['success'] = false;
        $result['msg'] = 'ไม่สามารถดึงข้อมูล รายการเกมได้';
        $game_id = preg_replace('/\d/', '', $game_id);
        $game_id = ucfirst($game_id);


//        dd($game_id);


//        $games = GameListProxy::where('product',$product_id)->where('enable',true)->get()->toArray();
//        dd($product_id);
        if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game_id . 'Repository.php'))) {
//           return app('Gametech\Game\Repositories\Games\\' . $game_id . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->gameList($product_id);
            $response = app('Gametech\Game\Repositories\Games\\' . $game_id . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->providerListPublic();
            if($response['success']){
                $result['success'] = true;
                $result['msg'] = $response['msg'];
                $result['provider'] = $response['provider'];
            }
        }


        return $result;
    }


    public function GameLog($data): array
    {
        if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/KickoffRepository.php'))) {
            return app('Gametech\Game\Repositories\Games\KickoffRepository', ['method' => $this->gameMethod, 'debug' => false])->gameLog($data);
        }

        return [];
    }

    public function UserDepositTransfer($product_id,$game_code, $user_name, $total, $update = true, $debug = false): array
    {
        $return['success'] = false;

        $games = $this->gameRepository->find($game_code);
        $game_id = preg_replace('/\d/', '', $games->id);
        $game = ucfirst($game_id);
//        dd($total);
        if ($debug) {
            return app('Gametech\Game\Repositories\Games\\' . $game . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->deposit($user_name, $total,$product_id);


        }

        $user = $this->findOneWhere(['user_name' => $user_name, 'game_code' => $game_code]);
        if (!is_null($games) && !is_null($user)) {
            if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game . 'Repository.php'))) {
                $return = app('Gametech\Game\Repositories\Games\\' . $game . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->deposit($user_name, $total,$product_id);
                if ($update) {

                    if ($return['success'] === true) {

                        $user->balance = $return['after'];
                        $user->save();

                    }
                }
            }
        }

        return $return;
    }

    public function UserWithdrawTransfer($product_id,$game_code, $user_name, $total, $update = true, $debug = false): array
    {
        $return['success'] = false;

        $games = $this->gameRepository->find($game_code);
        $game_id = preg_replace('/\d/', '', $games->id);
        $game = ucfirst($game_id);
//        dd($game);
        if ($debug) {
            return app('Gametech\Game\Repositories\Games\\' . $game . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->withdraw($user_name, $total,$product_id);

        }

        $user = $this->findOneWhere(['user_name' => $user_name, 'game_code' => $game_code]);
        if (!is_null($games) && !is_null($user)) {
            if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game . 'Repository.php'))) {

                $return = app('Gametech\Game\Repositories\Games\\' . $game . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->withdraw($user_name, $total,$product_id);
                if ($update) {
                    if ($return['success'] === true) {
                        $user->balance = $return['after'];
                        $user->save();
                    }
                }
            }
        }

        return $return;
    }


    public function checkOutStanding($game_id, $user_name, $product_id, $debug = false): array
    {
        $result['success'] = false;
        $result['msg'] = 'เกมดังกล่าว ยังไม่พร้อมให้บริการในขณะนี้';


        $game_id = preg_replace('/\d/', '', $game_id);
        $game_id = ucfirst($game_id);

        if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game_id . 'Repository.php'))) {
            return app('Gametech\Game\Repositories\Games\\' . $game_id . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->outStanding($user_name, $product_id);

        }


        return $result;
    }

    public function checkOutStandings($game_id, $user_name, $debug = false): array
    {
        $result['success'] = false;
        $result['msg'] = 'เกมดังกล่าว ยังไม่พร้อมให้บริการในขณะนี้';


        $game_id = preg_replace('/\d/', '', $game_id);
        $game_id = ucfirst($game_id);

        if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game_id . 'Repository.php'))) {
            return app('Gametech\Game\Repositories\Games\\' . $game_id . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->outStandings($user_name);

        }


        return $result;
    }

    public function addFreeGame($data, $debug = false): array
    {
        $result['success'] = false;
        $result['msg'] = 'เกมดังกล่าว ยังไม่พร้อมให้บริการในขณะนี้';

        $games = $this->gameSeamlessRepository->findOneWhere(['id' => $data['product_id'], 'enable' => 'Y']);
        $game = core()->getGame($games->method);
//        dd($game);
        $user = $this->findOneWhere(['member_code' => $data['member_code'], 'game_code' => $game->code]);
//        dd($user);
        if (!is_null($games) && !is_null($user)) {
            if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . ucfirst($games->method) . 'Repository.php'))) {
                return app('Gametech\Game\Repositories\Games\\' . ucfirst($games->method) . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->freegame($data);

            }
        }


        return $result;
    }

    public function betLimit($product_id)
    {
        if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/SeamlessRepository.php'))) {

            return app('Gametech\Game\Repositories\Games\SeamlessRepository', ['method' => $this->gameMethod, 'debug' => false])->betLimit($product_id);

        }
    }

    public function checkUserTurn($game_code, $member_code, $data, $debug = false): array
    {
        $return['success'] = false;

        $games = $this->gameRepository->findOneByField('code', $game_code);
        $game_id = preg_replace('/\d/', '', $games->id);
        $game = ucfirst($game_id);

        if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game . 'Repository.php'))) {
            return  app('Gametech\Game\Repositories\Games\\' . $game . 'Repository', ['method' => $this->gameMethod, 'debug' => $debug])->GetTurn($data);
        }

        return $return;
    }
}
