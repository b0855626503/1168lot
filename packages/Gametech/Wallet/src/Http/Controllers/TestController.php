<?php

namespace Gametech\Wallet\Http\Controllers;



use Gametech\Game\Repositories\GameRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;


class TestController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;


    protected $gameRepository;

    protected $gameUserRepository;

    protected $method;

    /**
     * Create a new Repository instance
     * @param GameRepository $gameRepo
     * @param GameUserRepository $gameUserRepo
     */
    public function __construct
    (

        GameRepository $gameRepo,
        GameUserRepository $gameUserRepo
    )
    {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->gameRepository = $gameRepo;

        $this->gameUserRepository = $gameUserRepo;

        $this->method = 'game';
    }

    public function index(): array
    {
        $return['success'] = false;
        $username = 'DAX000112';

        $user_pass = "Aa" . rand(100000, 999999);
        $param = [
            'accountType' => 1,
            'timeStamp' => now()->format('YmdHis')
        ];


        $postString = Arr::query($param);


        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Pass-Key' => config($this->method.'.dreamtech.passkey'),
            'Session-Id' => request()->getSession()->getId(),
            'Hash' => md5($postString),
        ])->post(config($this->method.'.dreamtech.apiurl') .$username. '/authenticate', $param);

        if($response->successful()){
            $response = $response->json();
            dd($response);


            if($response['code'] == 'UNKNOWN_ERROR'){
                $param = [
                    'accountStatus' => 1,
                    'accountType' => 1,
                    'agentLoginName' => config($this->method.'.dreamtech.agent'),
                    'balance' => 0,
                    'birthDate' => '2021-04-01',
                    'email' => '',
                    'firstName' => 'mc',
                    'gender' => 'M',
                    'lastName' => '',
                    'loginName' => $username,
                    'mode' => 'real',
                    'password' => $user_pass,
                    'timeStamp' => now()->format('YmdHis')
                ];

                $postString = Arr::query($param);

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Pass-Key' => config($this->method.'.dreamtech.passkey'),
                    'Session-Id' => request()->getSession()->getId(),
                    'Hash' => md5($postString),
                ])->post(config($this->method.'.dreamtech.apiurl') . 'create-check-account', $param);

                if($response->successful()) {
                    $response = $response->json();
                    if(!empty($response['platformLoginId'])){
                        $return['msg'] = 'Complete';
                        $return['success'] = true;
                        $return['user_name'] = $username;
                        $return['user_pass'] = $user_pass;
                    }
                }

            }
        }




        return $return;
    }








}
