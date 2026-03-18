<?php

namespace Gametech\Admin\Providers;

use Codedge\Updater\Traits\UseVersionFile;
use Codedge\Updater\UpdaterFacade;
use Gametech\Admin\Bouncer;
use Gametech\Admin\Facades\Bouncer as BouncerFacade;
use Gametech\Admin\Models\AdminProxy;
use Gametech\Admin\Models\RoleProxy;
use Gametech\Admin\Observers\AdminObserver;
use Gametech\Admin\Observers\RoleObserver;
use Gametech\Core\Tree;
use Gametech\Core\Core;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AdminServiceProvider extends ServiceProvider
{
    use UseVersionFile;

    /**
     * Bootstrap services.
     */
    public function boot(Router $router)
    {
        // ถ้า helpers ถูก autoload ผ่าน composer แล้ว ไม่ต้อง include ตรงนี้
        // include __DIR__ . '/../Http/helpers.php';

        AdminProxy::observe(AdminObserver::class);
        RoleProxy::observe(RoleObserver::class);

        // โหมดจาก Core (มี 1 record)
        $config = app()->make(Core::class)->getConfigData();
        if ($config->seamless == 'Y') {
            $this->registerConfigSeamless();
        } else {
            if ($config->multigame_open == 'Y') {
                $this->registerConfig();
            } else {
                $this->registerConfigSingle();
            }
        }

        // โหลด routes ใน boot
        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');

        // โหลด views
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'admin');

        // ผูก view composers (กัน CLI)
        if (! $this->app->runningInConsole()) {
            $this->composeView();
        }

        // $router->aliasMiddleware('admin', BouncerMiddleware::class);
    }

    /**
     * Register services.
     */
    public function register()
    {
        // assets publish
//        $this->publishes([
//            __DIR__ . '/../../publishable/assets' => public_path('assets/admin'),
//        ], 'public');

        // ACL + Bouncer
        $this->registerACL();
        $this->registerBouncer();
    }

    /**
     * Register package config (Multi-game).
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/Config/admin-menu.php', 'menu.admin');
        $this->mergeConfigFrom(dirname(__DIR__) . '/Config/acl.php',        'acl');
    }

    /**
     * Register package config (Single).
     */
    protected function registerConfigSingle()
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/Config/admin-menu-single.php', 'menu.admin');
        $this->mergeConfigFrom(dirname(__DIR__) . '/Config/acl-single.php',        'acl');
    }

    /**
     * Register package config (Seamless).
     */
    protected function registerConfigSeamless()
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/Config/admin-menu-seamless.php', 'menu.admin');
        $this->mergeConfigFrom(dirname(__DIR__) . '/Config/acl-seamless.php',        'acl');
    }

    /**
     * View composers — คอมโพสเมนูครั้งเดียวบน layout แล้ว share ให้ทุกวิว
     */
    protected function composeView()
    {
        $menuComposer = function ($view) {
            static $menuTree = null; // cache ต่อรีเควสต์
            static $aclKeys  = null; // cache ต่อรีเควสต์

            if ($menuTree) {
                $view->with('menu', $menuTree);
                return;
            }

            $tree = Tree::create();

            // ยังไม่ล็อกอิน → ส่งเมนูว่าง (กัน dereference null)
            $user = Auth::guard('admin')->user();
            if (! $user) {
                $view->with('menu', $tree);
                return;
            }

            $permissionType     = $user->role->permission_type ?? 'all';
            $allowedPermissions = (array) ($user->role->permissions ?? []);

            // ดึง key ใน ACL แบบปลอดภัย (สตริงเท่านั้น)
            if ($aclKeys === null) {
                $aclKeys = collect((array) config('acl', []))
                    ->pluck('key')
                    ->filter(fn($v) => is_string($v) && $v !== '')
                    ->unique()
                    ->values()
                    ->all();
            }

            $menu = (array) config('menu.admin');

//            dump($menu);

            foreach ($menu as $index => $item) {
                if (!bouncer()->hasPermission($item['key'])) {
                    continue;
                }

                if ($index + 1 < count(config('menu.admin')) && $permissionType != 'all') {
                    $permission = config('menu.admin')[$index + 1];

                    if (substr_count($permission['key'], '.') == 2 && substr_count($item['key'], '.') == 1) {
                        foreach ($allowedPermissions as $key => $value) {
                            if ($item['key'] == $value) {
                                $neededItem = $allowedPermissions[$key + 1];

                                foreach (config('menu.admin') as $key1 => $findMatced) {
                                    if ($findMatced['key'] == $neededItem) {
                                        $item['route'] = $findMatced['route'];
                                    }
                                }
                            }
                        }
                    }
                }

                $tree->add($item, 'menu');
            }

            $tree->items = core()->sortItems($tree->items);

            $view->with('menu', $tree);

//            foreach ($menu as $index => $item) {
//                $key = $item['key'] ?? null;
//
//                // เช็คสิทธิ์เฉพาะเมนูที่ "มีใน ACL"
//                if ($key && in_array($key, $aclKeys, true)) {
//                    if (! bouncer()->hasPermission($key)) {
//                        continue;
//                    }
//                }
//                // ถ้า key ไม่มีใน ACL → แสดงได้ทุกคน
//
//                // เติม route ให้หัวข้อ ตามลอจิกเดิม
//                if ($index + 1 < count($menu) && $permissionType !== 'all') {
//                    $permission = $menu[$index + 1] ?? null;
//
//                    if ($permission
//                        && substr_count($permission['key'] ?? '', '.') == 2
//                        && substr_count($item['key'] ?? '', '.') == 1) {
//
//                        foreach ($allowedPermissions as $k => $v) {
//                            if (($item['key'] ?? null) === $v) {
//                                $needed = $allowedPermissions[$k + 1] ?? null;
//
//                                if ($needed) {
//                                    foreach ($menu as $candidate) {
//                                        if (($candidate['key'] ?? null) === $needed) {
//                                            $item['route'] = $candidate['route'] ?? ($item['route'] ?? null);
//                                            break;
//                                        }
//                                    }
//                                }
//                            }
//                        }
//                    }
//                }
//
//                // ถ้ามี route แต่ระบบไม่มี route นี้จริง → ข้าม
//                if (isset($item['route']) && ! Route::has($item['route'])) {
//                    continue;
//                }
//
//                $tree->add($item, 'menu');
//            }
//
//            // เรียงเมนูด้วย core()->sortItems ถ้ามี
//            try { $tree->items = core()->sortItems($tree->items); } catch (\Throwable $e) {}
//
//            // เซ็ต currentRoute ให้ blade เดิมใช้งาน
//            $routeName = Route::currentRouteName() ?? '';                    // e.g. admin.marketing_team.index
//            $group     = Str::before(Str::after($routeName, 'admin.'), '.'); // -> marketing_team
//            if ($group) {
//                $tree->currentRoute = $group;
//            }
//
//            $menuTree = $tree;
//            $view->with('menu', $menuTree);
        };

        // ✅ แชร์เมนูให้ทั้ง layout และ module
        view()->composer('admin::layouts.master', $menuComposer);
        view()->composer('admin::module.*',       $menuComposer);

        // ✅ ACL สำหรับ module views
        view()->composer(['admin::module.*'], function ($view) {
            $view->with('acl', $this->createACL());
        });

        // header: version
        view()->composer(['admin::layouts.header'], function ($view) {
            $this->deleteVersionFile();
            $newpatch = false;
            $current = config('self-update.version_installed');
            $view->with('version', $current)->with('patch', $newpatch);
        });
    }


    /**
     * Registers acl to entire application
     */
    public function registerACL()
    {
        $this->app->singleton('acl', function () {
            return $this->createACL();
        });
    }

    /**
     * Create acl tree
     */
    public function createACL()
    {
        static $tree;

        if ($tree) {
            return $tree;
        }

        $tree = Tree::create();

        foreach ((array) config('acl') as $item) {
            $tree->add($item, 'acl');
        }

        $tree->items = core()->sortItems($tree->items);

        return $tree;
    }

    /**
     * Register Bouncer as a singleton.
     */
    protected function registerBouncer()
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('bouncer', BouncerFacade::class);

        $this->app->singleton('bouncer', function () {
            return app()->make(Bouncer::class);
        });
    }
}
