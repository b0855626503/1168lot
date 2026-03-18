<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Promotion\Repositories\PromotionContentRepository;
use Gametech\Promotion\Repositories\PromotionRepository;
use Illuminate\Http\Request;

class WebhookController extends AppBaseController
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

    public function index($mobile, Request $request)
    {

        $path = storage_path('logs/tw/webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($request->all(), true));

    }


}
