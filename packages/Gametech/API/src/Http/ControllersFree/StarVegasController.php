<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StarVegasController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberRepository;

    protected $gameUserRepository;

    public function __construct(
        BankPaymentRepository $repository,
        MemberRepository      $memberRepo,
        GameUserRepository    $gameUserRepo
    )
    {
        $this->_config = request('_config');

        $this->middleware('api');

        $this->repository = $repository;

        $this->memberRepository = $memberRepo;

        $this->gameUserRepository = $gameUserRepo;
    }


    public function getBalance(Request $request)
    {
        $account = $request->input('PlayerAccount');

        $url = 'https://apithdemo.igamingdemo.com/player/info';

        $sign['PlayerAccount'] = $account;
        $sign['PrivateKey'] = 'sFsf^sS3genIgJ^BJanX';
//        $postString = http_build_query($sign, null, '&');
        $postString = "";
        foreach ($sign as $keyR => $value) {
            $postString .= $keyR . '=' . $value . '&';
        }
        $postString = substr($postString, 0, -1);
//        dd($postString);
        $encrypt = base64_encode(hash("sha256", $postString, true));
//        dd($encrypt);
        $response = Http::timeout(15)->withHeaders([
            'merchantName' => 'Starvegass',
            'sign' => $encrypt
        ])->withOptions(['debug' => true, 'verify' => false])->post($url, ['PlayerAccount' => $account]);

        return $response;
    }


}
