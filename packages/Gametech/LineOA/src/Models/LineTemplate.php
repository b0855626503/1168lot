<?php

namespace Gametech\LineOA\Models;

use Gametech\LineOA\Contracts\LineTemplate as LineTemplateContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class LineTemplate extends Model implements LineTemplateContract
{
    protected $table = 'line_templates';

    protected $fillable = [
        'category',
        'key',
        'message',
        'message_type',
        'description',
        'enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    /**
     * Scope: เอาเฉพาะ template ที่เปิดใช้งานอยู่
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope: filter ตาม category
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * หาด้วย key เดียว
     */
    public function scopeKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    /**
     * helper: หา template แรกตาม key (ใช้ใน service ได้)
     */
    public static function findByKey(string $key): ?self
    {
        return static::query()
            ->where('key', $key)
            ->first();
    }

    // ถ้าอยากผูกกับ model พนักงานจริง ๆ (เช่น Gametech\Admin\Models\Employee)
    // สามารถแก้เป็น belongsTo ได้
    //
    // public function creator()
    // {
    //     return $this->belongsTo(Employee::class, 'created_by');
    // }
    //
    // public function updater()
    // {
    //     return $this->belongsTo(Employee::class, 'updated_by');
    // }

    public function getStructuredMessage(): array
    {
        $value = $this->message;

        // ถ้าจริง ๆ column เป็น string แล้ว cast ไม่ได้ ให้ลอง decode เพิ่ม
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            } else {
                // กรณีเป็น text ตรง ๆ
                return [
                    'version' => 1,
                    'messages' => [
                        [
                            'kind' => 'text',
                            'text' => $value,
                        ],
                    ],
                ];
            }
        }

        if (! is_array($value)) {
            return [
                'version' => 1,
                'messages' => [],
            ];
        }

        // ถ้า dev ใส่มาเป็น array แต่ไม่มี wrapper ก็ normalize ให้
        if (! Arr::has($value, 'messages')) {
            return [
                'version' => Arr::get($value, 'version', 1),
                'messages' => Arr::get($value, 'messages', []),
            ];
        }

        return $value;
    }

    /**
     * แปลง template นี้ให้กลายเป็น array ของ LINE messages
     *
     * @param  array  $vars  ตัวแปรแทน placeholder เช่น ['display_name' => 'Boat Junior']
     * @return array[] (พร้อมส่งเข้า pushMessages)
     */
    public function toLineMessages(array $vars = []): array
    {
        $structured = $this->getStructuredMessage();
        $items = Arr::get($structured, 'messages', []);

        $results = [];

        foreach ($items as $item) {
            $kind = Arr::get($item, 'kind', 'text');

            switch ($kind) {
                case 'text':
                    $text = (string) Arr::get($item, 'text', '');
                    if ($vars) {
                        $text = $this->applyPlaceholders($text, $vars);
                    }

                    if ($text === '') {
                        continue 2;
                    }

                    $results[] = [
                        'type' => 'text',
                        'text' => $text,
                    ];
                    break;

                case 'image':
                    $original = Arr::get($item, 'original') ?: Arr::get($item, 'url');
                    $preview = Arr::get($item, 'preview') ?: $original;

                    if (! $original) {
                        continue 2;
                    }

                    $results[] = [
                        'type' => 'image',
                        'originalContentUrl' => $original,
                        'previewImageUrl' => $preview,
                    ];
                    break;

                    // case 'sticker':
                    // case 'template':
                    // case 'flex':
                    //   ไว้ต่อยอดทีหลัง
            }
        }

        return $results;
    }

    /**
     * แทนที่ placeholder รูปแบบ {key} ด้วยค่าจาก $vars
     */
    protected function applyPlaceholders(string $text, array $vars): string
    {
        return preg_replace_callback('/\{(\w+)\}/u', function ($m) use ($vars) {
            $key = $m[1];

            return array_key_exists($key, $vars)
                ? (string) $vars[$key]
                : $m[0]; // ถ้าไม่มีค่าให้คงรูป {key} ไว้
        }, $text);
    }
}
