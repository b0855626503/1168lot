<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LotteryMarketDataTable;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Support\ToggleFieldGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LotteryMarketController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(LotteryMarketDataTable $dataTable)
    {
        $groups = LotteryGroup::query()->orderBy('sort')->get();

        return $dataTable->render($this->_config['view'], compact('groups'));
    }

    public function loadData(Request $request): JsonResponse
    {
        $id   = $request->input('id');
        $data = LotteryMarket::query()->find((int) $id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');
    }

    public function create(Request $request): JsonResponse
    {
        $data = (array) $request->input('data', []);

        $validated = validator($data, [
            'group_id'   => ['required', 'integer', 'exists:lotto_groups,id'],
            'name'       => ['required', 'string', 'max:191'],
            'code'       => ['required', 'string', 'max:100', 'alpha_dash', 'unique:lotto_markets,code'],
            'is_enabled' => ['nullable'],
        ])->validate();

        LotteryMarket::query()->create([
            'group_id'   => (int) $validated['group_id'],
            'name'       => $validated['name'],
            'code'       => strtolower($validated['code']),
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
        ]);

        return $this->sendSuccess('สร้างรายการหวยเรียบร้อยแล้ว');
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

        $market = LotteryMarket::query()->find($id);

        if (! $market) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $market->update([$method => $status]);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function update(Request $request): JsonResponse
    {
        $id   = (int) $request->input('id');
        $data = (array) $request->input('data', []);

        $market = LotteryMarket::query()->find($id);

        if (! $market) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $validated = validator($data, [
            'group_id'   => ['required', 'integer', 'exists:lotto_groups,id'],
            'name'       => ['required', 'string', 'max:191'],
            'code'       => ['required', 'string', 'max:100', 'alpha_dash', 'unique:lotto_markets,code,' . $market->id],
            'is_enabled' => ['nullable'],
        ])->validate();

        $market->update([
            'group_id'   => (int) $validated['group_id'],
            'name'       => $validated['name'],
            'code'       => strtolower($validated['code']),
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
        ]);

        return $this->sendSuccess('อัปเดตรายการหวยเรียบร้อยแล้ว');
    }
}
