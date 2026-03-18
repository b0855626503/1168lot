<?php

namespace Gametech\Wallet\Http\Controllers;

use Gametech\Core\Repositories\CheckinRepository;
use Gametech\Member\Repositories\MemberCheckinRepository;
use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CheckinController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected $memberRepository;

    protected $checkinRepository;

    protected $memberCheckinRepository;

    /**
     * Create a new Repository instance.
     * @param MemberRepository $memberRepo
     */
    public function __construct
    (
        MemberRepository        $memberRepo,
        MemberCheckinRepository $memberCheckinRepo,
        CheckinRepository       $checkinRepo
    )
    {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->memberRepository = $memberRepo;

        $this->memberCheckinRepository = $memberCheckinRepo;

        $this->checkinRepository = $checkinRepo;

    }

    public function index()
    {
        $profile = $this->memberRepository->getAff($this->id());

        $banks[] = ['method' => 'contributor', 'name' => 'รายชื่อเพื่อนที่แนะนำมา'];
        $banks[] = ['method' => 'contributor_income', 'name' => 'รายได้จากเพื่อนที่แนะนำมา'];

        $banks = collect($banks);

        return view($this->_config['view'], compact('profile', 'banks'));
    }

    public function indextest()
    {
        $profile = $this->memberRepository->getAffTest($this->id());
        dd($profile);

        return view($this->_config['view'], compact('profile'));
    }


    public function store()
    {
        $result['success'] = true;
        $date = now()->toDateString();
        $user = $this->user();
        $userId = $user->code;
        $userName = $user->user_name;
        $check = $this->memberCheckinRepository->findOneWhere(['member_code' => $userId, 'date_check' => $date, 'enable' => 'Y']);
        if ($check) {
            $result['success'] = false;
            $result['message'] = 'มีรายการเช็คชื่อในระบบแล้ว ไม่สามารถ เช็คชื่อซ้ำได้';
            return json_encode($result);
        }

        $event = $this->checkinRepository->scopeQuery(function ($query) use ($date) {
            return $query->where('enable', 'Y')->whereRaw(DB::raw("? between date_start and date_stop"), [$date]);
        })->first();

        if (!$event) {
            $result['success'] = false;
            $result['message'] = 'ไม่พบช่วงเวลาที่กำหนดให้ เช็คชื่อ';
            return json_encode($result);
        }

        $this->memberCheckinRepository->create([
            'check_code' => $event->code,
            'date_check' => $date,
            'member_code' => $userId,
            'ip' => request()->ip(),
            'enable' => 'Y',
            'user_create' => $userName,
            'user_update' => $userName
        ]);

        $result['success'] = true;
        $result['message'] = 'เช็คชื่อประจำวัน เรียบร้อยแล้ว';

        return json_encode($result);

    }

    public function history()
    {

        $date = now()->toDateString();
        $user = $this->user();
        $userId = $user->code;
        $userName = $user->user_name;
        $event = $this->checkinRepository->scopeQuery(function ($query) use ($date) {
            return $query->where('enable', 'Y')->whereRaw(DB::raw("? between date_start and date_stop"), [$date]);
        })->first();

        if (!$event) {
            return [];
        }

        $lists = $this->memberCheckinRepository->findWhere(['member_code' => $userId, 'check_code' => $event->code, 'enable' => 'Y']);
        $result = collect($lists)->map(function ($items) {
            $item = (object)$items;
            return [
                'date' => $item->date_check
            ];

        });
//        dd($result->all());

        return json_encode($result);
    }

}
