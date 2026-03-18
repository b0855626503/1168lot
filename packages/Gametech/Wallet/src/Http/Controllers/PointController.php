<?php

namespace Gametech\Wallet\Http\Controllers;


use Gametech\Core\Repositories\RewardRepository;

use Gametech\Member\Repositories\MemberRepository;
use Gametech\Member\Repositories\MemberRewardLogRepository;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class PointController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected $rewardRepository;

    protected $memberRepository;

    protected $memberRewardLogRepository;


    /**
     * Create a new Repository instance.
     *
     * @param RewardRepository $rewardRepo
     * @param MemberRepository $memberRepo
     * @param MemberRewardLogRepository $memberRewardLogRepo
     */
    public function __construct
    (
        RewardRepository $rewardRepo,
        MemberRepository $memberRepo,
        MemberRewardLogRepository  $memberRewardLogRepo
    )
    {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->rewardRepository = $rewardRepo;

        $this->memberRepository = $memberRepo;

        $this->memberRewardLogRepository = $memberRewardLogRepo;
    }

    public function index()
    {

        $rewards = $this->loadReward();

        $profile = $this->user();



        return view($this->_config['view'], compact('rewards','profile'));
    }

    public function loadReward(): Collection
    {
        $responses = collect($this->rewardRepository->loadReward()->toArray());

        $responses = $responses->map(function ($items){
            $item = (object)$items;
            $remain = ($item->qty - $item->exchange_count);
            $image = Storage::url('reward_img/'.$item->filepic);

//            $img = Image::make($image)->resize(300, 200);

            return [
                'code' => $item->code,
                'name' => $item->name,
                'image' => $image,
                'qty' => $item->qty,
                'remain' => $remain,
                'point' => $item->points,
                'shortcontent' => $item->short_details
            ];

        });


        return $responses;
    }

    public function store(Request $request): JsonResponse
    {
        $id = $request->input('id');


        $chk = $this->rewardRepository->loadRewardID($id);

        if($chk){
            $response = $this->memberRewardLogRepository->exchangeReward($id,$this->user());
            if($response['success'] === true){
                return $this->sendSuccess($response['message']);
            }else{
                return $this->sendError($response['message'],200);
            }
        }else{

            return $this->sendError('ไม่พบข้อมูลรางวัล',200);
        }



    }


    public function history()
    {
        $histories = $this->loadHistory();

        return view($this->_config['view'], compact('histories'));
    }

    public function loadHistory(): array
    {


        $responses = collect($this->memberRewardLogRepository->with('reward')->where('enable','Y')->where('member_code',$this->id())->withCasts([
            'date_create' => 'date:d/m/Y'
        ])->get())->whereNotNull('reward')->toArray();
//        dd($responses);

        return $responses;
    }


}
