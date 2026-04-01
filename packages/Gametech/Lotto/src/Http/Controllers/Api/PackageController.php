<?php

namespace Gametech\Lotto\Http\Controllers\Api;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\Models\LottoGroupPackage;
use Gametech\Lotto\Services\LottoPackageSelectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends AppBaseController
{
    public function available(int $groupId): JsonResponse
    {
        $packages = LottoGroupPackage::query()
            ->with(['betSettings' => static function ($query) {
                $query->where('is_enabled', true)->orderBy('bet_type');
            }])
            ->where('group_id', $groupId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $payload = $packages->map(static function (LottoGroupPackage $package): array {
            return [
                'id' => (int) $package->id,
                'group_id' => (int) $package->group_id,
                'name' => (string) $package->name,
                'is_active' => (bool) $package->is_active,
                'bet_settings' => $package->betSettings->map(static function ($setting): array {
                    return [
                        'bet_type' => (string) $setting->bet_type,
                        'payout' => (float) $setting->payout,
                        'discount_percent' => (float) $setting->discount_percent,
                    ];
                })->values(),
            ];
        })->values();

        return $this->sendResponse($payload, 'ดึง package สำเร็จ');
    }

    public function select(int $groupId, Request $request, LottoPackageSelectionService $selectionService): JsonResponse
    {
        $member = $request->user('customer');
        if (! $member || ! isset($member->code)) {
            return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
        }

        $validated = validator($request->all(), [
            'package_id' => ['required', 'integer', 'exists:lotto_group_packages,id'],
        ])->validate();

        $package = LottoGroupPackage::query()->find((int) $validated['package_id']);
        if (! $package || (int) $package->group_id !== $groupId) {
            return $this->sendResponseFail(['error_code' => 'PACKAGE_NOT_IN_GROUP'], 'package ไม่อยู่ใน group เดียวกัน', 400);
        }

        if (! (bool) $package->is_active) {
            return $this->sendResponseFail(['error_code' => 'PACKAGE_INACTIVE'], 'package ถูกปิดใช้งาน', 409);
        }

        // Helper-only selection for UI flow assist. Betting still validates package_id from request.
        $selectionService->select((int) $member->code, $groupId, (int) $package->id);

        return $this->sendResponse([
            'group_id' => $groupId,
            'package_id' => (int) $package->id,
            'selected' => true,
        ], 'เลือก package สำเร็จ');
    }

    public function selected(int $groupId, Request $request, LottoPackageSelectionService $selectionService): JsonResponse
    {
        $member = $request->user('customer');
        if (! $member || ! isset($member->code)) {
            return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
        }

        $selectedPackageId = $selectionService->getSelectedPackageId((int) $member->code, $groupId);
        if ($selectedPackageId === null) {
            return $this->sendResponseNew([
                'data' => null,
                'selected' => false,
            ], 'ยังไม่ได้เลือก package');
        }

        $package = LottoGroupPackage::query()->find($selectedPackageId);
        if (! $package || (int) $package->group_id !== $groupId || ! (bool) $package->is_active) {
            return $this->sendResponseNew([
                'data' => null,
                'selected' => false,
            ], 'ยังไม่ได้เลือก package');
        }

        return $this->sendResponseNew([
            'data' => [
                'group_id' => $groupId,
                'package_id' => (int) $package->id,
            ],
            'selected' => true,
        ], 'ดึงสถานะ package ที่เลือกสำเร็จ');
    }
}
