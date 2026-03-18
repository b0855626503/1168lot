<?php

use Illuminate\Support\Facades\Route;


$domains = explode(',', env('user_domain_addon_url'));

foreach ($domains as $domain) {
    Route::domain($domain)->group(function () {

        Route::group(['middleware' => ['web']], function () {

            require __DIR__.'/route_basic.php';

            Route::prefix('member')->group(function () {

                Route::group(['middleware' => ['customer', 'authuser', 'online']], function () {});


                require __DIR__.'/route_member.php';

                $routeFiles = File::allFiles(base_path('packages/Gametech/Wallet/src/Http/Routes/member'));

                foreach ($routeFiles as $file) {
                    if ($file->getExtension() === 'php' && $file->getFilename() !== 'route_member.php') {
                        try {
                            require $file->getPathname();
                        } catch (\Throwable $e) {
                            Log::error('Error loading route: '.$file->getFilename(), ['error' => $e->getMessage()]);
                        }
                    }
                }

            });
        });
    });
}
