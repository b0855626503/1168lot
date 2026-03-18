<?php

use Gametech\Sms\Http\Controllers\Admin\SmsCampaignController;
use Gametech\Sms\Http\Controllers\Admin\SmsDeliveryReceiptController;
use Gametech\Sms\Http\Controllers\Admin\SmsImportController;
use Illuminate\Support\Facades\Route;

// admin.xxx.com
Route::domain(
    config('app.admin_url').'.'.(
        is_null(config('app.admin_domain_url'))
            ? config('app.domain_url')
            : config('app.admin_domain_url')
    )
)->group(function () {

    Route::group(['middleware' => ['web', 'admin', 'auth', '2fa']], function () {

        Route::prefix('sms_campaign')
            ->name('admin.sms_campaign.')
            ->group(function () {

                Route::get('/', [SmsCampaignController::class, 'index'])
                    ->defaults('_config', [
                        'view' => 'admin::module.sms_campaign.index',
                    ])
                    ->name('index');

                Route::post('create', [SmsCampaignController::class, 'create'])
                    ->name('create');

                Route::post('loaddata', [SmsCampaignController::class, 'loadData'])
                    ->name('loaddata');

                Route::post('edit', [SmsCampaignController::class, 'edit'])
                    ->name('edit');

                // คง route เดิมไว้ (รองรับ {id?} ตามของคุณ) แต่กัน input แปลก ๆ
                Route::post('update/{id?}', [SmsCampaignController::class, 'update'])
                    ->whereNumber('id')
                    ->name('update');

                Route::post('delete', [SmsCampaignController::class, 'destroy'])
                    ->name('delete');

                /**
                 * เพิ่ม: สร้าง recipients เข้า campaign
                 * request: id, mode(member_all|upload_only|mixed), import_batch_id(optional)
                 */
                Route::post('build-recipients', [SmsCampaignController::class, 'buildRecipients'])
                    ->name('build_recipients');

                /**
                 * เพิ่ม: dispatch queued recipients เข้า SendSmsJob
                 * request: id, limit(optional)
                 */
                Route::post('dispatch', [SmsCampaignController::class, 'dispatchQueued'])
                    ->name('dispatch');

                Route::post('stats', [SmsCampaignController::class, 'stats'])
                    ->name('stats');

            });

        Route::prefix('sms_import')
            ->name('admin.sms_import.')
            ->group(function () {

                /**
                 * Parse/Preview Import file (CSV/XLS/XLSX)
                 * request: file, campaign_id(optional), country_code(optional), has_header(optional), phone_column(optional)
                 * response: JSON { success, batch_id, preview, counters }
                 */
                Route::post('parse', [SmsImportController::class, 'parse'])
                    ->name('parse');

            });

        Route::prefix('sms_logs')
            ->name('admin.sms_logs.')
            ->group(function () {

                Route::get('/', [SmsDeliveryReceiptController::class, 'index'])
                    ->defaults('_config', [
                        'view' => 'admin::module.sms_logs.index',
                    ])
                    ->name('index');

            });

    });

});
