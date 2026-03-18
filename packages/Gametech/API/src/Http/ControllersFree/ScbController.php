<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\Member\Models\MemberCreditLogProxy;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Promotion\Repositories\PromotionContentRepository;
use Gametech\Promotion\Repositories\PromotionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ScbController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberRepository;

    protected $gameUserRepository;

    protected $promotionRepository;

    protected $proContentRepository;

    public function __construct(
        BankPaymentRepository      $repository,
        PromotionRepository        $promotionRepo,
        PromotionContentRepository $proContentRepo
    )
    {
        $this->_config = request('_config');

//        $this->middleware('api');

        $this->repository = $repository;

        $this->promotionRepository = $promotionRepo;

        $this->proContentRepository = $proContentRepo;

    }

    public function index(Request $request)
    {


    }

    public function withdraw(Request $request)
    {
        $data = MemberCreditLogProxy::where('KIND', 'CONFIRM_WD')->with('member')->get()->toArray();
        $games = collect($data)->map(function ($items) {
            $item = (object)$items;
            return [
                'date' => $item->date_create,
                'amount' => $item->amount,
                'name' => $item->member['name'],
                'user' => $item->member['user_name']
            ];

        });

        return Response::json($games);
    }

    public function trueWallet($mobile, Request $request)
    {
        $data = MemberCreditLogProxy::where('KIND', 'CONFIRM_WD')->with('member')->get()->toArray();
        $games = collect($data)->map(function ($items) {
            $item = (object)$items;
            return [
                'date' => $item->date_create,
                'amount' => $item->amount,
                'name' => $item->member['name'],
                'user' => $item->member['user_name']
            ];

        });

        return Response::json($games);
    }

    public function promotion()
    {
        $data = $this->promotionRepository->orderBy('sort')->findWhere(['enable' => 'Y', 'use_wallet' => 'Y', ['code', '<>', 0]])->toArray();
//        $arr2 = $this->proContentRepository->orderBy('sort')->findWhere(['enable' => 'Y', ['code', '<>', 0]])->pluck('name_th as name','content','filepic');

//        $arr =

        $response = collect($data)->map(function ($items) {
            $item = (object)$items;
            return [
                'filepic' => $item->filepic,
                'content' => $item->content
            ];

        });

        return Response::json($response);
    }

    public function promotion_content()
    {
        $data = $this->proContentRepository->orderBy('sort')->findWhere(['enable' => 'Y', ['code', '<>', 0]])->toArray();
//        $arr2 = $this->proContentRepository->orderBy('sort')->findWhere(['enable' => 'Y', ['code', '<>', 0]])->pluck('name_th as name','content','filepic');

//        $arr =

        $response = collect($data)->map(function ($items) {
            $item = (object)$items;
            return [
                'filepic' => $item->filepic,
                'content' => $item->content
            ];

        });

        return Response::json($response);
    }


}
