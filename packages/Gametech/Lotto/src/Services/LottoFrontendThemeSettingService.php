<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LottoFrontendThemeSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LottoFrontendThemeSettingService
{
    public const CACHE_KEY = 'frontend_api:lotto_theme:active';

    public function ensureSingleton(): LottoFrontendThemeSetting
    {
        $defaultPreset = $this->defaultPresetKey();
        $preset = $this->presetByKey($defaultPreset);
        $tokens = $this->normalizeTokens((array) ($preset['tokens'] ?? []));

        return LottoFrontendThemeSetting::query()->firstOrCreate(
            ['singleton_key' => 'default'],
            [
                'preset_key' => $defaultPreset,
                'tokens' => $tokens,
                'custom_tokens' => [],
                'is_customized' => false,
                'version' => 1,
                'updated_by' => null,
            ]
        );
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function updateTheme(array $payload, ?string $updatedBy = null): LottoFrontendThemeSetting
    {
        $validated = validator($payload, [
            'preset_key' => ['required', 'string'],
            'custom_tokens' => ['nullable', 'array'],
        ])->validate();

        $presetKey = strtolower(trim((string) $validated['preset_key']));
        $preset = $this->presetByKey($presetKey);

        $customTokens = $this->normalizeTokens((array) ($validated['custom_tokens'] ?? []));
        $mergedTokens = $this->mergeWithPresetTokens($presetKey, $customTokens);
        $this->assertValidColorTokens($mergedTokens);

        $setting = DB::transaction(function () use ($presetKey, $customTokens, $mergedTokens, $updatedBy): LottoFrontendThemeSetting {
            $row = LottoFrontendThemeSetting::query()
                ->where('singleton_key', 'default')
                ->lockForUpdate()
                ->first();
            if (! $row instanceof LottoFrontendThemeSetting) {
                $row = $this->ensureSingleton();
                $row = LottoFrontendThemeSetting::query()
                    ->where('singleton_key', 'default')
                    ->lockForUpdate()
                    ->first();
            }

            $nextVersion = ((int) $row->version) + 1;
            $row->update([
                'preset_key' => $presetKey,
                'tokens' => $mergedTokens,
                'custom_tokens' => $customTokens,
                'is_customized' => ! empty($customTokens),
                'version' => $nextVersion,
                'updated_by' => $updatedBy,
            ]);

            return $row->fresh();
        });

        Cache::forget(self::CACHE_KEY);

        return $setting;
    }

    /**
     * @return array<string,mixed>
     */
    public function formatForPublicResponse(): array
    {
        $setting = $this->ensureSingleton();
        $preset = $this->presetByKey((string) $setting->preset_key);
        $tokens = $this->mergeWithPresetTokens((string) $setting->preset_key, (array) ($setting->custom_tokens ?? []));

        return [
            'preset_key' => (string) $setting->preset_key,
            'preset_name' => (string) ($preset['name'] ?? ucfirst((string) $setting->preset_key)),
            'is_customized' => (bool) $setting->is_customized,
            'version' => (int) $setting->version,
            'tokens' => $tokens,
            'updated_at' => optional($setting->updated_at)->toIso8601String(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function formatForAdminResponse(): array
    {
        $setting = $this->ensureSingleton();
        $preset = $this->presetByKey((string) $setting->preset_key);

        return [
            'id' => (int) $setting->id,
            'preset_key' => (string) $setting->preset_key,
            'preset_name' => (string) ($preset['name'] ?? ucfirst((string) $setting->preset_key)),
            'is_customized' => (bool) $setting->is_customized,
            'version' => (int) $setting->version,
            'tokens' => $this->mergeWithPresetTokens((string) $setting->preset_key, (array) ($setting->custom_tokens ?? [])),
            'custom_tokens' => $this->normalizeTokens((array) ($setting->custom_tokens ?? [])),
            'presets' => $this->presetsForAdmin(),
            'updated_at' => optional($setting->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function presetsForAdmin(): array
    {
        $presets = (array) config('lotto.frontend_theme.presets', []);

        return collect($presets)->map(function ($preset, $key): array {
            return [
                'key' => (string) $key,
                'name' => (string) Arr::get((array) $preset, 'name', ucfirst((string) $key)),
                'tokens' => $this->normalizeTokens((array) Arr::get((array) $preset, 'tokens', [])),
            ];
        })->values()->all();
    }

    /**
     * @param  array<string,string>  $tokens
     */
    private function assertValidColorTokens(array $tokens): void
    {
        foreach ($this->requiredTokenKeys() as $tokenKey) {
            $value = trim((string) ($tokens[$tokenKey] ?? ''));
            if ($value === '') {
                throw ValidationException::withMessages([
                    'tokens.'.$tokenKey => ['ค่าสีห้ามว่าง'],
                ]);
            }

            $isHex = (bool) preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value);
            $isRgb = (bool) preg_match('/^rgb\(\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*\)$/', $value);
            $isRgba = (bool) preg_match('/^rgba\(\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(?:0|0?\.\d+|1(?:\.0+)?)\s*\)$/', $value);

            if (! $isHex && ! $isRgb && ! $isRgba) {
                throw ValidationException::withMessages([
                    'tokens.'.$tokenKey => ['รองรับเฉพาะ hex, rgb(), rgba()'],
                ]);
            }
        }
    }

    /**
     * @param  array<string,string>  $customTokens
     * @return array<string,string>
     */
    private function mergeWithPresetTokens(string $presetKey, array $customTokens): array
    {
        $presetTokens = $this->normalizeTokens((array) Arr::get($this->presetByKey($presetKey), 'tokens', []));
        $normalizedCustom = $this->normalizeTokens($customTokens);
        $allowedKeys = $this->requiredTokenKeys();

        $effective = [];
        foreach ($allowedKeys as $tokenKey) {
            $effective[$tokenKey] = array_key_exists($tokenKey, $normalizedCustom)
                ? $normalizedCustom[$tokenKey]
                : (string) ($presetTokens[$tokenKey] ?? '');
        }

        return $effective;
    }

    /**
     * @param  array<string,mixed>  $tokens
     * @return array<string,string>
     */
    private function normalizeTokens(array $tokens): array
    {
        $normalized = [];
        foreach ($tokens as $key => $value) {
            $normalized[strtolower(trim((string) $key))] = trim((string) $value);
        }

        return $normalized;
    }

    /**
     * @return array<int,string>
     */
    private function requiredTokenKeys(): array
    {
        return array_values((array) config('lotto.frontend_theme.required_token_keys', []));
    }

    /**
     * @return array<string,mixed>
     */
    private function presetByKey(string $presetKey): array
    {
        $presets = (array) config('lotto.frontend_theme.presets', []);
        if (! array_key_exists($presetKey, $presets)) {
            throw ValidationException::withMessages([
                'preset_key' => ['preset ที่เลือกไม่รองรับ'],
            ]);
        }

        return (array) $presets[$presetKey];
    }

    private function defaultPresetKey(): string
    {
        return strtolower((string) config('lotto.frontend_theme.default', 'midnight'));
    }
}
