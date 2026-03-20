<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LotteryGroupDataTable;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Support\ToggleFieldGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LotteryGroupController extends AppBaseController
{
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
        ])->validate();

        LotteryGroup::query()->create([
            'name'       => $validated['name'],
            'code'       => strtolower($validated['code']),
            'sort'       => (int) ($validated['sort'] ?? 0),
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
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
        ])->validate();

        $group->update([
            'name'       => $validated['name'],
            'code'       => strtolower($validated['code']),
            'sort'       => (int) ($validated['sort'] ?? 0),
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
        ]);

        return $this->sendSuccess('อัปเดตกลุ่มหวยเรียบร้อยแล้ว');
    }
}
