<?php

namespace Gametech\Admin\Http\Controllers;

use Gametech\Admin\DataTables\GameUserDataTable;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Payment\Models\BankPayment;
use Gametech\Promotion\Repositories\PromotionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameUserController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $promotionRepository;

    public function __construct(
        GameUserRepository $repository,
        PromotionRepository $promotionRepository
    ) {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->promotionRepository = $promotionRepository;

    }

    public function index(GameUserDataTable $gameUserDataTable)
    {
        //        $games = $this->gameTypeRepository->findWhere(['enable' => 'Y'] ,['id as name','id'])->pluck('name', 'id');

        return $gameUserDataTable->render($this->_config['view']);
    }

    public function edit(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $data['bonus'] = 0;
        $data['amount'] = 0;
        $data['turnpro'] = 0;
        $data['pro_code'] = 0;
        $data['amount_balance'] = 0;
        $data['withdraw_limit_rate'] = 0;
        $data['withdraw_limit_amount'] = 0;
        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
            'ip' => $request->ip(),
            'credit_type' => 'D',
            'balance_before' => 0,
            'balance_after' => 0,
            'credit' => 0,
            'total' => 0,
            'credit_bonus' => 0,
            'credit_total' => 0,
            'credit_before' => 0,
            'credit_after' => 0,
            'pro_code' => 0,
            'bank_code' => 0,
            'auto' => 'N',
            'enable' => 'Y',
            'user_create' => 'System Auto',
            'user_update' => 'System Auto',
            'refer_code' => $chk->code,
            'refer_table' => 'games_user',
            'remark' => 'ล้างโปรออกจากไอดี โดยทีมงาน',
            'kind' => 'OTHER',
            'amount' => 0,
            'amount_balance' => 0,
            'withdraw_limit' => 0,
            'withdraw_limit_amount' => 0,
            'method' => 'D',
            'member_code' => $chk->member_code,
            'emp_code' => $this->id(),
            'emp_name' => $this->user()->name.' '.$this->user()->surname,
        ]);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

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

    public function loadPromotion(Request $request)
    {

        $data = $this->promotionRepository->where('active', 'Y')->where('enable', 'Y')->get();
        //        dd($data);

        //        dd($cacheP, $cached_provider);

        //        $data = collect($provider)->pluck('lobbyName', 'lobbyId');
        $dropdown = collect($data)
            ->pluck('name_th', 'code')
            ->map(fn ($name, $id) => ['value' => $id, 'text' => $name])
            ->sortBy('text', SORT_NATURAL | SORT_FLAG_CASE)
            ->prepend(['value' => '0', 'text' => '== เลือกโปร =='])
            ->values()
            ->toArray();
        //        if (!$data) {
        //            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        //        }

        return $this->sendResponse($dropdown, 'ดำเนินการเสร็จสิ้น');

    }

    public function update($id, Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;

        $data = json_decode($request['data'], true);

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
            'ip' => $request->ip(),
            'credit_type' => 'D',
            'balance_before' => 0,
            'balance_after' => 0,
            'credit' => 0,
            'total' => 0,
            'credit_bonus' => 0,
            'credit_total' => 0,
            'credit_before' => 0,
            'credit_after' => 0,
            'pro_code' => 0,
            'bank_code' => 0,
            'auto' => 'N',
            'enable' => 'Y',
            'user_create' => 'System Auto',
            'user_update' => 'System Auto',
            'refer_code' => 0,
            'refer_table' => 'games_user',
            'remark' => 'แก้ไขยอดเทริน หรือ ยอดอั้น โดยทีมงาน เทรินโปร : '.$data['turnpro'].' / อัตราอั้นถอน : '.$data['withdraw_limit_rate'].' (เท่า)',
            'kind' => 'OTHER',
            'amount' => 0,
            'amount_balance' => $data['amount_balance'],
            'withdraw_limit' => 0,
            'withdraw_limit_amount' => $data['withdraw_limit_amount'],
            'method' => 'D',
            'member_code' => $chk->member_code,
            'emp_code' => $this->id(),
            'emp_name' => $this->user()->name.' '.$this->user()->surname,
        ]);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');

        $chk = $this->repository->find($id);

        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $data['turnpro'] = 0;
        $data['amount_balance'] = 0;
        $data['withdraw_limit_rate'] = 0;
        $data['withdraw_limit_amount'] = 0;
        $this->repository->update($data, $id);

        app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
            'ip' => $request->ip(),
            'credit_type' => 'D',
            'balance_before' => 0,
            'balance_after' => 0,
            'credit' => 0,
            'total' => 0,
            'credit_bonus' => 0,
            'credit_total' => 0,
            'credit_before' => 0,
            'credit_after' => 0,
            'pro_code' => 0,
            'bank_code' => 0,
            'auto' => 'N',
            'enable' => 'Y',
            'user_create' => 'System Auto',
            'user_update' => 'System Auto',
            'refer_code' => 0,
            'refer_table' => 'games_user',
            'remark' => 'รีเซตยอดเทรินออกทั้งหมด โดยทีมงาน',
            'kind' => 'OTHER',
            'amount' => 0,
            'amount_balance' => 0,
            'withdraw_limit' => 0,
            'withdraw_limit_amount' => 0,
            'method' => 'D',
            'member_code' => $chk->member_code,
            'emp_code' => $this->id(),
            'emp_name' => $this->user()->name.' '.$this->user()->surname,
        ]);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function lastPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'member_code' => ['required', 'integer'],
        ]);

        $memberCode = (int) $validated['member_code'];

        $p = BankPayment::query()
            ->with(['member', 'promotion'])
            ->where('member_topup', $memberCode)
            ->where('enable', 'Y')
            ->where('status', 1)
            ->orderByDesc('code')
            ->first();

        if (! $p) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบรายการฝากล่าสุดของสมาชิกนี้',
                'data' => null,
            ], 200);
        }

        $amount = $p->value ?? $p->amount ?? 0;

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => [
                'bank_payment_code' => (int) ($p->code ?? 0),
                'member_code' => (int) ($p->member_topup ?? $memberCode),
                'member_name' => (string) (
                    optional($p->member)->name
                    ?? $p->name
                    ?? ''
                ),
                'amount' => (float) $amount,
                'bank_time' => (string) ($p->bank_time ?? optional($p->created_at)->toDateTimeString() ?? ''),
                'bank' => (string) ($p->bank ?? ''),

                'pro_id' => (int) ($p->pro_id ?? 0),
                'pro_name' => (string) (optional($p->promotion)->name_th ?? ''),
                'pro_amount' => (float) ($p->pro_amount ?? 0),
            ],
        ], 200);
    }
}
