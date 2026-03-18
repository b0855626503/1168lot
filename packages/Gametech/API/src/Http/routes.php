<?php

use Illuminate\Support\Facades\Route;
$apiRoute = config('gametech.api_url') ?? 'api';

Route::domain("$apiRoute." . (is_null(config('app.admin_domain_url')) ? config('app.domain_url') : config('app.admin_domain_url')))
    ->group(function () {

        Route::prefix('api')->group(function () {

            Route::group([
                'namespace' => 'Gametech\API\Http\Controllers',
                'middleware' => ['api', 'whitelist'],
            ], function () {

                // Catch-all สำหรับทุกเส้นทางที่ไม่แมป
                Route::any('{any}', function (\Illuminate\Http\Request $request) {

                    // ดึงค่าจากทุกชนิด payload (query/form/json) แบบปลอดภัย
                    $id = $request->input('id');
                    $productId = $request->input('productId');
                    $username = $request->input('username');

                    // กันคีย์หาย (เผื่อ request ไม่มีฟิลด์มา)
                    $resp = [
                        'id' => $id ?? null,
                        'statusCode' => 0,
                        'balance' => 0.0,
                        'productId' => $productId ?? null,
                        'currency' => 'THB',
                        'username' => $username ?? null,
                        'timestampMillis' => now()->getTimestampMs(),
                    ];

                    // ถ้าต้องการเก็บ raw request ทั้งก้อนเพื่อ debug:
                    // $resp['__raw'] = $request->all();

                    return response()->json($resp, 200);
                })->where('any', '.*');

                // include อื่น ๆ ถ้าต้องการ
                // include 'routessub.php';
            });

        });

        // ถ้าจะมีอีก prefix/api ชุดอื่น ค่อยเปิดส่วนนี้
        // Route::prefix('api')->group(function () {
        //     Route::group(['namespace' => 'Gametech\API\Http\Controllers', 'middleware' => ['api']], function () {
        //         include 'routeshotdog.php';
        //     });
        // });

    });
