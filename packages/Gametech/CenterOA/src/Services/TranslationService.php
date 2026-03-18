<?php

namespace Gametech\CenterOA\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    /**
     * driver ปัจจุบัน: google|argos_cloud|argos_http|argos_local
     */
    protected string $driver;

    /**
     * config ทั้งก้อนของ services.translation
     *
     * @var array<string,mixed>
     */
    protected array $config = [];

    /**
     * เปิดใช้งาน translation ไหม (ตาม driver ปัจจุบัน)
     */
    protected bool $enabled = false;

    /**
     * ภาษาต้นทาง default (บาง driver อาจไม่ใช้)
     */
    protected ?string $defaultSource = null;

    /**
     * ภาษาปลายทาง default
     */
    protected ?string $defaultTarget = 'th';

    /**
     * timeout รวม (วินาที) เฉลี่ย ๆ
     */
    protected int $timeout = 3;

    /**
     * TranslationService constructor.
     */
    public function __construct()
    {
        // อ่าน config หลัก
        $config = config('services.translation', []);

        $this->config = $config;
        $this->driver = (string) Arr::get($config, 'driver', 'google');
        $this->defaultTarget = Arr::get($config, 'google.default_target', 'th');

        // ตั้งค่า driver-specific เบื้องต้น (ใช้ google เป็นฐาน)
        if ($this->driver === 'google') {
            $google = Arr::get($config, 'google', []);
            $this->enabled = (bool) Arr::get($google, 'enabled', false);
            $this->defaultSource = Arr::get($google, 'default_source');
            $this->defaultTarget = Arr::get($google, 'default_target', $this->defaultTarget);
            $this->timeout = (int) Arr::get($google, 'timeout', 3);

            // เผื่อกรณีโปรเจกต์เดิมมี config('services.google_translate') อยู่แล้ว
            if (! $this->enabled && empty(Arr::get($google, 'api_key'))) {
                $legacy = config('services.google_translate', []);
                if (! empty($legacy)) {
                    $this->config['google'] = array_merge($legacy, [
                        'enabled' => Arr::get($legacy, 'enabled', true),
                        'default_source' => Arr::get($legacy, 'default_source'),
                        'default_target' => Arr::get($legacy, 'default_target', 'th'),
                        'timeout' => Arr::get($legacy, 'timeout', 3),
                    ]);
                    $this->enabled = (bool) Arr::get($this->config['google'], 'enabled', true);
                    $this->defaultSource = Arr::get($this->config['google'], 'default_source');
                    $this->defaultTarget = Arr::get($this->config['google'], 'default_target', 'th');
                    $this->timeout = (int) Arr::get($this->config['google'], 'timeout', 3);
                }
            }
        } else {
            // driver อื่น ๆ
            $driverConfig = Arr::get($config, $this->driver, []);
            $this->enabled = (bool) Arr::get($driverConfig, 'enabled', false);
            // default target ยังใช้ค่าเดียวกันไปก่อน (หรือจะอ่านจาก driver นั้น ๆ ก็ได้)
        }
    }

    /**
     * แปลข้อความจาก source → target
     *
     * @param  string  $text  ข้อความต้นฉบับ
     * @param  string|null  $target  ภาษาปลายทาง เช่น 'en', 'ja', 'zh-Hant'
     * @param  string|null  $source  ภาษาต้นทาง เช่น 'th', null = auto (ตามความสามารถของ provider)
     * @return array{
     *     original_text: string,
     *     translated_text: string,
     *     source: string|null,
     *     target: string,
     *     detected_source: string|null,
     *     provider: string
     * }
     */
    public function translate(string $text, ?string $target = null, ?string $source = null): array
    {
        $text = trim($text);

        // ถ้าข้อความว่าง → ไม่ต้องทำอะไร
        if ($text === '') {
            return [
                'original_text' => '',
                'translated_text' => '',
                'source' => $source,
                'target' => $target ?? $this->defaultTarget ?? 'th',
                'detected_source' => $source,
                'provider' => 'none',
            ];
        }

        // ถ้าไม่ได้ enable หรือ driver ไม่รู้จัก → คืนค่าเดิม
        if (! $this->enabled) {
            return [
                'original_text' => $text,
                'translated_text' => $text,
                'source' => $source,
                'target' => $target ?? $this->defaultTarget ?? 'th',
                'detected_source' => $source,
                'provider' => 'disabled',
            ];
        }

        $target = $target ?: $this->defaultTarget ?: 'th';
        $source = $source ?: $this->defaultSource;

        try {
            switch ($this->driver) {
                case 'google':
                    return $this->translateWithGoogle($text, $target, $source);

                case 'argos_cloud':
                    return $this->translateWithArgosCloud($text, $target, $source);

                case 'argos_http':
                    return $this->translateWithArgosHttp($text, $target, $source);

                case 'argos_local':
                    return $this->translateWithArgosLocal($text, $target, $source);

                default:
                    Log::channel('center_oa')->warning('[Translation] unknown_driver', [
                        'driver' => $this->driver,
                    ]);

                    return [
                        'original_text' => $text,
                        'translated_text' => $text,
                        'source' => $source,
                        'target' => $target,
                        'detected_source' => $source,
                        'provider' => 'unknown-driver',
                    ];
            }
        } catch (\Throwable $e) {
            Log::channel('center_oa')->error('[Translation] translate_exception', [
                'driver' => $this->driver,
                'message' => $e->getMessage(),
            ]);

            return [
                'original_text' => $text,
                'translated_text' => $text,
                'source' => $source,
                'target' => $target,
                'detected_source' => $source,
                'provider' => $this->driver.'-exception',
            ];
        }
    }

    /**
     * ตรวจจับภาษา (บาง driver ใช้ trick ผ่าน translate, บาง driverอาจรองรับ detect โดยตรง)
     *
     * @param  string|null  $hintLanguage  ภาษา hint เช่น 'th' หรือ null
     * @return string|null language code เช่น 'th', 'en', 'ja', ถ้าหาไม่ได้ให้คืน null
     */
    public function detectLanguage(string $text, ?string $hintLanguage = null): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return $hintLanguage;
        }

        // สำหรับ google: ใช้ observed detectedSourceLanguage จากผล translate
        if ($this->driver === 'google') {
            $result = $this->translate($text, $this->defaultTarget ?? 'th', $hintLanguage);

            return $result['detected_source'] ?: $hintLanguage;
        }

        // Argos cloud ณ ตอนนี้ยังไม่มี detect language โดยตรง → ใช้ hint กลับไป
        if (in_array($this->driver, ['argos_cloud', 'argos_http', 'argos_local'], true)) {
            // ถ้าอยากฉลาดขึ้น อนาคตสามารถไปผูก model detect แยกได้
            return $hintLanguage;
        }

        return $hintLanguage;
    }

    /**
     * helper สำหรับเลือก target language ตาม conversation/contact/account
     *
     * @param  string|null  $conversationOutgoing  ภาษา override ของห้อง (ถ้ามี)
     * @param  string|null  $contactPreferred  ภาษาหลักของ contact (ถ้ามี)
     * @param  string|null  $fallback  ภาษาสำรองสุดท้าย เช่น 'th'
     */
    public function resolveTargetLanguage(
        ?string $conversationOutgoing,
        ?string $contactPreferred,
        ?string $fallback = 'th'
    ): string {
        return $conversationOutgoing
            ?? $contactPreferred
            ?? $this->defaultTarget
            ?? $fallback
            ?? 'th';
    }

    /**
     * helper: ระบบนี้เปิดแปลภาษาอยู่ไหม (ดูตาม driver/enable)
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * ดู driver ปัจจุบัน เผื่ออยาก debug/log เพิ่ม
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    // ------------------------------------------------------------------------
    //  ด้านล่างนี้เป็น implementation แยกตาม provider
    // ------------------------------------------------------------------------

    /**
     * แปลด้วย Google Cloud Translation API v2
     *
     * @see https://cloud.google.com/translate/docs/reference/rest/v2/translate
     */
    protected function translateWithGoogle(string $text, string $target, ?string $source = null): array
    {
        $google = Arr::get($this->config, 'google', []);
        $apiKey = Arr::get($google, 'api_key');
        $timeout = (int) Arr::get($google, 'timeout', $this->timeout);

        if (empty($apiKey)) {
            Log::channel('center_oa')->warning('[Translation] google_api_key_missing');

            return [
                'original_text' => $text,
                'translated_text' => $text,
                'source' => $source,
                'target' => $target,
                'detected_source' => $source,
                'provider' => 'google-missing-key',
            ];
        }

        $payload = [
            'q' => $text,
            'target' => $target,
            'format' => 'text',
            'key' => $apiKey,
        ];

        if (! empty($source)) {
            $payload['source'] = $source;
        }

        $response = Http::timeout($timeout)
            ->retry(1, 200)
            ->asForm()
            ->post('https://translation.googleapis.com/language/translate/v2', $payload);

        if (! $response->successful()) {
            Log::channel('center_oa')->warning('[Translation] google_translate_failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'original_text' => $text,
                'translated_text' => $text,
                'source' => $source,
                'target' => $target,
                'detected_source' => $source,
                'provider' => 'google-translate-failed',
            ];
        }

        $data = $response->json();
        $translation = Arr::get($data, 'data.translations.0', []);

        $translatedText = Arr::get($translation, 'translatedText', $text);
        $detectedSource = Arr::get($translation, 'detectedSourceLanguage', $source);

        return [
            'original_text' => $text,
            'translated_text' => $translatedText,
            'source' => $source,
            'target' => $target,
            'detected_source' => $detectedSource,
            'provider' => 'google-translate',
        ];
    }

    /**
     * แปลด้วย Argos Cloud API (https://translate.argosopentech.com/translate)
     */
    protected function translateWithArgosCloud(string $text, string $target, ?string $source = null): array
    {
        $cfg = Arr::get($this->config, 'argos_cloud', []);
        $baseUri = rtrim((string) Arr::get($cfg, 'base_uri', 'https://translate.argosopentech.com'), '/');
        $timeout = (int) Arr::get($cfg, 'timeout', 6);

        // Argos ตัว public endpoint ใช้ JSON แบบ:
        // { q: "Hello!", source: "en", target: "es" }
        // บาง implementation รองรับ "auto" แต่เพื่อความชัวร์ ใช้ source ตามที่เรารู้
        $payload = [
            'q' => $text,
            'source' => $source ?: 'en', // ถ้าอยากใช้ auto แบบจริงจัง ต้องเช็ค docs อีกที
            'target' => $target,
        ];

        $response = Http::timeout($timeout)
            ->retry(1, 200)
            ->post($baseUri.'/translate', $payload);

        if (! $response->successful()) {
            Log::channel('center_oa')->warning('[Translation] argos_cloud_failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'original_text' => $text,
                'translated_text' => $text,
                'source' => $source,
                'target' => $target,
                'detected_source' => $source,
                'provider' => 'argos-cloud-failed',
            ];
        }

        $json = $response->json();
        $translatedText = Arr::get($json, 'translatedText', $text);

        return [
            'original_text' => $text,
            'translated_text' => $translatedText,
            'source' => $source,
            'target' => $target,
            'detected_source' => $source,
            'provider' => 'argos-cloud',
        ];
    }

    /**
     * ไว้สำหรับอนาคต: แปลผ่าน Argos HTTP service ภายในระบบ (self-hosted)
     */
    protected function translateWithArgosHttp(string $text, string $target, ?string $source = null): array
    {
        Log::channel('center_oa')->warning('[Translation] argos_http_not_implemented');

        return [
            'original_text' => $text,
            'translated_text' => $text,
            'source' => $source,
            'target' => $target,
            'detected_source' => $source,
            'provider' => 'argos-http-not-implemented',
        ];
    }

    /**
     * ไว้สำหรับอนาคต: แปลผ่าน Argos local (เช่น CLI หรือ daemon)
     */
    protected function translateWithArgosLocal(string $text, string $target, ?string $source = null): array
    {
        Log::channel('center_oa')->warning('[Translation] argos_local_not_implemented');

        return [
            'original_text' => $text,
            'translated_text' => $text,
            'source' => $source,
            'target' => $target,
            'detected_source' => $source,
            'provider' => 'argos-local-not-implemented',
        ];
    }
}
