<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LotteryGroupDataTable;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Support\ToggleFieldGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $availableLanguages = (array) config('languages.available', []);
        $languageOptions = collect($availableLanguages)->map(static function ($item, $lang): array {
            $name = is_array($item) ? (string) ($item['name'] ?? strtoupper((string) $lang)) : strtoupper((string) $lang);

            return [
                'value' => (string) $lang,
                'text' => $name . ' (' . (string) $lang . ')',
            ];
        })->values()->all();

        return $dataTable->render($this->_config['view'], [
            'languageOptions' => $languageOptions,
        ]);
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
            'description' => ['nullable', 'string', 'max:5000'],
            'name_en'    => ['nullable', 'string', 'max:191'],
            'name_kh'    => ['nullable', 'string', 'max:191'],
            'name_laos'  => ['nullable', 'string', 'max:191'],
            'logo'       => ['nullable', 'string', 'max:255'],
            'icon'       => ['nullable', 'string', 'max:255'],
            'code'       => ['required', 'string', 'max:100', 'alpha_dash', 'unique:lotto_groups,code'],
            'sort'       => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable'],
        ])->validate();

        $request->validate([
            'logo_file' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:5120'],
            'icon_file' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:5120'],
        ]);

        $payload = [
            'name'       => $validated['name'],
            'description' => $this->normalizeDescriptionJson($validated['description'] ?? null),
            'name_en'    => $this->nullableString($validated['name_en'] ?? null),
            'name_kh'    => $this->nullableString($validated['name_kh'] ?? null),
            'name_laos'  => $this->nullableString($validated['name_laos'] ?? null),
            'logo'       => $this->nullableString($validated['logo'] ?? null),
            'icon'       => $this->nullableString($validated['icon'] ?? null),
            'code'       => strtolower($validated['code']),
            'sort'       => (int) ($validated['sort'] ?? 0),
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
            'affect_existing_members' => false,
        ];

        $payload = $this->attachUploadedMedia($request, $payload);

        LotteryGroup::query()->create($payload);

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
            'description' => ['nullable', 'string', 'max:5000'],
            'name_en'    => ['nullable', 'string', 'max:191'],
            'name_kh'    => ['nullable', 'string', 'max:191'],
            'name_laos'  => ['nullable', 'string', 'max:191'],
            'logo'       => ['nullable', 'string', 'max:255'],
            'icon'       => ['nullable', 'string', 'max:255'],
            'code'       => ['required', 'string', 'max:100', 'alpha_dash', 'unique:lotto_groups,code,' . $group->id],
            'sort'       => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable'],
        ])->validate();

        $request->validate([
            'logo_file' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:5120'],
            'icon_file' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:5120'],
        ]);

        $payload = [
            'name'       => $validated['name'],
            'description' => $this->normalizeDescriptionJson($validated['description'] ?? null),
            'name_en'    => $this->nullableString($validated['name_en'] ?? null),
            'name_kh'    => $this->nullableString($validated['name_kh'] ?? null),
            'name_laos'  => $this->nullableString($validated['name_laos'] ?? null),
            'logo'       => $this->nullableString($validated['logo'] ?? null),
            'icon'       => $this->nullableString($validated['icon'] ?? null),
            'code'       => strtolower($validated['code']),
            'sort'       => (int) ($validated['sort'] ?? 0),
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
        ];

        $payload = $this->attachUploadedMedia($request, $payload, [
            'logo' => (string) ($group->logo ?? ''),
            'icon' => (string) ($group->icon ?? ''),
        ]);

        $group->update($payload);

        return $this->sendSuccess('อัปเดตกลุ่มหวยเรียบร้อยแล้ว');
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

    private function normalizeDescriptionJson($value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);
        if (! is_array($decoded)) {
            return null;
        }

        $clean = [];
        foreach ($decoded as $lang => $text) {
            if (! is_scalar($text)) {
                continue;
            }

            $langKey = trim((string) $lang);
            $message = trim((string) $text);
            if ($langKey === '' || $message === '') {
                continue;
            }

            $clean[$langKey] = $message;
        }

        if (empty($clean)) {
            return null;
        }

        return json_encode($clean, JSON_UNESCAPED_UNICODE);
    }
}
