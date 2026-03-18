<?php

namespace Gametech\Game\Http\Controllers;


use App\Http\Controllers\AppBaseController;
use Gametech\Game\Repositories\GameRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CallbackController extends AppBaseController
{

    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected $gameRepository;


    /**
     * Create a new controller instance.
     *
     * @param GameRepository $gameRepo
     */
    public function __construct
    (
        GameRepository $gameRepo
    )
    {
        $this->_config = request('_config');

        $this->gameRepository = $gameRepo;
    }

    public function GameCurl($param, $action)
    {

        $response = rescue(function () use ($param, $action) {

            $url = 'https://api.hentory.io/' . $action;

            return Http::timeout(15)->withHeaders([
                'Content-Type' => 'application/json'
            ])->withOptions([
                'debug' => false
            ])->post($url, $param);


        }, function ($e) {

            return $e->response;

        }, true);


        $result = $response->json();


        $result['msg'] = ($result['message'] ?? 'พบปัญหาบางประการ');


        if ($response->successful()) {
            if ($result['code'] == 0) {
                $result['success'] = true;
            } else {
                $result['success'] = false;
            }

        } else {
            $result['success'] = false;
        }

        return $result;

    }

    public function authenticate(Request $request)
    {

        $param = [
          'token' => request()->getSession()->getId(),
            'ip' => $request->ip(),
            'timestamp' => now()->timestamp
        ];

//        dd($param);

        $response = $this->GameCurl($param, 'authenticate-token');

        dd($response);
    }

    public function verifySession(Request $request)
    {

        $param = [
            'data' => [
                'player_name' => 'boatjunior',
                'nickname' => 'boatjunior',
                'currency' => 'THB',
                'reminder_time' => now()->timestamp
            ],
            'error' => null
        ];

        return $param;
    }
}
