<?php

namespace App\Providers;

use Gametech\Core\Core as CoreService;  // resolve ตรง ๆ
use Gametech\Core\Tree;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/../game/game.php',     'game');
//        $this->mergeConfigFrom(dirname(__DIR__) . '/../game/gamefree.php', 'gamefree');
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        JsonResource::withoutWrapping();

        // บังคับ https เฉพาะ web context เท่านั้น (กันชน CLI และ API)
        if (! $this->app->runningInConsole() && ! $this->isApiContext()) {
            if (config('app.force_https', true)) {
                URL::forceScheme('https');
                $this->app['request']->server->set('HTTPS', true);
            }
        }

        if (app()->environment('local')) {
            DB::listen(function ($query) {
                Log::channel('slowlog')->info(
                    '['.$query->time.' ms] '.$query->sql,
                    $query->bindings
                );
            });
        }

        // === ถ้าเป็น API context: ตัดจบตรงนี้ ไม่ share/compose อะไรที่เป็นของ web ===
        if ($this->isApiContext()) {
            // อยาก share ค่านิดหน่อยให้ API ใช้ก็ทำได้ตรงนี้ (แต่เลี่ยงเรียก Core หนัก ๆ)
            return;
        }

        // ====== WEB CONTEXT เท่านั้น ======

        // แชร์ webconfig แบบปลอดภัย (อย่าทำให้เว็บล่มถ้า Core เด้ง)
        if ($core = $this->safeCore()) {
            try {
                View::share('webconfig', $core->getConfigData());

            } catch (\Throwable $e) {
                View::share('webconfig', null);
            }
        } else {
            View::share('webconfig', null);
        }
        $languages = config('languages.available');
        View::share('languages', $languages);

        // รอให้ service providers อื่น ๆ บูตเสร็จก่อนค่อยผูก view composers
        if (! $this->app->runningInConsole()) {
            $this->app->booted(function () {
                $core = $this->safeCore();
                $this->composeFrontViews();   // lazy-resolve core ภายใน
                $this->composeAdminViews();   // lazy-resolve core ภายใน
            });
        }
    }

    /**
     * ตรวจว่าเป็น API context ไหม
     * - โฮสต์ขึ้นต้นด้วย api.
     * - path เป็น api/*
     * - เส้นทางมี middleware ชื่อ 'api'
     */
    private function isApiContext(): bool
    {
        if ($this->app->runningInConsole()) {
            return false; // CLI = ไม่ถือเป็น API request
        }

        $req = $this->app['request'];

        // 1) subdomain api.*
//        $isApiSubdomain = Str::startsWith($req->getHost() ?? '', 'api.');

        // 2) path /api/*
        $isApiPath = $req->is('api/*');

        // 3) route middleware 'api'
        $isApiMiddleware = false;
        if (class_exists(Route::class)) {
            $route = Route::current();
            if ($route) {
                $middleware = collect($route->gatherMiddleware())->map(fn ($m) => strtolower((string) $m));
                $isApiMiddleware = $middleware->contains(fn ($m) => $m === 'api' || Str::contains($m, 'api'));
            }
        }

        return $isApiPath || $isApiMiddleware;
    }

    /**
     * ดึง Core แบบปลอดภัย (ไม่ใช้ Facade) — อาจได้ null ถ้า core ยังไม่พร้อม
     */
    private function safeCore(): ?CoreService
    {
        if (! $this->app->bound('core')) {
            return null;
        }
        try {
            /** @var CoreService $core */
            $core = $this->app->make('core');
            return $core;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Composer ฝั่งลูกค้า/วอลเล็ต – lazy-resolve core ภายใน closure
     */
    private function composeFrontViews(): void
    {
        view()->composer(
            ['wallet::layouts.*', 'wallet::customer.*', 'wallet::customer.credit.*', 'layouts.*', 'module.*'],
            function ($view) {
                $tree = Tree::create();

                // resolve core ทุกครั้งแบบปลอดภัย
                $core = $this->safeCore();

                if (! $core) {
                    $view->with([
                        'config'     => null,
                        'menu'       => $tree,
                        'notice'     => [],
                        'notice_new' => [],
                        'lang'       => Session::get('lang'),
                        'userdata'   => null,
                        'topupbanks' => 0,
                        'topuptws'   => 0,
                        'topuppayment'   => 0,
                        'topupslip'   => 0,
                        'contacts'   => [],
                        'menus'   => [],
                        'refill'     => '',
                        'single'     => null,
                    ]);
                    return;
                }

                // ลด I/O ในหนึ่งรอบ render
                static $bag;
                if (! $bag) {
                    try {
                        $config    = $core->getConfigData();
                        $contacts  = $core->getContact();
                        $notice    = $core->getNoticeData();
                        $noticeNew = $core->getNoticeNewData();
                        $userdata  = $core->getProfile();
//                        $banks     = $core->getBankTopupCounts();
                        $menus     = $core->getGameType();
                        $refill = '';
                        $single = null;

                        if (($config->seamless ?? 'N') === 'Y') {
                            $refill = $core->getRefill();
                        } elseif (($config->multigame_open ?? 'N') === 'N') {
                            $single = $core->getGame();
                        }

//                        $bankList = is_iterable($banks) ? collect($banks) : collect();
//                        $tw       = $banks['tw'];
//                        $bank     = $banks['bank'];
//                        $payment     = $banks['payment'];
//                        $slip     = $banks['slip'];

                        $bag = compact('config','contacts','notice','noticeNew','userdata','refill','single','menus');
                    } catch (\Throwable $e) {
                        $bag = [
                            'config'     => null,
                            'contacts'   => [],
                            'notice'     => [],
                            'noticeNew'  => [],
                            'userdata'   => null,
                            'menus'       => [],
                            'refill'     => '',
                            'single'     => null,
                        ];
                    }
                }
//                dd($bag['userdata']);

                $view->with('config',     $bag['config']);
                $view->with('menu',       $tree);
                $view->with('notice',     $bag['notice']);
                $view->with('notice_new', $bag['noticeNew']);
                $view->with('lang',       Session::get('lang'));
                $view->with('userdata',   $bag['userdata']);
//                $view->with('topupbanks', $bag['bank']);
//                $view->with('topuptws',   $bag['tw']);
//                $view->with('topuppayment',   $bag['payment']);
//                $view->with('topupslip',   $bag['slip']);
                $view->with('contacts',   $bag['contacts']);
                $view->with('refill',     $bag['refill']);
                $view->with('single',     $bag['single']);
                $view->with('menus',     $bag['menus']);
            }
        );
    }

    /**
     * Composer ฝั่งแอดมิน – เบา และกัน core ไม่พร้อม
     */
    private function composeAdminViews(): void
    {
        view()->composer(['admin::layouts.*', 'admin::module.*', 'admin::auth.login', 'admin::2fa.*'], function ($view) {
            $config = null;
            if ($core = $this->safeCore()) {
                try { $config = $core->getConfigData(); } catch (\Throwable $e) { $config = null; }
            }
            $view->with('config', $config);
        });
    }
}