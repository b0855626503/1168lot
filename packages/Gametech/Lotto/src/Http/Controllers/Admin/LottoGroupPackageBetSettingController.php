<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoGroupPackage;
use Gametech\Lotto\Models\LottoGroupPackageBetSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LottoGroupPackageBetSettingController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function list(Request $request): JsonResponse
    {
        $validated = validator($request->all(), [
            'package_id' => ['required', 'integer', 'exists:lotto_group_packages,id'],
        ])->validate();

        $rows = LottoGroupPackageBetSetting::query()
            ->where('package_id', (int) $validated['package_id'])
            ->orderBy('bet_type')
            ->get();

        return $this->sendResponse($rows, 'ดึงรายการ bet settings ของ package สำเร็จ');
    }

    public function create(Request $request): JsonResponse
    {
        $validated = validator((array) $request->input('data', []), [
            'package_id' => ['required', 'integer', 'exists:lotto_group_packages,id'],
            'bet_type' => ['required', Rule::in(BetType::all())],
            'payout' => ['required', 'numeric', 'gt:0'],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_enabled' => ['nullable', 'boolean'],
        ])->validate();

        $exists = LottoGroupPackageBetSetting::query()
            ->where('package_id', (int) $validated['package_id'])
            ->where('bet_type', (string) $validated['bet_type'])
            ->exists();
        if ($exists) {
            return $this->sendError('bet_type นี้ถูกตั้งค่าใน package แล้ว', 422);
        }

        $package = LottoGroupPackage::query()->find((int) $validated['package_id']);
        if (! $package || ! (bool) $package->is_active) {
            return $this->sendError('PACKAGE_INACTIVE', 409);
        }

        $row = LottoGroupPackageBetSetting::query()->create([
            'package_id' => (int) $validated['package_id'],
            'bet_type' => (string) $validated['bet_type'],
            'payout' => $validated['payout'],
            'discount_percent' => $validated['discount_percent'],
            'is_enabled' => (bool) ($validated['is_enabled'] ?? true),
        ]);

        return $this->sendResponse($row, 'สร้าง bet setting ของ package สำเร็จ');
    }

    public function edit(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $row = LottoGroupPackageBetSetting::query()->find($id);
        if (! $row) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        if ($request->has('status') && (string) $request->input('method') === 'is_enabled') {
            $row->update([
                'is_enabled' => (bool) ((int) $request->input('status') === 1),
            ]);

            return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
        }

        return $this->sendResponse($row, 'ดำเนินการเสร็จสิ้น');
    }

    public function update(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $row = LottoGroupPackageBetSetting::query()->find($id);
        if (! $row) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $validated = validator((array) $request->input('data', []), [
            'package_id' => ['required', 'integer', 'exists:lotto_group_packages,id'],
            'bet_type' => ['required', Rule::in(BetType::all())],
            'payout' => ['required', 'numeric', 'gt:0'],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_enabled' => ['nullable', 'boolean'],
        ])->validate();

        $exists = LottoGroupPackageBetSetting::query()
            ->where('package_id', (int) $validated['package_id'])
            ->where('bet_type', (string) $validated['bet_type'])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return $this->sendError('bet_type นี้ถูกตั้งค่าใน package แล้ว', 422);
        }

        $package = LottoGroupPackage::query()->find((int) $validated['package_id']);
        if (! $package || ! (bool) $package->is_active) {
            return $this->sendError('PACKAGE_INACTIVE', 409);
        }

        $row->update([
            'package_id' => (int) $validated['package_id'],
            'bet_type' => (string) $validated['bet_type'],
            'payout' => $validated['payout'],
            'discount_percent' => $validated['discount_percent'],
            'is_enabled' => (bool) ($validated['is_enabled'] ?? $row->is_enabled),
        ]);

        return $this->sendResponse($row->fresh(), 'อัปเดต bet setting ของ package สำเร็จ');
    }

    public function delete(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $row = LottoGroupPackageBetSetting::query()->find($id);
        if (! $row) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $row->delete();

        return $this->sendResponse([], 'ลบ bet setting ของ package สำเร็จ');
    }
}

