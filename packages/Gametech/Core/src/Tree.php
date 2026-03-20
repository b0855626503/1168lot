<?php

namespace Gametech\Core;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

class Tree
{
    /**
     * เก็บรายการเมนูแบบ tree
     *
     * @var array
     */
    public $items = [];

    /**
     * เก็บสิทธิ์ (acl roles)
     *
     * @var array
     */
    public $roles = [];

    /**
     * URL/Path ปัจจุบัน (เก็บเป็น path เพื่อลด false-negative)
     *
     * @var string
     */
    public $current;

    /**
     * key ปัจจุบันของเมนู
     *
     * @var string
     */
    public $currentKey;

    /**
     * ชื่อปัจจุบันของเมนู
     *
     * @var string
     */
    public $currentName;

    /**
     * route ปัจจุบันของเมนู (อิงจาก key เดิม)
     *
     * @var string
     */
    public $currentRoute;

    /**
     * path ปัจจุบันอยู่ใต้ path ของ item นี้หรือไม่
     */
    protected function isPathMatch(?string $itemPath): bool
    {
        if (! is_string($itemPath) || $itemPath === '') {
            return false;
        }

        if ($this->current === $itemPath) {
            return true;
        }

        if ($itemPath === '/') {
            return $this->current === '/';
        }

        return str_starts_with($this->current, rtrim($itemPath, '/') . '/');
    }

    /**
     * แปลง url ของ item ให้เป็น path ที่ใช้เทียบได้จริง
     */
    protected function itemPath(array $item): ?string
    {
        $url = (string) ($item['url'] ?? '');

        if ($url === '' || $url === '#' || str_starts_with($url, 'javascript:')) {
            return null;
        }

        return parse_url($url, PHP_URL_PATH) ?: '/';
    }

    /**
     * current key อยู่ใต้ key ของ item นี้หรือไม่
     */
    protected function isCurrentKeyWithin(array $item): bool
    {
        $itemKey = (string) ($item['key'] ?? '');
        $currentKey = (string) ($this->currentKey ?? '');

        if ($itemKey === '' || $currentKey === '') {
            return false;
        }

        return $currentKey === $itemKey || str_starts_with($currentKey, $itemKey . '.');
    }

    /**
     * item นี้ หรือ item ลูกของมัน คือ route ปัจจุบันหรือไม่
     */
    protected function isCurrentItem(array $item, bool $includeDescendants = true): bool
    {
        if ($this->isCurrentKeyWithin($item) || $this->isPathMatch($this->itemPath($item))) {
            return true;
        }

        if (! $includeDescendants) {
            return false;
        }

        foreach ((array) ($item['children'] ?? []) as $child) {
            if (is_array($child) && $this->isCurrentItem($child, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        // เก็บเฉพาะ path เช่น /admin/members แทนที่จะเป็นทั้ง URL
        $this->current = parse_url(Request::fullUrl(), PHP_URL_PATH) ?: '/';
    }

    /**
     * สร้าง Tree แบบมี callback
     *
     * @param  callable|null  $callback
     * @return static
     */
    public static function create($callback = null)
    {
        $tree = new Tree;

        if ($callback) {
            $callback($tree);
        }

        return $tree;
    }

    /**
     * กรอง params ให้เป็นสเกลาร์/สตริง ป้องกันโยน object/array ใหญ่เข้า route()
     */
    protected function sanitizeParams(array $params): array
    {
        foreach ($params as $k => $v) {
            if (is_scalar($v) || $v === null) {
                continue;
            }
            $params[$k] = is_object($v) && method_exists($v, '__toString')
                ? (string) $v
                : (string) json_encode($v, JSON_UNESCAPED_UNICODE);
        }

        return $params;
    }

    /**
     * สร้าง href ของเมนูอย่างปลอดภัยและเบา
     * - ให้สิทธิ์ url ตรง ๆ มาก่อน
     * - ถ้ามี route ให้ใช้ route(..., false) เพื่อได้ relative URL
     * - ถ้า route ไม่มี ให้ '#'
     */
    protected function buildHref(array $item): string
    {
        if (!empty($item['url'])) {
            return (string) $item['url'];
        }

        if (!empty($item['route'])) {
            $name   = (string) $item['route'];
            $params = $this->sanitizeParams((array)($item['params'] ?? []));

            if (!Route::has($name)) {
                return '#';
            }

            // ใช้ relative URL (ลดงาน regex/การประกอบ host)
            return route($name, $params, false);
        }

        return '#';
    }

    /**
     * เพิ่ม item ลงใน tree
     *
     * @param  array   $item
     * @param  string  $type 'menu' หรือ 'acl'
     * @return void
     */
    public function add(array $item, $type = '')
    {
        $item['children'] = [];

        if ($type === 'menu') {
            // สร้าง URL เมนูแบบเบา และกัน route ไม่เจอ
            $item['url'] = $this->buildHref($item);
            if ($item['url'] === '#') {
                $item['route_not_found'] = true; // optional ใช้เป็น flag ให้ layer บน ๆ
            }

            // เทียบเฉพาะ path
            $itemPath    = $this->itemPath($item) ?: '/';

            if ($this->isPathMatch($itemPath)) {
                $this->currentKey   = $item['key'] ?? null;
                $this->currentName  = $item['name'] ?? null;
                $this->currentRoute = $item['key'] ?? null;
            }

            // ดึง segment กลางของ key เดิม มาเก็บเป็น currentRoute เหมือนพฤติกรรมเก่า
            $keys = explode('.', (string)$this->currentKey);
            if (isset($keys[1])) {
                $this->currentRoute = $keys[1];
            }

        } elseif ($type === 'acl') {
            // map route → key สำหรับตรวจสิทธิ์
            if (isset($item['route'], $item['key'])) {
                $this->roles[$item['route']] = $item['key'];
            }
        }

        // แปลง key เป็น path ของ children แล้ว set ลงโครงสร้าง
        $children = str_replace('.', '.children.', $item['key']);
        Arr::set($this->items, $children, $item);
    }

    /**
     * คืน class 'active' ถ้าเมนูนี้คืออันปัจจุบัน
     */
    public function getActive(array $item)
    {
        if ($this->isCurrentItem($item, true)) {
            return 'active';
        }

        return null;
    }

    /**
     * คืน class 'menu-open' (ใช้กับเมนูที่ต้องขยาย)
     */
    public function getActives(array $item)
    {
        if ($this->isCurrentItem($item, true) && count((array) ($item['children'] ?? [])) > 0) {
            return 'menu-open';
        }

        return null;
    }

    /**
     * ใช้ในฝั่งหน้าเว็บธรรมดา เทียบชื่อ route ล้วน ๆ
     */
    public function getFrontActive($route)
    {
        $name = Route::currentRouteName();

        if ($route === $name) {
            return 'active';
        }

        return null;
    }
}
