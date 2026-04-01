<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoGroupPackage;
use Gametech\Lotto\Models\LottoGroupPackageBetSetting;
use Gametech\Lotto\Models\LottoTicketItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LottoGroupPackageController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function list(Request $request): JsonResponse
    {
        $validated = validator($request->all(), [
            'group_id' => ['required', 'integer', 'exists:lotto_groups,id'],
            'include_inactive' => ['nullable', 'boolean'],
        ])->validate();

        $query = LottoGroupPackage::query()
            ->with(['betSettings' => static function ($betSettingsQuery) {
                $betSettingsQuery->orderBy('bet_type');
            }])
            ->where('group_id', (int) $validated['group_id'])
            ->orderBy('id');

        if (! ((bool) ($validated['include_inactive'] ?? false))) {
            $query->where('is_active', true);
        }

        $items = $query->get();

        return $this->sendResponse($items, 'ดึงรายการ package สำเร็จ');
    }

    public function create(Request $request): JsonResponse
    {
        $validated = validator((array) $request->input('data', []), [
            'group_id' => ['required', 'integer', 'exists:lotto_groups,id'],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
            'bet_settings' => ['nullable', 'array'],
            'bet_settings.*.bet_type' => ['required_with:bet_settings', Rule::in(BetType::all())],
            'bet_settings.*.payout' => ['required_with:bet_settings', 'numeric', 'gt:0'],
            'bet_settings.*.discount_percent' => ['required_with:bet_settings', 'numeric', 'min:0', 'max:100'],
            'bet_settings.*.is_enabled' => ['nullable', 'boolean'],
        ])->validate();

        $exists = LottoGroupPackage::query()
            ->where('group_id', (int) $validated['group_id'])
            ->where('name', (string) $validated['name'])
            ->exists();
        if ($exists) {
            return $this->sendError('ชื่อ package นี้มีอยู่แล้วในกลุ่มหวยดังกล่าว', 422);
        }

        $package = DB::transaction(function () use ($validated) {
            $created = LottoGroupPackage::query()->create([
                'group_id' => (int) $validated['group_id'],
                'name' => (string) $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            $betSettings = collect((array) ($validated['bet_settings'] ?? []))
                ->map(static function (array $row): array {
                    return [
                        'bet_type' => (string) $row['bet_type'],
                        'payout' => (float) $row['payout'],
                        'discount_percent' => (float) ($row['discount_percent'] ?? 0),
                        'is_enabled' => (bool) ($row['is_enabled'] ?? true),
                    ];
                })
                ->unique('bet_type')
                ->values()
                ->all();

            foreach ($betSettings as $row) {
                LottoGroupPackageBetSetting::query()->create([
                    'package_id' => (int) $created->id,
                    'bet_type' => $row['bet_type'],
                    'payout' => $row['payout'],
                    'discount_percent' => $row['discount_percent'],
                    'is_enabled' => $row['is_enabled'],
                ]);
            }

            return $created;
        });

        return $this->sendResponse($package, 'สร้าง package สำเร็จ');
    }

    public function edit(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $package = LottoGroupPackage::query()->find($id);
        if (! $package) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        if ($request->has('status') && (string) $request->input('method') === 'is_active') {
            $package->update([
                'is_active' => (bool) ((int) $request->input('status') === 1),
            ]);

            return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
        }

        return $this->sendResponse($package, 'ดำเนินการเสร็จสิ้น');
    }

    public function update(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $package = LottoGroupPackage::query()->find($id);
        if (! $package) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $validated = validator((array) $request->input('data', []), [
            'group_id' => ['required', 'integer', 'exists:lotto_groups,id'],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
            'bet_settings' => ['nullable', 'array'],
            'bet_settings.*.bet_type' => ['required_with:bet_settings', Rule::in(BetType::all())],
            'bet_settings.*.payout' => ['required_with:bet_settings', 'numeric', 'gt:0'],
            'bet_settings.*.discount_percent' => ['required_with:bet_settings', 'numeric', 'min:0', 'max:100'],
            'bet_settings.*.is_enabled' => ['nullable', 'boolean'],
        ])->validate();

        $exists = LottoGroupPackage::query()
            ->where('group_id', (int) $validated['group_id'])
            ->where('name', (string) $validated['name'])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return $this->sendError('ชื่อ package นี้มีอยู่แล้วในกลุ่มหวยดังกล่าว', 422);
        }

        DB::transaction(function () use ($package, $validated): void {
            $package->update([
                'group_id' => (int) $validated['group_id'],
                'name' => (string) $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? $package->is_active),
            ]);

            $betSettings = collect((array) ($validated['bet_settings'] ?? []))
                ->map(static function (array $row): array {
                    return [
                        'bet_type' => (string) $row['bet_type'],
                        'payout' => (float) $row['payout'],
                        'discount_percent' => (float) ($row['discount_percent'] ?? 0),
                        'is_enabled' => (bool) ($row['is_enabled'] ?? true),
                    ];
                })
                ->unique('bet_type')
                ->values();

            foreach ($betSettings as $row) {
                LottoGroupPackageBetSetting::query()->updateOrCreate(
                    [
                        'package_id' => (int) $package->id,
                        'bet_type' => $row['bet_type'],
                    ],
                    [
                        'payout' => $row['payout'],
                        'discount_percent' => $row['discount_percent'],
                        'is_enabled' => $row['is_enabled'],
                    ]
                );
            }
        });

        return $this->sendResponse($package->fresh(), 'อัปเดต package สำเร็จ');
    }

    public function delete(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $package = LottoGroupPackage::query()->find($id);
        if (! $package) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $wasUsed = LottoTicketItem::query()
            ->where('package_id_at_time', $id)
            ->exists();

        // Business rule: package ที่ถูกใช้งานแล้วห้าม hard delete -> disable only.
        if ($wasUsed) {
            $package->update(['is_active' => false]);

            return $this->sendResponse([], 'package เคยถูกใช้งานแล้ว ระบบปิดใช้งานแทนการลบถาวร');
        }

        // For consistency and rollback safety, prefer soft disable even if unused.
        $package->update(['is_active' => false]);

        return $this->sendResponse([], 'ปิดใช้งาน package สำเร็จ');
    }
}
