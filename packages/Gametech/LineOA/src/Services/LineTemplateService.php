<?php

namespace Gametech\LineOA\Services;

use Gametech\LineOA\Models\LineTemplate;
use Illuminate\Support\Facades\Cache;

/**
 * LineTemplateService
 *
 * หน้าที่:
 *  - ดึงข้อความ template ตาม key ที่กำหนด
 *  - รองรับการแก้ไขข้อความจากหลังบ้าน (ผ่านตาราง line_templates)
 *  - ถ้าไม่เจอใน DB → ใช้ default จาก config/line_oa.php
 *  - แทนค่า {variable} / {{variable}} / :variable ต่าง ๆ ลงในข้อความ
 *
 * การใช้งานหลัก:
 *   // 1) ข้อความล้วน (string)
 *   $text = $templateService->render('register.ask_phone', [
 *       'contact_name' => 'ลูกค้า A',
 *   ]);
 *
 *   // 2) LINE messages (text + image) จาก JSON template
 *   $messages = $templateService->renderMessages('welcome.default', [
 *       'display_name' => 'ลูกค้า A',
 *   ]);
 */
class LineTemplateService
{
    /**
     * prefix cache สำหรับ template (กัน key ชนของที่อื่น)
     */
    protected string $cachePrefix = 'line_oa_template_';

    /**
     * เวลา cache template (วินาที)
     * - 0 หรือ null = ไม่ cache
     */
    protected int $cacheSeconds;

    public function __construct(?int $cacheSeconds = 60)
    {
        $this->cacheSeconds = $cacheSeconds ?? 0;
    }

    /**
     * render ข้อความ template ตาม key (string ธรรมดา)
     *
     * @param  string  $key  เช่น 'register.ask_phone'
     * @param  array   $vars ตัวแปรสำหรับแทนค่าใน template เช่น ['username' => 'demo01']
     */
    public function render(string $key, array $vars = []): string
    {
        $template = $this->getMessage($key);

        if ($template === null || $template === '') {
            // กันเคสสุดขอบ ไม่ให้ return ค่าว่าง
            $template = $key;
        }

        return $this->replaceVariables($template, $vars);
    }

    /**
     * render template → LINE messages (ส่งเข้า LINE Messaging API ได้เลย)
     *
     * - รองรับทั้งกรณี template เป็น JSON (version + messages)
     * - และกรณี template เป็นข้อความล้วน (fallback เป็น text message เดียว)
     *
     * @param  string  $key   เช่น 'welcome.default'
     * @param  array   $vars  context สำหรับแทนตัวแปร
     *
     * @return array[] โครงสร้าง LINE messages แบบ:
     *   [
     *     ['type' => 'text', 'text' => '...'],
     *     ['type' => 'image', 'originalContentUrl' => '...', 'previewImageUrl' => '...'],
     *   ]
     */
    public function renderMessages(string $key, array $vars = []): array
    {
        $raw = $this->getMessage($key);

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        // พยายาม decode JSON ก่อน
        $data = json_decode($raw, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            // รองรับทั้งเคสที่มี 'messages' และเคสที่เป็น array ของ message ตรง ๆ
            if (isset($data['messages']) && is_array($data['messages'])) {
                return $this->buildLineMessagesFromJsonTemplate($data, $vars);
            }

            // เผื่ออนาคต: ถ้า config เก็บเป็น array ของ messages เลย
            if ($this->isArrayOfMessages($data)) {
                return $this->buildLineMessagesFromJsonTemplate(['messages' => $data], $vars);
            }
        }

        // ถ้าไม่ใช่ JSON ที่รู้จัก → ถือว่าเป็น template text ธรรมดา
        $text = $this->replaceVariables($raw, $vars);

        if (trim($text) === '') {
            return [];
        }

        return [
            [
                'type' => 'text',
                'text' => $text,
            ],
        ];
    }

    /**
     * ดึงข้อความ template (raw string) ตาม key
     *  - ลองจาก DB ก่อน
     *  - ถ้าไม่มี → ใช้ config('line_oa.templates')
     */
    public function getMessage(string $key): ?string
    {
        // 1) ลองจาก cache ก่อน
        if ($this->cacheSeconds > 0) {
            $cacheKey = $this->cachePrefix.$key;

            return Cache::remember($cacheKey, $this->cacheSeconds, function () use ($key) {
                return $this->resolveMessage($key);
            });
        }

        // ไม่ใช้ cache
        return $this->resolveMessage($key);
    }

    /**
     * clear cache ของ template บางตัว
     */
    public function forget(string $key): void
    {
        if ($this->cacheSeconds > 0) {
            Cache::forget($this->cachePrefix.$key);
            // เผื่อในอนาคตใช้ cache key อื่นสำหรับ messages
            Cache::forget($this->cachePrefix.'messages_'.$key);
        }
    }

    /**
     * clear cache ของ template ทั้งหมด (เฉพาะที่ใช้ prefix นี้)
     * NOTE: ถ้าต้องแบบ global จริง ๆ อาจใช้ cache tags หรือวิธีอื่นตาม driver
     */
    public function clearAllCache(): void
    {
        // ถ้า cache driver รองรับ tag สามารถใช้ tag ได้
        // ที่นี่ให้เป็น placeholder ไว้ก่อน
        // ตัวจริงโบ๊ทจะเลือกใช้ตาม infra ที่ใช้
    }

    /**
     * ใช้ตอนหลังบ้าน save/อัปเดต template เสร็จ
     * เพื่อให้ค่าใหม่ถูกโหลดโดยไม่ต้องรอหมดเวลา cache
     */
    public function onTemplateUpdated(string $key): void
    {
        $this->forget($key);
    }

