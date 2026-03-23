<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoRatePlanDataTable;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LottoRatePlan;
use Gametech\Lotto\Models\LottoRatePlanItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LottoRatePlanController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(LottoRatePlanDataTable $dataTable)
    {
        $groups = LotteryGroup::query()->orderBy('sort')->orderByDesc('id')->get();
        $betTypes = collect(BetType::all())->map(function (string $type) {
            return [
                'key' => $type,
                'label' => BetType::label($type),
            ];
        })->values();

        return $dataTable->render($this->_config['view'], [
            'groups' => $groups,
            'betTypes' => $betTypes,
        ]);
    }

    public function loadData(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');

        $data = LottoRatePlan::query()
            ->with('items')
            ->find($id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');
    }

    public function create(Request $request): JsonResponse
    {
        $payload = $this->validatePayload((array) $request->input('data', []));

        DB::transaction(function () use ($payload) {
            $plan = LottoRatePlan::query()->create([
                'group_id' => (int) $payload['group_id'],
                'name' => $payload['name'],
                'description' => $payload['description'] ?? null,
                'is_enabled' => (bool) ($payload['is_enabled'] ?? false),
            ]);

            $this->syncItems($plan->id, (array) $payload['items']);
        });

        return $this->sendSuccess('สร้างอัตราจ่ายเรียบร้อยแล้ว');
    }

    public function update(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $payload = $this->validatePayload((array) $request->input('data', []), $id);

        $plan = LottoRatePlan::query()->find($id);

        if (! $plan) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        DB::transaction(function () use ($plan, $payload) {
            $plan->update([
                'group_id' => (int) $payload['group_id'],
                'name' => $payload['name'],
                'description' => $payload['description'] ?? null,
                'is_enabled' => (bool) ($payload['is_enabled'] ?? false),
            ]);

            $this->syncItems($plan->id, (array) $payload['items']);
        });

        return $this->sendSuccess('อัปเดตอัตราจ่ายเรียบร้อยแล้ว');
    }

    public function edit(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $status = (bool) $request->input('status');
        $method = (string) $request->input('method', '');

        if ($method !== 'is_enabled') {
            return $this->sendError('method ไม่ถูกต้อง', 422);
        }

        $plan = LottoRatePlan::query()->find($id);

        if (! $plan) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $plan->update(['is_enabled' => $status]);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    protected function validatePayload(array $data): array
    {
        $rules = [
            'group_id' => ['required', 'integer', 'exists:lotto_groups,id'],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_enabled' => ['nullable'],
            'items' => ['required', 'array'],
        ];

        foreach (BetType::all() as $type) {
            $rules['items.' . $type . '.payout'] = ['nullable', 'numeric', 'min:0'];
            $rules['items.' . $type . '.discount_percent'] = ['nullable', 'numeric', 'min:0', 'max:100'];
        }

        return validator($data, $rules)->validate();
    }

    protected function syncItems(int $ratePlanId, array $items): void
    {
        foreach (BetType::all() as $type) {
            $item = (array) ($items[$type] ?? []);

            LottoRatePlanItem::query()->updateOrCreate(
                [
                    'rate_plan_id' => $ratePlanId,
                    'bet_type' => $type,
                ],
                [
                    'payout' => (float) ($item['payout'] ?? 0),
                    'discount_percent' => (float) ($item['discount_percent'] ?? 0),
                ]
            );
        }
    }
}

