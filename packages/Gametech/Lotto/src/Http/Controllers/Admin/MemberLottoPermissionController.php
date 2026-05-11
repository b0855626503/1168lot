<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\MemberLottoPermissionDataTable;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\MemberLottoMarketPolicy;
use Gametech\Lotto\Support\ToggleFieldGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class MemberLottoPermissionController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(MemberLottoPermissionDataTable $dataTable)
    {
        $groupOptions = LotteryGroup::query()
            ->where('is_enabled', true)
            ->orderBy('sort')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($group) => ['value' => (int) $group->id, 'text' => $group->name])
            ->values()
            ->toArray();

        $marketOptions = LotteryMarket::query()
            ->where('is_enabled', true)
            ->orderBy('name')
            ->get(['id', 'group_id', 'name'])
            ->map(fn ($market) => [
                'value' => (int) $market->id,
                'group_id' => (int) $market->group_id,
                'text' => $market->name,
            ])
            ->values()
            ->toArray();

        return $dataTable->render($this->_config['view'], [
            'groupOptions' => $groupOptions,
            'marketOptions' => $marketOptions,
        ]);
    }

    public function loadData(Request $request): JsonResponse
    {
        $data = MemberLottoMarketPolicy::query()->find((int) $request->input('id'));

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');
    }

    public function create(Request $request): JsonResponse
    {
        $data = (array) $request->input('data', []);

        $validated = validator($data, [
            'member_id' => ['required', 'integer', 'exists:members,code'],
            'group_id' => ['required', 'integer', 'exists:lotto_groups,id'],
            'market_id' => [
                'required',
                'integer',
                'exists:lotto_markets,id',
                Rule::unique('member_lotto_market_policies', 'market_id')
                    ->where('member_id', (int) ($data['member_id'] ?? 0)),
            ],
            'is_allowed' => ['nullable'],
        ], [
            'market_id.unique' => 'สมาชิกนี้มีรายการในหวยนี้อยู่แล้ว กรุณาตรวจสอบหรือลบรายการเดิมก่อน',
        ])->validate();

        $market = LotteryMarket::query()->find((int) $validated['market_id']);
        if (! $market || (int) $market->group_id !== (int) $validated['group_id']) {
            return $this->sendError('รายการหวยไม่อยู่ในกลุ่มที่เลือก', 422);
        }

        MemberLottoMarketPolicy::query()->create([
            'member_id' => (int) $validated['member_id'],
            'group_id' => (int) $validated['group_id'],
            'market_id' => (int) $validated['market_id'],
            'is_allowed' => false,
            'source' => 'admin',
            'policy_version' => 1,
        ]);

        return $this->sendSuccess('เพิ่มรายการบล็อกสมาชิกเรียบร้อยแล้ว');
    }

    public function edit(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');

        try {
            $status = ToggleFieldGuard::resolveBoolean($request->input('status'));
            $method = ToggleFieldGuard::resolveField((string) $request->input('method'), ['is_allowed']);
        } catch (InvalidArgumentException $exception) {
            return $this->sendError($exception->getMessage(), 422);
        }

        $item = MemberLottoMarketPolicy::query()->find($id);

        if (! $item) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $item->update([
            $method => $status,
            'source' => 'admin',
            'policy_version' => (int) $item->policy_version + 1,
        ]);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function update(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $data = (array) $request->input('data', []);

        $item = MemberLottoMarketPolicy::query()->find($id);
        if (! $item) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $validated = validator($data, [
            'member_id' => ['required', 'integer', 'exists:members,code'],
            'group_id' => ['required', 'integer', 'exists:lotto_groups,id'],
            'market_id' => [
                'required',
                'integer',
                'exists:lotto_markets,id',
                Rule::unique('member_lotto_market_policies', 'market_id')
                    ->where('member_id', (int) ($data['member_id'] ?? 0))
                    ->ignore($id),
            ],
            'is_allowed' => ['nullable'],
        ], [
            'market_id.unique' => 'สมาชิกนี้มีรายการในหวยนี้อยู่แล้ว กรุณาตรวจสอบหรือลบรายการเดิมก่อน',
        ])->validate();

        $market = LotteryMarket::query()->find((int) $validated['market_id']);
        if (! $market || (int) $market->group_id !== (int) $validated['group_id']) {
            return $this->sendError('รายการหวยไม่อยู่ในกลุ่มที่เลือก', 422);
        }

        $item->update([
            'member_id' => (int) $validated['member_id'],
            'group_id' => (int) $validated['group_id'],
            'market_id' => (int) $validated['market_id'],
            'is_allowed' => false,
            'source' => 'admin',
            'policy_version' => (int) $item->policy_version + 1,
        ]);

        return $this->sendSuccess('อัปเดตรายการบล็อกสมาชิกเรียบร้อยแล้ว');
    }

    public function delete(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');

        $item = MemberLottoMarketPolicy::query()->find($id);
        if (! $item) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $item->delete();

        return $this->sendSuccess('ปลดบล็อกสมาชิกเรียบร้อยแล้ว');
    }
}