    /**
     * แกนจริงในการ "หา message จาก key"
     * 1) DB: line_templates
     * 2) config: line_oa.templates
     */
    protected function resolveMessage(string $key): ?string
    {
        // 1) ลองหาใน DB ก่อน
        $dbTemplate = LineTemplate::query()
            ->where('key', $key)
            ->where('enabled', true)
            ->orderByDesc('id')
            ->first();

        if ($dbTemplate && $dbTemplate->message !== null && $dbTemplate->message !== '') {
            return $dbTemplate->message;
        }

        // 2) ถ้าไม่เจอใน DB → ใช้ default จาก config
        $default = config('line_oa.templates.'.$key);

        if (is_string($default)) {
            return $default;
        }

        // 3) ถ้าไม่เจอจริง ๆ → null
        return null;
    }

    /**
     * แทนค่า {variable} / {{variable}} / :variable ต่าง ๆ ใน message
     *
     * ตัวอย่าง:
     *   template: "ยินดีต้อนรับ {username} เข้าสู่ {web_name}"
     *   vars:     ['username' => 'demo01', 'web_name' => 'THEGRAND789']
     *   ผลลัพธ์: "ยินดีต้อนรับ demo01 เข้าสู่ THEGRAND789"
     */
    protected function replaceVariables(string $message, array $vars): string
    {
        if ($message === '' || empty($vars)) {
            return $message;
        }

        // เตรียม map สำหรับ strtr
        $replace = [];
        foreach ($vars as $key => $value) {
            $value = (string) $value;

            // รองรับทั้ง {key}, :key, และ {{key}} (สำหรับ JSON template แบบที่โบ๊ทออกแบบ)
            $replace['{'.$key.'}']   = $value;
            $replace[':'.$key]       = $value;
            $replace['{{'.$key.'}}'] = $value;
        }

        return strtr($message, $replace);
    }

    /**
     * helper: ดึงค่า default ตาม key พร้อม fallback
     * (ถ้าวันหลังอยากใช้ที่อื่น)
     */
    public function getDefaultFromConfig(string $key, ?string $fallback = null): ?string
    {
        $value = config('line_oa.templates.'.$key);

        if (is_string($value)) {
            return $value;
        }

        return $fallback;
    }

    /**
     * ลิสต์ template ทั้งหมดที่เริ่มต้นด้วย prefix ที่กำหนด
     * เช่น 'register.' → จะได้ทุก key ที่เกี่ยวกับสมัครสมาชิก
     *
     * ใช้ประโยชน์ในหน้าหลังบ้าน เวลาอยากโชว์เป็นกลุ่ม
     */
    public function listByPrefix(string $prefix): array
    {
        $prefix = rtrim($prefix, '.').'.';

        $items = LineTemplate::query()
            ->where('key', 'like', $prefix.'%')
            ->orderBy('key')
            ->get(['key', 'message', 'enabled']);

        $result = [];

        foreach ($items as $item) {
            $result[] = [
                'key'     => $item->key,
                'message' => $item->message,
                'enabled' => (bool) $item->enabled,
            ];
        }

        return $result;
    }

    /* =====================================================================
     *  ส่วน helper สำหรับ JSON template → LINE messages
     * ===================================================================== */

    /**
     * ตรวจว่า array นี้หน้าตาเหมือน "array ของ message" ไหม
     * (เช่น [['kind' => 'text', ...], ['kind' => 'image', ...]])
     */
    protected function isArrayOfMessages(array $data): bool
    {
        if ($data === []) {
            return false;
        }

        // เอา element แรกมาดูโครงสร้าง
        $first = reset($data);
        return is_array($first) && array_key_exists('kind', $first);
    }

    /**
     * แปลง JSON template (version + messages) ให้กลายเป็น LINE messages
     *
     * รูปแบบ JSON ที่รองรับ:
     *
     * {
     *   "version": 1,
     *   "messages": [
     *     { "kind": "text", "text": "..." },
     *     { "kind": "image", "original": "...", "preview": "..." }
     *   ]
     * }
     */
    protected function buildLineMessagesFromJsonTemplate(array $template, array $vars): array
    {
        $messages = $template['messages'] ?? [];
        if (! is_array($messages) || empty($messages)) {
            return [];
        }

        $result = [];

        foreach ($messages as $item) {
            if (! is_array($item)) {
                continue;
            }

            $kind = $item['kind'] ?? 'text';

            if ($kind === 'text') {
                $text = (string) ($item['text'] ?? '');
                $text = $this->replaceVariables($text, $vars);

                if (trim($text) === '') {
                    continue;
                }

                $result[] = [
                    'type' => 'text',
                    'text' => $text,
                ];
            } elseif ($kind === 'image') {
                $original = (string) ($item['original'] ?? '');
                $preview  = (string) ($item['preview'] ?? '');

                $original = $this->replaceVariables($original, $vars);
                $preview  = $this->replaceVariables($preview, $vars);

                if ($original === '') {
                    continue;
                }

                $result[] = [
                    'type'              => 'image',
                    'contentUrl'=> $original,
                    'previewUrl'=> $preview,
                    'originalContentUrl'=> $original,
                    'previewImageUrl'   => $preview !== '' ? $preview : $original,
                ];
            } else {
                // เผื่ออนาคตจะรองรับ kind อื่น เช่น sticker / flex ฯลฯ
                continue;
            }
        }

        return $result;
    }
}
