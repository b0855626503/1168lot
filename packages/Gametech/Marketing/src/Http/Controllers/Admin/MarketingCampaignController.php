<?php

namespace Gametech\Marketing\Http\Controllers\Admin;

use App\Http\Controllers\AppBaseController;
use Gametech\Marketing\DataTables\MarketingCampaignDataTable;
use Gametech\Marketing\DataTables\MarketingMemberDataTable;
use Gametech\Marketing\Repositories\MarketingCampaignRepository;
use Gametech\Marketing\Repositories\MarketingTeamRepository;
use Gametech\Marketing\Repositories\RegistrationLinkRepository;
use Gametech\Payment\Models\BankPaymentProxy;
use Gametech\Payment\Models\WithdrawProxy;
use Gametech\Payment\Models\WithdrawSeamlessProxy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketingCampaignController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $marketingTeamRepository;

    protected $registrationLinkRepository;

    public function __construct(
        MarketingCampaignRepository $repository,
        MarketingTeamRepository $marketingTeamRepository,
        RegistrationLinkRepository $registrationLinkRepository
    ) {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->marketingTeamRepository = $marketingTeamRepository;

        $this->registrationLinkRepository = $registrationLinkRepository;

    }

    public function index(MarketingCampaignDataTable $marketingCampaignDataTable)
    {
        return $marketingCampaignDataTable->render($this->_config['view']);
    }

    public function loadData(Request $request)
    {
        $id = $request->input('id');

        $data = $this->repository->find($id);
        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');

    }

    public function create(Request $request)
    {

        $data = json_decode($request['data'], true);

        $data['enable'] = true;

        $campaign = $this->repository->create($data);

        $team_id = $data['team_id'] ?? null;

        if ($campaign) {
            $link = $this->addRegisterLink($campaign, $team_id);
            if (! $link) {
                $link = $this->addRegisterLink($campaign, $team_id);
            }

        }

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function addRegisterLink($campaign, $team_id)
    {
        return $this->registrationLinkRepository->create([
            'code' => Str::random(20),
            'team_id' => $team_id,
            'campaign_id' => $campaign->id,
        ]);
    }

    public function edit(Request $request)
    {

        $id = $request->input('id');
        $status = $request->input('status');
        $method = $request->input('method');

        $data[$method] = $status;
        if ($method == 'is_ended') {
            $data['ended_at'] = now();
        }

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function update($id, Request $request)
    {

        $data = json_decode($request['data'], true);

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');

        $chk = $this->repository->find($id);

        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $this->repository->delete($id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function loadTeam()
    {
        $teams = [
            'value' => null,
            'text' => 'ทีมที่ดูแล / เกี่ยวข้อง',
        ];

        $responses = collect($this->marketingTeamRepository->where('enable', true)->get()->toArray());

        $responses = $responses->map(function ($items) {
            $item = (object) $items;

            return [
                'value' => $item->id,
                'text' => $item->name,
            ];

        })->prepend($teams);

        $result['teams'] = $responses;

        return $this->sendResponseNew($result, 'complete');
    }

    public function store(MarketingMemberDataTable $marketingMemberDataTable, $id)
    {

        $data = $this->repository->find($id);
        if (! $data) {
            session()->flash('error', 'ไม่พบข้อมูล แคมเปญ รหัสนี้');

            return redirect()->back();
        }

        $user = auth()->guard('admin')->user();

        if ($user->role->name === 'marketing') {
            $allowedUsers = array_map('trim', explode(',', strtolower($data->admin_username ?? '')));
            if (! in_array(strtolower($user->user_name), $allowedUsers)) {
                abort(403, 'คุณไม่มีสิทธิ์เข้าถึงแคมเปญนี้');
            }
        }

        //        $mbDataTable = $marketingMemberDataTable->html();

        $campaign_name = $data->name;

        return $marketingMemberDataTable->with('campaign_id', $id)->render($this->_config['view'], compact('id', 'campaign_name'));

        //        return view($this->_config['view'], compact('id', 'campaign_name'));

    }

    public function loadReport(Request $request)
    {
        $id = $request->input('id');
        $method = $request->input('method');
        $date = $request->input('date') ?? now()->toDateString();
        $startDate = $request->input('date_start') ?? now()->toDateString();
        $endDate = $request->input('date_end') ?? now()->toDateString();

        $campaign = $this->repository->find($id);
        if (! $campaign) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        switch ($method) {
            case 'register-all':
                $data = $campaign->members()->count();
                $result['sum'] = $data;
                break;

            case 'register-today':
                $data = $campaign->members()
                    ->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate])
                    ->count();
                $result['sum'] = $data;
                break;

            case 'register-all-deposit':
                $data = $campaign->members()
//                    ->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate])
                    ->whereNotBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate])
                    ->whereHas('deposits', function ($query) use ($date) {
                        // จะกรองให้เฉพาะ member ที่มีรายการฝาก
                        $query->where('status', 1)->where('enable', 'Y')->whereDate('date_approve', $date);
                    })
                    ->count();
                $result['sum'] = $data;
                break;

            case 'member-all-first-deposit':
                $data = $campaign->members()
                    ->whereHas('firstDeposit', function ($q) use ($startDate,$endDate) {
                        $q->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate]);
                    })
                    ->whereDoesntHave('deposits', function ($q) use ($startDate) {
                        $q->where('status', 1)
                            ->where('enable', 'Y')
                            ->whereDate('date_approve', '<', $startDate);
                    })
                    ->with(['firstDeposit' => function ($q) {
                        $q->select('member_topup', 'value', 'date_approve')
                            ->where('status', 1)
                            ->where('enable', 'Y')
                            ->orderBy('date_approve', 'asc');
                    }])->get()->sum(function ($member) {
                        return $member->firstDeposit->value ?? 0;
                    });
