<?php

namespace Gametech\Marketing\Http\Controllers\Admin;

use App\Http\Controllers\AppBaseController;
use Carbon\Carbon;
use Gametech\Marketing\DataTables\MarketingCampaignDataTable;
use Gametech\Marketing\DataTables\MarketingMemberDataTable;
use Gametech\Marketing\Repositories\MarketingCampaignRepository;
use Gametech\Marketing\Repositories\MarketingTeamRepository;
use Gametech\Marketing\Repositories\RegistrationLinkRepository;
use Gametech\Marketing\Services\CampaignDashboardService;
use Gametech\Payment\Models\BankPaymentProxy;
use Gametech\Payment\Models\WithdrawProxy;
use Gametech\Payment\Models\WithdrawSeamlessProxy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketingCampaignController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $marketingTeamRepository;

    protected $registrationLinkRepository;

    protected CampaignDashboardService $campaignDashboardService;

    public function __construct(
        MarketingCampaignRepository $repository,
        MarketingTeamRepository $marketingTeamRepository,
        RegistrationLinkRepository $registrationLinkRepository,
        CampaignDashboardService $campaignDashboardService
    ) {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->marketingTeamRepository = $marketingTeamRepository;

        $this->registrationLinkRepository = $registrationLinkRepository;

        $this->campaignDashboardService = $campaignDashboardService;
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

        [$startDate, $endDate] = $this->normalizeDateRange($startDate, $endDate);
        [$reportStartAt, $reportEndAt] = $this->dateTimeRange($startDate, $endDate);
        [$singleDateStartAt, $singleDateEndAt] = $this->dateTimeRange($date, $date);

        switch ($method) {
            case 'register-all':
                $data = $campaign->members()->count();
                $result['sum'] = $data;
                break;

            case 'register-today':
                $data = $campaign->members()
                    ->whereBetween('date_regis', [$startDate, $endDate])
                    ->count();
                $result['sum'] = $data;
                break;

            case 'register-all-deposit':
                $data = $campaign->members()
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->where('date_regis', '<', $startDate)
                            ->orWhere('date_regis', '>', $endDate);
                    })
                    ->whereHas('deposits', function ($query) use ($singleDateStartAt, $singleDateEndAt) {
                        $query->where('status', 1)
                            ->where('enable', 'Y')
                            ->where('date_approve', '>=', $singleDateStartAt)
                            ->where('date_approve', '<', $singleDateEndAt);
                    })
                    ->count();
                $result['sum'] = $data;
                break;

            case 'member-all-first-deposit':
                $firstDepositDates = DB::table('bank_payment')
                    ->select('member_topup', DB::raw('MIN(date_approve) as first_date_approve'))
                    ->where('status', 1)
                    ->where('enable', 'Y')
                    ->whereNotNull('member_topup')
                    ->groupBy('member_topup');

                $data = (float) DB::table('members')
                    ->joinSub($firstDepositDates, 'first_deposits', function ($join) {
                        $join->on('first_deposits.member_topup', '=', 'members.code');
                    })
                    ->join('bank_payment as first_payment', function ($join) {
                        $join->on('first_payment.member_topup', '=', 'first_deposits.member_topup')
                            ->on('first_payment.date_approve', '=', 'first_deposits.first_date_approve');
                    })
                    ->where('members.campaign_id', $campaign->id)
                    ->where('first_payment.status', 1)
                    ->where('first_payment.enable', 'Y')
                    ->where('first_deposits.first_date_approve', '>=', $reportStartAt)
                    ->where('first_deposits.first_date_approve', '<', $reportEndAt)
                    ->sum('first_payment.value');
                $result['sum'] = $data;
                break;

            case 'register-deposit':
                $data = $campaign->members()
                    ->whereBetween('date_regis', [$startDate, $endDate])
                    ->whereHas('deposits', function ($query) use ($reportStartAt, $reportEndAt) {
                        $query->where('status', 1)
                            ->where('enable', 'Y')
                            ->where('date_approve', '>=', $reportStartAt)
                            ->where('date_approve', '<', $reportEndAt);
                    })
                    ->count();
                $result['sum'] = $data;
                break;

            case 'register-not-deposit':
                $data = $campaign->members()
                    ->whereBetween('date_regis', [$startDate, $endDate])
                    ->whereDoesntHave('deposits', function ($query) use ($reportStartAt, $reportEndAt) {
                        $query->where('status', 1)
                            ->where('enable', 'Y')
                            ->where('date_approve', '>=', $reportStartAt)
                            ->where('date_approve', '<', $reportEndAt);
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
                    ->where('date_approve', '>=', $reportStartAt)
                    ->where('date_approve', '<', $reportEndAt)
                    ->where('enable', 'Y')
                    ->whereIn('member_topup', function ($q) use ($campaign) {
                        $q->select('code')
                            ->from('members')
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
                    ->where('date_approve', '>=', $reportStartAt)
                    ->where('date_approve', '<', $reportEndAt)
                    ->where('enable', 'Y')
                    ->whereIn('member_topup', function ($q) use ($campaign) {
                        $q->select('code')
                            ->from('members')
                            ->where('campaign_id', $campaign->id);
                    })
                    ->sum('value');
                $result['sum'] = $data;
                break;

            case 'deposit-register-today':
                $data = BankPaymentProxy::where('status', 1)
                    ->where('date_approve', '>=', $reportStartAt)
                    ->where('date_approve', '<', $reportEndAt)
                    ->where('enable', 'Y')
                    ->whereIn('member_topup', function ($q) use ($campaign, $startDate, $endDate) {
                        $q->select('code')
                            ->from('members')
                            ->where('campaign_id', $campaign->id)
                            ->whereBetween('date_regis', [$startDate, $endDate]);
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
                    ->where('date_approve', '>=', $reportStartAt)
                    ->where('date_approve', '<', $reportEndAt)
                    ->where('enable', 'Y')
                    ->whereIn('member_code', function ($q) use ($campaign) {
                        $q->select('code')
                            ->from('members')
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
                    ->where('created_at', '>=', $reportStartAt)
                    ->where('created_at', '<', $reportEndAt)
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

    public function dashboardSummary(Request $request, int $campaign): JsonResponse
    {
        $campaignModel = $this->repository->find($campaign);
        if (! $campaignModel) {
            return $this->sendError('ไม่พบข้อมูล campaign ดังกล่าว', 200);
        }

        $today = now()->toDateString();
        $dateStart = (string) ($request->input('date_start') ?? $today);
        $dateEnd = (string) ($request->input('date_end') ?? $today);

        [$dateStart, $dateEnd] = $this->normalizeDateRange($dateStart, $dateEnd);

        $data = $this->campaignDashboardService->getDashboard($campaign, $dateStart, $dateEnd);

        return $this->sendResponseNew($data, 'ดำเนินการเสร็จสิ้น');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function normalizeDateRange(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        return [$start->toDateString(), $end->toDateString()];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function dateTimeRange(string $startDate, string $endDate): array
    {
        [$normalizedStart, $normalizedEnd] = $this->normalizeDateRange($startDate, $endDate);
        $startAt = Carbon::parse($normalizedStart)->startOfDay();
        $endAt = Carbon::parse($normalizedEnd)->addDay()->startOfDay();

        return [$startAt->toDateTimeString(), $endAt->toDateTimeString()];
    }
}
