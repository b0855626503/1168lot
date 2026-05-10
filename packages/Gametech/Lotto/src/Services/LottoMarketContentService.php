<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LottoMarketContent;
use Gametech\Lotto\Support\LottoContentLocale;
use Gametech\Lotto\Support\LottoContentSanitizer;
use Illuminate\Support\Facades\Cache;

class LottoMarketContentService
{
    public const CACHE_TTL_SECONDS = 60;
    private const CONTENT_FIELDS = [
        'title',
        'summary',
        'rules_content',
        'schedule_content',
        'prize_content',
        'formula_content',
        'seo_title',
        'seo_description',
    ];

    /**
     * @return array<int, string>
     */
    public function supportedLocales(): array
    {
        return LottoContentLocale::supported();
    }

    public function normalizeLocale(?string $locale): string
    {
        return LottoContentLocale::normalize($locale);
    }

    /**
     * @param  array<int|string, mixed>  $contentsByLocale
     */
    public function upsertContentsForMarket(int $marketId, array $contentsByLocale): void
    {
        $normalizedMap = [];
        foreach ($contentsByLocale as $locale => $payload) {
            if (! is_array($payload)) {
                continue;
            }

            $normalizedMap[$this->normalizeLocale((string) $locale)] = $payload;
        }

        foreach ($this->supportedLocales() as $locale) {
            $raw = $normalizedMap[$locale] ?? null;
            if (! is_array($raw)) {
                continue;
            }

            $sanitized = LottoContentSanitizer::sanitizePayload($raw);
            $isEnabled = (bool) ($sanitized['is_enabled'] ?? true);

            LottoMarketContent::query()->updateOrCreate(
                [
                    'market_id' => $marketId,
                    'locale' => $locale,
                ],
                [
                    'title' => $sanitized['title'] ?? null,
                    'summary' => $sanitized['summary'] ?? null,
                    'rules_content' => $sanitized['rules_content'] ?? null,
                    'schedule_content' => $sanitized['schedule_content'] ?? null,
                    'prize_content' => $sanitized['prize_content'] ?? null,
                    'formula_content' => $sanitized['formula_content'] ?? null,
                    'seo_title' => $sanitized['seo_title'] ?? null,
                    'seo_description' => $sanitized['seo_description'] ?? null,
                    'is_enabled' => $isEnabled,
                ]
            );
        }
    }

    public function invalidateMarketCache(int $marketId): void
    {
        foreach ($this->supportedLocales() as $locale) {
            Cache::forget($this->cacheKey($marketId, $locale));
        }
    }

    /**
     * @return array<string, array<string,mixed>>
     */
    public function contentsMapForAdmin(int $marketId): array
    {
        $rows = LottoMarketContent::query()
            ->where('market_id', $marketId)
            ->get()
            ->keyBy('locale');

        $result = [];
        foreach ($this->supportedLocales() as $locale) {
            $row = $rows->get($locale);
            $payload = $row instanceof LottoMarketContent ? $row->toArray() : [];
            $result[$locale] = $this->contentObject($payload);
            $result[$locale]['is_enabled'] = $row instanceof LottoMarketContent
                ? (bool) $row->is_enabled
                : true;
        }

        return $result;
    }

    /**
     * @return array{locale:string,fallback_locale:?string,content:array<string,mixed>}
     */
    public function resolveForFrontend(int $marketId, ?string $requestedLocale): array
    {
        $locale = $this->normalizeLocale($requestedLocale);

        $primary = $this->getEnabledContentByLocaleWithCache($marketId, $locale);
        if ($primary !== null) {
            if ($locale === 'th') {
                return [
                    'locale' => $locale,
                    'fallback_locale' => null,
                    'content' => $this->contentObject($primary),
                ];
            }

            $fallback = $this->getEnabledContentByLocaleWithCache($marketId, 'th');
            if ($fallback !== null) {
                [$content, $usedFallback] = $this->mergeWithFallbackContent($primary, $fallback);

                return [
                    'locale' => $locale,
                    'fallback_locale' => $usedFallback ? 'th' : null,
                    'content' => $content,
                ];
            }

            return [
                'locale' => $locale,
                'fallback_locale' => null,
                'content' => $this->contentObject($primary),
            ];
        }

        $fallback = $this->getEnabledContentByLocaleWithCache($marketId, 'th');
        if ($fallback !== null) {
            return [
                'locale' => $locale,
                'fallback_locale' => 'th',
                'content' => $this->contentObject($fallback),
            ];
        }

        return [
            'locale' => $locale,
            'fallback_locale' => null,
            'content' => $this->emptyContentObject(),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getEnabledContentByLocaleWithCache(int $marketId, string $locale): ?array
    {
        $normalizedLocale = $this->normalizeLocale($locale);

        $cached = Cache::remember(
            $this->cacheKey($marketId, $normalizedLocale),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            static function () use ($marketId, $normalizedLocale): ?array {
                $row = LottoMarketContent::query()
                    ->where('market_id', $marketId)
                    ->where('locale', $normalizedLocale)
                    ->where('is_enabled', true)
                    ->first();

                return $row?->toArray();
            }
        );

        return is_array($cached) ? $cached : null;
    }

    public function cacheKey(int $marketId, string $locale): string
    {
        return sprintf('lotto:market-content:%d:%s', $marketId, $this->normalizeLocale($locale));
    }

    /**
     * @param  array<string,mixed>  $row
     * @return array<string,mixed>
     */
    public function contentObject(array $row): array
    {
        $content = [];
        foreach (self::CONTENT_FIELDS as $field) {
            $content[$field] = $row[$field] ?? null;
        }

        return $content;
    }

    /**
     * @return array<string,mixed>
     */
    public function emptyContentObject(): array
    {
        return array_fill_keys(self::CONTENT_FIELDS, null);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string,mixed>
     */
    public function sanitizeSingleLocalePayload(array $payload): array
    {
        return LottoContentSanitizer::sanitizePayload($payload);
    }

    /**
     * @param  array<string,mixed>  $primary
     * @param  array<string,mixed>  $fallback
     * @return array{0:array<string,mixed>,1:bool}
     */
    private function mergeWithFallbackContent(array $primary, array $fallback): array
    {
        $primaryContent = $this->contentObject($primary);
        $fallbackContent = $this->contentObject($fallback);
        $merged = $primaryContent;
        $usedFallback = false;

        foreach (self::CONTENT_FIELDS as $field) {
            if ($this->hasTextValue($merged[$field] ?? null)) {
                continue;
            }

            if (! $this->hasTextValue($fallbackContent[$field] ?? null)) {
                continue;
            }

            $merged[$field] = $fallbackContent[$field];
            $usedFallback = true;
        }

        return [$merged, $usedFallback];
    }

    private function hasTextValue(mixed $value): bool
    {
        return trim((string) ($value ?? '')) !== '';
    }
}