//                    ->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate])
//                    ->whereNotBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate])
//                    ->whereHas('deposits', function ($query) use ($date) {
//                        // จะกรองให้เฉพาะ member ที่มีรายการฝาก
//                        $query->where('status', 1)->where('enable', 'Y')->whereDate('date_approve', $date);
//                    })
//                    ->count();
                $result['sum'] = $data;
                break;

            case 'register-deposit':
                $data = $campaign->members()
                    ->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate])
                    ->whereHas('deposits', function ($query) use ($startDate,$endDate) {
                        // จะกรองให้เฉพาะ member ที่มีรายการฝาก
                        $query->where('status', 1)->where('enable', 'Y')->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate]);
                    })
                    ->count();
                $result['sum'] = $data;
                break;

            case 'register-not-deposit':
                $data = $campaign->members()
                    ->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate])
                    ->whereDoesntHave('deposits', function ($query) use ($startDate, $endDate) {
                        // จะกรองให้เฉพาะ member ที่มีรายการฝาก
                        $query->where('status', 1)->where('enable', 'Y')->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate]);
                    })
                    ->count();
                $result['sum'] = $data;
                break;

            case 'bonus-all':
                $data = BankPaymentProxy::where('status', 1)
                    ->where('pro_id', '>', 0)
                    ->where('enable', 'Y')
                    ->whereIn('member_topup', function ($q) use ($campaign) {
                        $q->select('code')
                            ->from('members') // หรือ table ของ MemberProxy
                            ->where('campaign_id', $campaign->id);
                    })
                    ->sum('pro_amount');

                $result['sum'] = $data;
                break;

            case 'bonus-today':
                $data = BankPaymentProxy::where('status', 1)
                    ->where('pro_id', '>', 0)
                    ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate])
//                    ->whereDate('date_approve', $date)
                    ->where('enable', 'Y')
                    ->whereIn('member_topup', function ($q) use ($campaign) {
                        $q->select('code')
                            ->from('members') // หรือ table ของ MemberProxy
                            ->where('campaign_id', $campaign->id);
                    })
                    ->sum('pro_amount');
                $result['sum'] = $data;
                break;

            case 'deposit-all':
                $data = BankPaymentProxy::where('status', 1)
                    ->where('enable', 'Y')
                    ->whereIn('member_topup', function ($q) use ($campaign) {
                        $q->select('code')
                            ->from('members') // หรือ table ของ MemberProxy
                            ->where('campaign_id', $campaign->id);
                    })
                    ->sum('value');

                $result['sum'] = $data;
                break;

            case 'deposit-today':
                $data = BankPaymentProxy::where('status', 1)
                    ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate])
//                    ->whereDate('date_approve', $date)
                    ->where('enable', 'Y')
                    ->whereIn('member_topup', function ($q) use ($campaign) {
                        $q->select('code')
                            ->from('members') // หรือ table ของ MemberProxy
                            ->where('campaign_id', $campaign->id);
                    })
                    ->sum('value');
                $result['sum'] = $data;
                break;

            case 'deposit-register-today':
                $data = BankPaymentProxy::where('status', 1)
                    ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate])
