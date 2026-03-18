<?php

use Gametech\Sms\Http\Controllers\Admin\SmsCampaignRecipientsController;
use Gametech\Sms\Http\Controllers\Admin\SmsImportController;
use Gametech\Sms\Http\Controllers\VonageDeliveryReceiptController;
use Gametech\Sms\Http\Controllers\Webhooks\SmsDeliveryReceiptWebhookController;
use Gametech\Sms\Http\Middleware\VerifySmsWebhookSignature;
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

            // https://api.xxx.com/api/sms/webhook/xxxx
            Route::prefix('sms')
                ->as('sms.')
                ->group(function () {

//                    Route::match(['GET', 'POST'], '/webhook/vonage/dlr', [VonageDeliveryReceiptController::class, 'handle'])
//                        ->middleware([VerifySmsWebhookSignature::class])
//                        ->name('vonage.dlr');

                    Route::match(['GET', 'POST'], '/webhook/{provider}/dlr', [SmsDeliveryReceiptWebhookController::class, 'dlr'])
                        ->middleware([VerifySmsWebhookSignature::class])
                        ->name('webhook.dlr');

                    Route::post('/imports/parse', [SmsImportController::class, 'parse'])
                        ->name('imports.parse');

                    Route::post('/campaigns/recipients/build', [SmsCampaignRecipientsController::class, 'build'])
                        ->name('recipients.build');

                });

        });

});
