<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

if (config('app.user_url') === '') {
    $domain = (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
} else {
    $domain = config('app.user_url').'.'.(is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
}
Route::domain($domain)->group(function () {
    Route::middleware('web')->group(function () {

        // Route พื้นฐานของระบบ
        require __DIR__.'/route_basic.php';

        Route::prefix('member')->group(function () {

            Route::middleware(['customer', 'online'])->group(function () {
                // ไว้สำหรับ future route ถ้าจะเพิ่ม

                // Route หลักของสมาชิก
                require __DIR__.'/route_member.php';

                // ✅ โหลด route ทั้งหมดจากโฟลเดอร์ย่อย
                $routePath = base_path('packages/Gametech/Wallet/src/Http/Routes/member');
                $routeFiles = File::allFiles($routePath);

                foreach ($routeFiles as $file) {
                    if (
                        $file->getExtension() === 'php' &&
                        $file->getFilename() !== 'route_member.php' &&
                        ! in_array(realpath($file->getPathname()), get_included_files())
                    ) {
                        try {
                            require $file->getPathname();
                        } catch (\Throwable $e) {
                            Log::error('Error loading route file', [
                                'file' => $file->getPathname(),
                                'message' => $e->getMessage(),
                            ]);
                        }
                    }
                }

            });
        });
    });
});

// ✅ เพิ่ม route ภายนอก หากมี
//$addonRoute = __DIR__.'/routes_addon.php';
//if (file_exists($addonRoute)) {
//    require $addonRoute;
//}
