<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\Lotto\Models\LottoNavbar;
use Gametech\Lotto\Models\LottoNavbarItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LottoNavbarConfigController extends BaseController
{
    public function show(Request $request): JsonResponse
    {
        $language = $this->requestLanguage($request);
        $code = trim((string) $request->query('code', 'mobile_bottom_nav'));
        if ($code === '') {
            $code = 'mobile_bottom_nav';
        }

        $published = LottoNavbar::query()
            ->where('code', strtolower($code))
            ->where('is_published', true)
            ->where('is_active', true)
            ->orderByDesc('published_version')
            ->first();

        if (! $published instanceof LottoNavbar) {
            return $this->sendError('ไม่พบ navbar config ที่เผยแพร่แล้ว', 404);
        }

        $version = (int) ($published->published_version ?? 0);
        $cacheKey = sprintf(
            'frontend_api:lotto_navbar:%s:%s:v%d',
            strtolower($code),
            strtolower($language),
            $version
        );

        $payload = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($published, $language): array {
            $items = LottoNavbarItem::query()
                ->where('navbar_id', (int) $published->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            return [
                'code' => (string) $published->code,
                'published_version' => (int) ($published->published_version ?? 0),
                'items' => $items->map(function (LottoNavbarItem $item) use ($language): array {
                    $labels = is_array($item->label_json) ? $item->label_json : [];

                    return [
                        'key' => (string) $item->key,
                        'item_type' => (string) $item->item_type,
                        'icon_type' => (string) $item->icon_type,
                        'icon' => (string) ($item->icon ?? ''),
                        'label' => $this->resolveLabel($labels, $language, (string) $item->key),
                        'label_i18n' => $labels,
                        'action_type' => (string) $item->action_type,
                        'action_value' => (string) ($item->action_value ?? ''),
                        'sort_order' => (int) $item->sort_order,
                    ];
                })->values()->all(),
            ];
        });

        return $this->sendResponse([
            'language' => $language,
            'navbar' => $payload,
        ], 'ดึง navbar config สำเร็จ');
    }

    /**
     * @param  array<string,mixed>  $labels
     */
    private function resolveLabel(array $labels, string $language, string $key): string
    {
        $normalized = [];
        foreach ($labels as $lang => $value) {
            if (! is_string($value)) {
                continue;
            }

            $normalized[strtolower(trim((string) $lang))] = trim($value);
        }

        $candidates = [strtolower($language), 'th', 'en'];
        foreach ($candidates as $candidate) {
            $value = trim((string) ($normalized[$candidate] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return $key;
    }
}
