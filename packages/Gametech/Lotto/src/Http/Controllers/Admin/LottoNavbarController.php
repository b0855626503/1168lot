<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoNavbarDataTable;
use Gametech\Lotto\Models\LottoNavbar;
use Gametech\Lotto\Models\LottoNavbarItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LottoNavbarController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(LottoNavbarDataTable $dataTable)
    {
        return $dataTable->render($this->_config['view']);
    }

    public function list(Request $request): JsonResponse
    {
        $validated = validator($request->all(), [
            'code' => ['nullable', 'string', 'max:64'],
        ])->validate();

        $query = LottoNavbar::query()->orderByDesc('id');

        $code = trim((string) ($validated['code'] ?? ''));
        if ($code !== '') {
            $query->where('code', 'like', '%'.$code.'%');
        }

        $items = $query->get()->map(static function (LottoNavbar $navbar): array {
            return [
                'id' => (int) $navbar->id,
                'code' => (string) $navbar->code,
                'name' => (string) ($navbar->name ?? ''),
                'is_active' => (bool) $navbar->is_active,
                'is_published' => (bool) $navbar->is_published,
                'published_version' => $navbar->published_version !== null ? (int) $navbar->published_version : null,
                'published_at' => optional($navbar->published_at)->format('Y-m-d H:i:s'),
                'updated_at' => optional($navbar->updated_at)->format('Y-m-d H:i:s'),
            ];
        })->values()->all();

        return $this->sendResponse(['items' => $items], 'ดึงรายการ navbar config สำเร็จ');
    }

    public function loadData(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');

        $navbar = LottoNavbar::query()
            ->with(['items' => function ($query): void {
                $query->orderBy('sort_order')->orderBy('id');
            }])
            ->find($id);

        if (! $navbar instanceof LottoNavbar) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 404);
        }

        $items = $this->formatNavbarItemsForResponse($navbar->items->all());

        $published = LottoNavbar::query()
            ->with(['items' => function ($query): void {
                $query->orderBy('sort_order')->orderBy('id');
            }])
            ->where('code', (string) $navbar->code)
            ->where('is_published', true)
            ->where('is_active', true)
            ->orderByDesc('published_version')
            ->first();

        $publishedSnapshot = null;
        if ($published instanceof LottoNavbar) {
            $publishedSnapshot = [
                'id' => (int) $published->id,
                'published_version' => $published->published_version !== null ? (int) $published->published_version : null,
                'published_at' => optional($published->published_at)->format('Y-m-d H:i:s'),
                'items' => $this->formatNavbarItemsForResponse($published->items->all()),
            ];
        }

        return $this->sendResponse([
            'id' => (int) $navbar->id,
            'code' => (string) $navbar->code,
            'name' => (string) ($navbar->name ?? ''),
            'is_active' => (bool) $navbar->is_active,
            'is_published' => (bool) $navbar->is_published,
            'published_version' => $navbar->published_version !== null ? (int) $navbar->published_version : null,
            'published_at' => optional($navbar->published_at)->format('Y-m-d H:i:s'),
            'items' => $items,
            'published_snapshot' => $publishedSnapshot,
        ], 'ดึงข้อมูล navbar config สำเร็จ');
    }

    public function create(Request $request): JsonResponse
    {
        $payload = (array) $request->input('data', []);
        $validated = $this->validateNavbarPayload($payload);

        $exists = LottoNavbar::query()
            ->where('code', $validated['code'])
            ->where('is_published', false)
            ->exists();

        if ($exists) {
            return $this->sendError('มี draft ของ code นี้อยู่แล้ว กรุณาแก้ไข draft เดิม', 422);
        }

        $navbar = DB::transaction(function () use ($validated): LottoNavbar {
            $navbar = LottoNavbar::query()->create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'is_active' => $validated['is_active'],
                'is_published' => false,
            ]);

            $this->syncNavbarItems($navbar, $validated['items']);

            return $navbar->fresh();
        });

        return $this->sendResponse($navbar, 'สร้าง navbar config สำเร็จ');
    }

    public function edit(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $navbar = LottoNavbar::query()->find($id);
        if (! $navbar instanceof LottoNavbar) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 404);
        }

        if ($request->has('status') && (string) $request->input('method') === 'is_active') {
            if ((bool) $navbar->is_published) {
                return $this->sendError('ห้ามเปลี่ยนสถานะ draft/published ผ่านเมธอดนี้', 422);
            }

            $navbar->update([
                'is_active' => (bool) ((int) $request->input('status') === 1),
            ]);

            return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
        }

        return $this->sendResponse($navbar, 'ดำเนินการเสร็จสิ้น');
    }

    public function update(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $navbar = LottoNavbar::query()->find($id);

        if (! $navbar instanceof LottoNavbar) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 404);
        }

        if ((bool) $navbar->is_published) {
            return $this->sendError('แก้ไข published row โดยตรงไม่ได้ กรุณาแก้ draft แล้ว publish ใหม่', 422);
        }

        $payload = (array) $request->input('data', []);
        $validated = $this->validateNavbarPayload($payload, $navbar);

        DB::transaction(function () use ($navbar, $validated): void {
            $navbar->update([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'is_active' => $validated['is_active'],
            ]);

            $this->syncNavbarItems($navbar, $validated['items']);
        });

        return $this->sendResponse($navbar->fresh(), 'อัปเดต navbar config สำเร็จ');
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = validator($request->all(), [
            'id' => ['required', 'integer', 'exists:lotto_navbars,id'],
        ])->validate();

        $navbar = LottoNavbar::query()->find((int) $validated['id']);
        if (! $navbar instanceof LottoNavbar) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 404);
        }

        if ((bool) $navbar->is_published) {
            return $this->sendError('ห้ามลบ published row โดยตรง', 422);
        }

        $navbar->delete();

        return $this->sendSuccess('ลบ draft navbar config สำเร็จ');
    }

    public function publish(Request $request): JsonResponse
    {
        $validated = validator($request->all(), [
            'id' => ['required', 'integer', 'exists:lotto_navbars,id'],
        ])->validate();

        $source = LottoNavbar::query()
            ->with(['items' => function ($query): void {
                $query->orderBy('sort_order')->orderBy('id');
            }])
            ->find((int) $validated['id']);

        if (! $source instanceof LottoNavbar) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 404);
        }

        if ((bool) $source->is_published) {
            return $this->sendError('รายการนี้เป็น published row อยู่แล้ว', 422);
        }

        $activeItems = $source->items->where('is_active', true)->values();
        if ($activeItems->count() < 1) {
            return $this->sendError('ห้าม publish โดยไม่มี active item', 422);
        }

        $this->assertValidItemConstraints($activeItems->all());

        $published = DB::transaction(function () use ($source): LottoNavbar {
            $nextVersion = ((int) LottoNavbar::query()
                ->where('code', (string) $source->code)
                ->max('published_version')) + 1;

            LottoNavbar::query()
                ->where('code', (string) $source->code)
                ->where('is_published', true)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $published = LottoNavbar::query()->create([
                'code' => (string) $source->code,
                'name' => (string) ($source->name ?? ''),
                'is_active' => true,
                'is_published' => true,
                'published_version' => $nextVersion,
                'published_at' => now()->format('Y-m-d H:i:s'),
            ]);

            foreach ($source->items as $item) {
                LottoNavbarItem::query()->create([
                    'navbar_id' => (int) $published->id,
                    'key' => (string) $item->key,
                    'item_type' => (string) $item->item_type,
                    'icon_type' => (string) $item->icon_type,
                    'icon' => (string) ($item->icon ?? ''),
                    'label_json' => is_array($item->label_json) ? $item->label_json : [],
                    'action_type' => (string) $item->action_type,
                    'action_value' => (string) ($item->action_value ?? ''),
                    'sort_order' => (int) $item->sort_order,
                    'is_active' => (bool) $item->is_active,
                ]);
            }

            return $published;
        });

        $this->invalidatePublishedCache((string) $source->code, (int) $published->published_version);

        return $this->sendResponse($published, 'เผยแพร่ navbar config สำเร็จ');
    }

    public function unpublish(Request $request): JsonResponse
    {
        $validated = validator($request->all(), [
            'id' => ['required', 'integer', 'exists:lotto_navbars,id'],
        ])->validate();

        $navbar = LottoNavbar::query()->find((int) $validated['id']);

        if (! $navbar instanceof LottoNavbar) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 404);
        }

        if (! ((bool) $navbar->is_published) || ! ((bool) $navbar->is_active)) {
            return $this->sendError('รายการนี้ไม่ใช่ active published row', 422);
        }

        $navbar->update(['is_active' => false]);

        $this->invalidatePublishedCache((string) $navbar->code, (int) ($navbar->published_version ?? 0));

        return $this->sendSuccess('ยกเลิกการเผยแพร่สำเร็จ');
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array{code:string,name:?string,is_active:bool,items:array<int,array<string,mixed>>}
     */
    private function validateNavbarPayload(array $payload, ?LottoNavbar $updating = null): array
    {
        $validated = validator($payload, [
            'code' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_\-]+$/i'],
            'name' => ['nullable', 'string', 'max:191'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['required'],
        ], [
            'code.regex' => 'code ต้องเป็น a-z, 0-9, _, - เท่านั้น',
        ])->validate();

        $code = strtolower(trim((string) $validated['code']));

        $dupCodeQuery = LottoNavbar::query()
            ->where('code', $code)
            ->where('is_published', false);

        if ($updating instanceof LottoNavbar) {
            $dupCodeQuery->where('id', '!=', (int) $updating->id);
        }

        if ($dupCodeQuery->exists()) {
            throw ValidationException::withMessages([
                'code' => ['มี draft ของ code นี้อยู่แล้ว'],
            ]);
        }

        $itemsInput = $this->normalizeItemsInput($payload['items'] ?? null);
        if (count($itemsInput) < 1) {
            throw ValidationException::withMessages([
                'items' => ['ต้องมี item อย่างน้อย 1 รายการ'],
            ]);
        }

        $validatedItems = [];
        $presetIcons = ['home', 'ticket', 'wallet', 'user', 'menu', 'star', 'gift', 'settings'];
        foreach ($itemsInput as $index => $item) {
            $itemValidator = validator($item, [
                'id' => ['nullable', 'integer'],
                'key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_\-]+$/i'],
                'item_type' => ['required', Rule::in(['normal', 'center_cta'])],
                'icon_type' => ['required', Rule::in(['preset', 'emoji', 'image'])],
                'icon' => ['nullable', 'string', 'max:255'],
                'label_i18n' => ['required', 'array'],
                'action_type' => ['required', 'string', 'max:32'],
                'action_value' => ['nullable', 'string', 'max:255'],
                'sort_order' => ['required', 'integer', 'min:1', 'max:9999'],
                'is_active' => ['nullable', 'boolean'],
            ], [
                'key.regex' => 'key ของ item ต้องเป็น a-z, 0-9, _, - เท่านั้น',
            ]);

            $row = $itemValidator->validate();
            $labels = Arr::where((array) $row['label_i18n'], static fn ($value): bool => is_string($value));
            $normalizedLabels = [];
            foreach ($labels as $lang => $text) {
                $langKey = strtolower(trim((string) $lang));
                $normalizedLabels[$langKey] = trim((string) $text);
            }

            $th = trim((string) ($normalizedLabels['th'] ?? ''));
            if ($th === '') {
                throw ValidationException::withMessages([
                    'items.'.$index.'.label_i18n' => ['ต้องมี label ภาษา th อย่างน้อย 1 ค่า'],
                ]);
            }

            $iconType = (string) $row['icon_type'];
            $iconValue = trim((string) ($row['icon'] ?? ''));

            if ($iconType === 'preset' && ! in_array($iconValue, $presetIcons, true)) {
                throw ValidationException::withMessages([
                    'items.'.$index.'.icon' => ['icon_type=preset ต้องเลือก icon จากรายการที่รองรับ'],
                ]);
            }

            if ($iconType === 'emoji' && ! preg_match('/\p{Extended_Pictographic}/u', $iconValue)) {
                throw ValidationException::withMessages([
                    'items.'.$index.'.icon' => ['icon_type=emoji ต้องเป็น emoji จริง'],
                ]);
            }

            $validatedItems[] = [
                'id' => isset($row['id']) ? (int) $row['id'] : null,
                'key' => strtolower(trim((string) $row['key'])),
                'item_type' => (string) $row['item_type'],
                'icon_type' => $iconType,
                'icon' => $iconValue,
                'label_json' => $normalizedLabels,
                'action_type' => trim((string) $row['action_type']),
                'action_value' => trim((string) ($row['action_value'] ?? '')),
                'sort_order' => (int) $row['sort_order'],
                'is_active' => (bool) ($row['is_active'] ?? true),
            ];
        }

        $this->assertValidItemConstraints($validatedItems);

        return [
            'code' => $code,
            'name' => Arr::has($validated, 'name') ? trim((string) ($validated['name'] ?? '')) : null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'items' => $validatedItems,
        ];
    }

    /**
     * @param  array<int,array<string,mixed>>  $items
     */
    private function assertValidItemConstraints(array $items): void
    {
        $keys = collect($items)->pluck('key')->all();
        if (count($keys) !== count(array_unique($keys))) {
            throw ValidationException::withMessages([
                'items' => ['key ของ item ห้ามซ้ำกัน'],
            ]);
        }

        $activeItems = collect($items)->where('is_active', true)->values();

        $activeSorts = $activeItems->pluck('sort_order')->all();
        if (count($activeSorts) !== count(array_unique($activeSorts))) {
            throw ValidationException::withMessages([
                'items' => ['sort_order ของ active item ห้ามซ้ำกัน'],
            ]);
        }

        $centerCount = $activeItems->where('item_type', 'center_cta')->count();
        if ($centerCount > 1) {
            throw ValidationException::withMessages([
                'items' => ['active item ประเภท center_cta มีได้สูงสุด 1 รายการ'],
            ]);
        }
    }

    /**
     * @param  mixed  $itemsInput
     * @return array<int,array<string,mixed>>
     */
    private function normalizeItemsInput($itemsInput): array
    {
        if (is_string($itemsInput)) {
            $decoded = json_decode($itemsInput, true);
            if (! is_array($decoded)) {
                throw ValidationException::withMessages([
                    'items' => ['รูปแบบ items ต้องเป็น JSON array'],
                ]);
            }

            $itemsInput = $decoded;
        }

        if (! is_array($itemsInput)) {
            throw ValidationException::withMessages([
                'items' => ['รูปแบบ items ไม่ถูกต้อง'],
            ]);
        }

        return array_values(array_filter($itemsInput, static fn ($row): bool => is_array($row)));
    }

    /**
     * @param  array<int,array<string,mixed>>  $items
     */
    private function syncNavbarItems(LottoNavbar $navbar, array $items): void
    {
        $existingIds = LottoNavbarItem::query()
            ->where('navbar_id', (int) $navbar->id)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $incomingIds = collect($items)
            ->pluck('id')
            ->filter(static fn ($id): bool => $id !== null)
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();

        $deleteIds = array_values(array_diff($existingIds, $incomingIds));
        if (! empty($deleteIds)) {
            LottoNavbarItem::query()
                ->where('navbar_id', (int) $navbar->id)
                ->whereIn('id', $deleteIds)
                ->delete();
        }

        foreach ($items as $item) {
            $attributes = [
                'key' => (string) $item['key'],
                'item_type' => (string) $item['item_type'],
                'icon_type' => (string) $item['icon_type'],
                'icon' => (string) ($item['icon'] ?? ''),
                'label_json' => (array) ($item['label_json'] ?? []),
                'action_type' => (string) $item['action_type'],
                'action_value' => (string) ($item['action_value'] ?? ''),
                'sort_order' => (int) $item['sort_order'],
                'is_active' => (bool) $item['is_active'],
            ];

            $itemId = isset($item['id']) ? (int) $item['id'] : null;
            $existing = null;
            if ($itemId !== null) {
                $existing = LottoNavbarItem::query()
                    ->where('navbar_id', (int) $navbar->id)
                    ->where('id', $itemId)
                    ->first();
            }

            if ($existing instanceof LottoNavbarItem) {
                $existing->update($attributes);

                continue;
            }

            LottoNavbarItem::query()->create(array_merge($attributes, [
                'navbar_id' => (int) $navbar->id,
            ]));
        }
    }

    private function invalidatePublishedCache(string $code, int $version): void
    {
        if ($version < 1) {
            return;
        }

        $locales = array_keys((array) config('languages.available', []));
        $locales = array_values(array_unique(array_merge($locales, ['th', 'en'])));

        foreach ($locales as $locale) {
            $cacheKey = sprintf(
                'frontend_api:lotto_navbar:%s:%s:v%d',
                strtolower(trim($code)),
                strtolower(trim((string) $locale)),
                $version
            );
            Cache::forget($cacheKey);
        }
    }

    /**
     * @param  array<int,LottoNavbarItem>  $items
     * @return array<int,array<string,mixed>>
     */
    private function formatNavbarItemsForResponse(array $items): array
    {
        return collect($items)->map(static function (LottoNavbarItem $item): array {
            return [
                'id' => (int) $item->id,
                'key' => (string) $item->key,
                'item_type' => (string) $item->item_type,
                'icon_type' => (string) $item->icon_type,
                'icon' => (string) ($item->icon ?? ''),
                'label_i18n' => is_array($item->label_json) ? $item->label_json : [],
                'action_type' => (string) $item->action_type,
                'action_value' => (string) ($item->action_value ?? ''),
                'sort_order' => (int) $item->sort_order,
                'is_active' => (bool) $item->is_active,
            ];
        })->values()->all();
    }
}
