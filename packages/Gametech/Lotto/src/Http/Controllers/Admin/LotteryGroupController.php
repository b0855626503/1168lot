<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LotteryGroupDataTable;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Services\MemberMarketPolicyService;
use Gametech\Lotto\Support\ToggleFieldGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LotteryGroupController extends AppBaseController
{
    private const ROLLOUT_MODES = [
        MemberMarketPolicyService::ROLLOUT_NEW_ONLY,
        MemberMarketPolicyService::ROLLOUT_ALL,
        MemberMarketPolicyService::ROLLOUT_SELECTED,
    ];

    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(LotteryGroupDataTable $dataTable)
    {
        return $dataTable->render($this->_config['view']);
    }

    public function loadData(Request $request): JsonResponse
    {
        $id   = $request->input('id');
        $data = LotteryGroup::query()->find((int) $id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');
    }

    public function create(Request $request): JsonResponse
    {
        $data = (array) $request->input('data', []);

        $validated = validator($data, [
            'name'       => ['required', 'string', 'max:191'],
            'code'       => ['required', 'string', 'max:100', 'alpha_dash', 'unique:lotto_groups,code'],
            'sort'       => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable'],
            'rollout_mode' => ['nullable', 'string', 'in:' . implode(',', self::ROLLOUT_MODES)],
        ])->validate();

        LotteryGroup::query()->create([
            'name'       => $validated['name'],
            'code'       => strtolower($validated['code']),
            'sort'       => (int) ($validated['sort'] ?? 0),
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
            'rollout_mode' => (string) ($validated['rollout_mode'] ?? MemberMarketPolicyService::ROLLOUT_NEW_ONLY),
            'affect_existing_members' => false,
        ]);

        return $this->sendSuccess('สร้างกลุ่มหวยเรียบร้อยแล้ว');
    }

    public function edit(Request $request): JsonResponse
    {
        $id     = (int) $request->input('id');

        try {
            $status = ToggleFieldGuard::resolveBoolean($request->input('status'));
            $method = ToggleFieldGuard::resolveField((string) $request->input('method'), ['is_enabled']);
        } catch (InvalidArgumentException $exception) {
            return $this->sendError($exception->getMessage(), 422);
        }

        $group = LotteryGroup::query()->find($id);

        if (! $group) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $group->update([$method => $status]);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function update(Request $request): JsonResponse
    {
        $id   = (int) $request->input('id');
        $data = (array) $request->input('data', []);

        $group = LotteryGroup::query()->find($id);

        if (! $group) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $validated = validator($data, [
            'name'       => ['required', 'string', 'max:191'],
            'code'       => ['required', 'string', 'max:100', 'alpha_dash', 'unique:lotto_groups,code,' . $group->id],
            'sort'       => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable'],
            'rollout_mode' => ['nullable', 'string', 'in:' . implode(',', self::ROLLOUT_MODES)],
        ])->validate();

        $incomingMode = (string) ($validated['rollout_mode'] ?? MemberMarketPolicyService::ROLLOUT_NEW_ONLY);
        $isModeChanged = $group->rollout_mode !== $incomingMode;

        $group->update([
            'name'       => $validated['name'],
            'code'       => strtolower($validated['code']),
            'sort'       => (int) ($validated['sort'] ?? 0),
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
            'rollout_mode' => $incomingMode,
            'policy_version' => $isModeChanged
                ? ((int) $group->policy_version + 1)
                : (int) $group->policy_version,
        ]);

        return $this->sendSuccess('อัปเดตกลุ่มหวยเรียบร้อยแล้ว');
    }

    public function applyRollout(Request $request, MemberMarketPolicyService $policyService): JsonResponse
    {
        $id = (int) $request->input('id');
        $payload = validator($request->all(), [
            'scope' => ['required', 'string', 'in:all,selected'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'min:1'],
        ])->validate();

        $group = LotteryGroup::query()->find($id);

        if (! $group) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        if ($payload['scope'] === 'selected' && empty($payload['member_ids'])) {
            return $this->sendError('กรุณาระบุสมาชิกอย่างน้อย 1 รายการ', 422);
        }

        $affectedMembers = $policyService->applyGroupRollout(
            $group->id,
            (string) $payload['scope'],
            (array) ($payload['member_ids'] ?? [])
        );

        $group->update([
            'affect_existing_members' => true,
            'policy_version' => (int) $group->policy_version + 1,
        ]);

        return $this->sendResponse([
            'affected_members' => $affectedMembers,
        ], 'อัปเดตสิทธิ์สมาชิกเดิมเรียบร้อยแล้ว');
    }

    public function searchMembers(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->input('keyword', ''));
        $limit = max(1, min((int) $request->input('limit', 20), 50));

        $query = DB::table('members')
            ->select(['code', 'user_name', 'firstname', 'lastname'])
            ->where('code', '>', 0)
            ->orderByDesc('code');

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword): void {
                $builder->where('user_name', 'like', '%' . $keyword . '%')
                    ->orWhere('firstname', 'like', '%' . $keyword . '%')
                    ->orWhere('lastname', 'like', '%' . $keyword . '%');

                if (ctype_digit($keyword)) {
                    $builder->orWhere('code', (int) $keyword);
                }
            });
        }

        $items = $query->limit($limit)->get()->map(static function ($row): array {
            $name = trim((string) (($row->firstname ?? '') . ' ' . ($row->lastname ?? '')));
            $label = trim((string) ($row->user_name ?? ''));

            return [
                'value' => (int) $row->code,
                'text' => '#' . (int) $row->code . ' - ' . ($label !== '' ? $label : '-') . ($name !== '' ? ' (' . $name . ')' : ''),
            ];
        })->values();

        return $this->sendResponse($items, 'ดำเนินการเสร็จสิ้น');
    }
}
