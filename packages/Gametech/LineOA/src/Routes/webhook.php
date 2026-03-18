<?php

use Gametech\LineOA\Http\Controllers\LineWebhookController;
use Illuminate\Support\Facades\Route;

$apiRoute = config('gametech.api_url') ?? 'api';

// api.xxx.com
Route::domain(
    $apiRoute.'.'.(
        is_null(config('app.admin_domain_url'))
            ? config('app.domain_url')
            : config('app.admin_domain_url')
    )
)->group(function () {

    Route::prefix('api')
        ->middleware(['api'])
        ->as('api.') // 👈 ชื่อ route ทั้งกลุ่มขึ้นต้น api.
        ->group(function () {

            // https://api.xxx.com/api/line-oa/webhook/{token}
            Route::prefix('line-oa')
                ->as('line-oa.')
                ->group(function () {

                    Route::post('webhook/{token}', [LineWebhookController::class, 'handle'])
                        ->name('webhook');
                    // => ชื่อเต็ม: api.line-oa.webhook

                });

        });

});
