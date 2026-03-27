<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LotteryMarketDataTable;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Support\ToggleFieldGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'name_en'    => ['nullable', 'string', 'max:191'],
            'name_kh'    => ['nullable', 'string', 'max:191'],
            'name_laos'  => ['nullable', 'string', 'max:191'],
            'logo'       => ['nullable', 'string', 'max:255'],
            'icon'       => ['nullable', 'string', 'max:255'],
            'code'       => ['required', 'string', 'max:100', 'alpha_dash', 'unique:lotto_markets,code'],
            'draw_mode' => ['nullable', 'string', 'in:' . implode(',', LotteryMarket::drawModes())],
            'auto_open_time' => ['nullable', 'date_format:H:i'],
            'auto_close_time' => ['nullable', 'date_format:H:i'],
            'auto_result_time' => ['nullable', 'date_format:H:i'],
            'result_url' => ['nullable', 'string', 'max:255'],
            'is_enabled' => ['nullable'],
        ])->validate();

        $this->validateAutoDrawConfiguration($validated);

        $request->validate([
            'logo_file' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:5120'],
            'icon_file' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:5120'],
        ]);

        $payload = [
            'group_id'   => (int) $validated['group_id'],
            'name'       => $validated['name'],
            'name_en'    => $this->nullableString($validated['name_en'] ?? null),
            'name_kh'    => $this->nullableString($validated['name_kh'] ?? null),
            'name_laos'  => $this->nullableString($validated['name_laos'] ?? null),
            'logo'       => $this->nullableString($validated['logo'] ?? null),
            'icon'       => $this->nullableString($validated['icon'] ?? null),
            'code'       => strtolower($validated['code']),
            'draw_mode'  => (string) ($validated['draw_mode'] ?? LotteryMarket::DRAW_MODE_MANUAL),
            'auto_open_time' => $this->normalizeTime($validated['auto_open_time'] ?? null),
            'auto_close_time' => $this->normalizeTime($validated['auto_close_time'] ?? null),
            'auto_result_time' => $this->normalizeTime($validated['auto_result_time'] ?? null),
            'result_url' => $this->nullableString($validated['result_url'] ?? null),
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
            'affect_existing_members' => false,
        ];

        $payload = $this->attachUploadedMedia($request, $payload);

        LotteryMarket::query()->create($payload);

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
            'name_en'    => ['nullable', 'string', 'max:191'],
            'name_kh'    => ['nullable', 'string', 'max:191'],
            'name_laos'  => ['nullable', 'string', 'max:191'],
            'logo'       => ['nullable', 'string', 'max:255'],
            'icon'       => ['nullable', 'string', 'max:255'],
            'code'       => ['required', 'string', 'max:100', 'alpha_dash', 'unique:lotto_markets,code,' . $market->id],
            'draw_mode' => ['nullable', 'string', 'in:' . implode(',', LotteryMarket::drawModes())],
            'auto_open_time' => ['nullable', 'date_format:H:i'],
            'auto_close_time' => ['nullable', 'date_format:H:i'],
            'auto_result_time' => ['nullable', 'date_format:H:i'],
            'result_url' => ['nullable', 'string', 'max:255'],
            'is_enabled' => ['nullable'],
        ])->validate();

        $this->validateAutoDrawConfiguration($validated);

        $request->validate([
            'logo_file' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:5120'],
            'icon_file' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:5120'],
        ]);

        $payload = [
            'group_id'   => (int) $validated['group_id'],
            'name'       => $validated['name'],
            'name_en'    => $this->nullableString($validated['name_en'] ?? null),
            'name_kh'    => $this->nullableString($validated['name_kh'] ?? null),
            'name_laos'  => $this->nullableString($validated['name_laos'] ?? null),
            'logo'       => $this->nullableString($validated['logo'] ?? null),
            'icon'       => $this->nullableString($validated['icon'] ?? null),
            'code'       => strtolower($validated['code']),
            'draw_mode'  => (string) ($validated['draw_mode'] ?? LotteryMarket::DRAW_MODE_MANUAL),
            'auto_open_time' => $this->normalizeTime($validated['auto_open_time'] ?? null),
            'auto_close_time' => $this->normalizeTime($validated['auto_close_time'] ?? null),
            'auto_result_time' => $this->normalizeTime($validated['auto_result_time'] ?? null),
            'result_url' => $this->nullableString($validated['result_url'] ?? null),
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
        ];

        $payload = $this->attachUploadedMedia($request, $payload, [
            'logo' => (string) ($market->logo ?? ''),
            'icon' => (string) ($market->icon ?? ''),
        ]);

        $market->update($payload);

        return $this->sendSuccess('อัปเดตรายการหวยเรียบร้อยแล้ว');
    }

    private function attachUploadedMedia(Request $request, array $payload, array $oldMedia = []): array
    {
        $map = [
            'logo_file' => 'logo',
            'icon_file' => 'icon',
        ];

        foreach ($map as $fileField => $column) {
            if (! $request->hasFile($fileField)) {
                continue;
            }

            $file = $request->file($fileField);
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $path = $file->store('lotto/media', 'public');
            $payload[$column] = '/storage/' . $path;

            $this->deleteOldPublicFile($oldMedia[$column] ?? null);
        }

        return $payload;
    }

    private function deleteOldPublicFile(?string $oldPath): void
    {
        if (! $oldPath || ! str_starts_with($oldPath, '/storage/')) {
            return;
        }

        try {
            $relative = substr($oldPath, strlen('/storage/'));
            if ($relative !== '') {
                Storage::disk('public')->delete($relative);
            }
        } catch (\Throwable $exception) {
            // ignore file deletion failure to keep update flow stable
        }
    }

    private function nullableString($value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeTime($value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed . ':00';
    }

    private function validateAutoDrawConfiguration(array $validated): void
    {
        $drawMode = (string) ($validated['draw_mode'] ?? LotteryMarket::DRAW_MODE_MANUAL);

        if ($drawMode === LotteryMarket::DRAW_MODE_MANUAL) {
            return;
        }

        if (empty($validated['auto_close_time'])) {
            throw new InvalidArgumentException('กรุณาระบุเวลาปิดรับอัตโนมัติสำหรับโหมดงวดอัตโนมัติ');
        }

        if (! empty($validated['auto_open_time']) && ! empty($validated['auto_close_time'])) {
            if ((string) $validated['auto_open_time'] === (string) $validated['auto_close_time']) {
                throw new InvalidArgumentException('เวลาเปิดรับและเวลาปิดรับต้องไม่เท่ากัน');
            }
        }
    }
}
