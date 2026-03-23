<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\MemberLottoSettingDataTable;
use Gametech\Lotto\Models\LottoRatePlan;
use Gametech\Lotto\Models\MemberLottoSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberLottoSettingController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(MemberLottoSettingDataTable $dataTable)
    {
        $ratePlanOptions = LottoRatePlan::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($plan) => ['value' => (int) $plan->id, 'text' => $plan->name])
            ->values()
            ->toArray();

        return $dataTable->render($this->_config['view'], [
            'ratePlanOptions' => $ratePlanOptions,
        ]);
    }

    public function loadData(Request $request): JsonResponse
    {
        $id   = $request->input('id');
        $data = MemberLottoSetting::query()->with('member', 'ratePlan')->find((int) $id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');
    }

    public function create(Request $request): JsonResponse
    {
        $data = (array) $request->input('data', []);

        $validated = validator($data, [
            'member_id'   => ['required', 'integer', 'exists:members,code', Rule::unique('member_lotto_settings', 'member_id')],
            'rate_plan_id' => ['required', 'integer', 'exists:lotto_rate_plans,id'],
        ], [
            'member_id.unique' => 'สมาชิกนี้มีการตั้งค่าอัตราจ่ายแล้ว',
        ])->validate();

        try {
            MemberLottoSetting::query()->create([
                'member_id'    => $validated['member_id'],
                'rate_plan_id' => $validated['rate_plan_id'],
            ]);

            return $this->sendResponse([], 'เพิ่มการตั้งค่าสมาชิกสำเร็จ');
        } catch (\Exception $e) {
            return $this->sendError('เพิ่มการตั้งค่าสมาชิกไม่สำเร็จ: ' . $e->getMessage());
        }
    }

    public function edit(Request $request): JsonResponse
    {
        $id   = $request->input('id');
        $data = MemberLottoSetting::query()->find((int) $id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');
    }

    public function update(Request $request): JsonResponse
    {
        $id   = $request->input('id');
        $data = (array) $request->input('data', []);

        $setting = MemberLottoSetting::query()->find((int) $id);
        if (! $setting) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $validated = validator($data, [
            'member_id'    => [
                'required',
                'integer',
                'exists:members,code',
                Rule::unique('member_lotto_settings', 'member_id')->ignore((int) $id),
            ],
            'rate_plan_id' => ['required', 'integer', 'exists:lotto_rate_plans,id'],
        ], [
            'member_id.unique' => 'สมาชิกนี้มีการตั้งค่าอัตราจ่ายแล้ว',
        ])->validate();

        try {
            $setting->update([
                'member_id'    => $validated['member_id'],
                'rate_plan_id' => $validated['rate_plan_id'],
            ]);

            return $this->sendResponse([], 'อัปเดตการตั้งค่าสมาชิกสำเร็จ');
        } catch (\Exception $e) {
            return $this->sendError('อัปเดตการตั้งค่าสมาชิกไม่สำเร็จ: ' . $e->getMessage());
        }
    }
}