//                    ->whereDate('date_approve', $date)
                    ->where('enable', 'Y')
                    ->whereIn('member_topup', function ($q) use ($campaign,$startDate, $endDate) {
                        $q->select('code')
                            ->from('members') // หรือ table ของ MemberProxy
                            ->where('campaign_id', $campaign->id)->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate]);
                    })
                    ->sum('value');
                $result['sum'] = $data;
                break;

            case 'withdraw-all':
                $data = WithdrawProxy::where('status', 1)
                    ->where('enable', 'Y')
                    ->whereIn('member_code', function ($q) use ($campaign) {
                        $q->select('code')
                            ->from('members') // หรือ table ของ MemberProxy
                            ->where('campaign_id', $campaign->id);
                    })
                    ->sum('amount');

                $result['sum'] = $data;
                break;

            case 'withdraw-today':
                $data = WithdrawProxy::where('status', 1)
                    ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate])
//                    ->whereDate('date_approve', $date)
                    ->where('enable', 'Y')
                    ->whereIn('member_code', function ($q) use ($campaign) {
                        $q->select('code')
                            ->from('members') // หรือ table ของ MemberProxy
                            ->where('campaign_id', $campaign->id);
                    })
                    ->sum('amount');
                $result['sum'] = $data;
                break;

            case 'click-all':
                $data = $campaign->registrationLink->clicks()
                    ->count();
                $result['sum'] = $data;
                break;

            case 'click-today':
                $data = $campaign->registrationLink->clicks()
                    ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
//                    ->whereDate('created_at', $date)
                    ->count();
                $result['sum'] = $data;
                break;

            case 'register':
                $regData = app('Gametech\Marketing\Repositories\MarketingMemberRepository')->active()
                    ->where('campaign_id', $id)
                    ->groupBy('date_regis')
                    ->orderBy('date_regis', 'asc')
                    ->select(DB::raw('COUNT(*) as value'), DB::raw('date_regis as date'))
                    ->get()
                    ->keyBy('date'); // 👉 keyBy เพื่อให้เข้าถึงง่ายตามวันที่

                // 🔵 3. รวมวันที่ทั้งหมดที่เกิดขึ้นจากทั้งสองชุด
                $allDates = collect($regData->keys())
                    ->unique()
                    ->sort();

                // 🟢 4. สร้างผลลัพธ์
                $result = [
                    'label' => [],
                    'register' => [],
                ];

                foreach ($allDates as $date) {
                    $result['label'][] = core()->Date($date, 'd M');
                    $result['register'][] = $regData[$date]->value ?? 0;
                }
                break;
            case 'income':
                $withdrawData = WithdrawSeamlessProxy::where('status', 1)
                    ->where('enable', 'Y')
                    ->whereIn('member_code', function ($q) use ($campaign) {
                        $q->select('code')
                            ->from('members')
                            ->where('campaign_id', $campaign->id);
                    })
                    ->selectRaw('DATE(date_approve) as date, SUM(amount) as total')
                    ->groupByRaw('DATE(date_approve)')
                    ->orderByRaw('DATE(date_approve)')
                    ->get()
                    ->keyBy('date');

                // 🟡 2. โหลดข้อมูล "ฝาก"
                $depositData = BankPaymentProxy::where('status', 1)
                    ->where('enable', 'Y')
                    ->selectRaw('DATE(date_approve) as date, SUM(value) as total')
                    ->whereIn('member_topup', function ($q) use ($campaign) {
                        $q->select('code')->from('members')->where('campaign_id', $campaign->id);
                    })
                    ->groupByRaw('DATE(date_approve)')
                    ->orderByRaw('DATE(date_approve)')
                    ->get()
                    ->keyBy('date');

                // 🔵 3. รวมวันที่ทั้งหมดที่เกิดขึ้นจากทั้งสองชุด
                $allDates = collect($withdrawData->keys())
                    ->merge($depositData->keys())
                    ->unique()
                    ->sort();

                // 🟢 4. สร้างผลลัพธ์
                $result = [
                    'label' => [],
                    'withdraw' => [],
                    'deposit' => [],
                ];

                foreach ($allDates as $date) {
                    $result['label'][] = core()->Date($date, 'd M');

                    $result['withdraw'][] = $withdrawData[$date]->total ?? 0;
                    $result['deposit'][] = $depositData[$date]->total ?? 0;
                }
                break;

            case 'click':
                $data = $campaign->registrationLink->clicks()
                    ->groupByRaw('DATE(created_at)')
                    ->orderByRaw('DATE(created_at)')
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as value')->get();

                foreach ($data as $i => $item) {

                    $result['label'][] = core()->Date($item['date'], 'd M');
                    $result['bar'][] = $item['value'];

                }
                break;
        }

        return $this->sendResponseNew($result, 'ดำเนินการเสร็จสิ้น');
    }
}
