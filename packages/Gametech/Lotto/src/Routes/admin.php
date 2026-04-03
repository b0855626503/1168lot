<?php

use Illuminate\Support\Facades\Route;

Route::domain(
    config('app.admin_url').'.'.(
        is_null(config('app.admin_domain_url'))
            ? config('app.domain_url')
            : config('app.admin_domain_url')
    )
)->group(function () {
    Route::group(['middleware' => ['web', 'admin', 'auth', '2fa']], function () {
        Route::prefix('lotto')->group(function () {
            Route::get('/', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\SectionController@redirectToDefault')
                ->name('admin.lotto.index');

            Route::get('switches', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoSwitchController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.switches.index',
            ])->name('admin.lotto.switches.index');

            Route::get('groups', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LotteryGroupController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.groups.index',
            ])->name('admin.lotto.groups.index');

            Route::post('groups/edit', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LotteryGroupController@edit')
                ->name('admin.lotto.groups.edit');

            Route::post('groups/create', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LotteryGroupController@create')
                ->name('admin.lotto.groups.create');

            Route::post('groups/loaddata', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LotteryGroupController@loadData')
                ->name('admin.lotto.groups.loaddata');

            Route::post('groups/update', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LotteryGroupController@update')
                ->name('admin.lotto.groups.update');

            Route::get('markets', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LotteryMarketController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.markets.index',
            ])->name('admin.lotto.markets.index');

            Route::post('markets/create', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LotteryMarketController@create')
                ->name('admin.lotto.markets.create');

            Route::post('markets/loaddata', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LotteryMarketController@loadData')
                ->name('admin.lotto.markets.loaddata');

            Route::post('markets/edit', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LotteryMarketController@edit')
                ->name('admin.lotto.markets.edit');

            Route::post('markets/update', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LotteryMarketController@update')
                ->name('admin.lotto.markets.update');

            Route::get('group-packages', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoGroupPackageDashboardController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.group_packages.index',
                'title' => 'แพ็กเกจกลุ่มหวย',
                'description' => 'จัดการ package ระดับ group',
                'section' => 'settings.group_packages',
            ])->name('admin.lotto.group_packages.index');

            Route::post('group-packages/list', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoGroupPackageController@list')
                ->name('admin.lotto.group_packages.list');

            Route::post('group-packages/create', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoGroupPackageController@create')
                ->name('admin.lotto.group_packages.create');

            Route::post('group-packages/edit', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoGroupPackageController@edit')
                ->name('admin.lotto.group_packages.edit');

            Route::post('group-packages/update', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoGroupPackageController@update')
                ->name('admin.lotto.group_packages.update');

            Route::post('group-packages/delete', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoGroupPackageController@delete')
                ->name('admin.lotto.group_packages.delete');

            Route::post('group-package-bet-settings/list', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoGroupPackageBetSettingController@list')
                ->name('admin.lotto.group_package_bet_settings.list');

            Route::post('group-package-bet-settings/create', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoGroupPackageBetSettingController@create')
                ->name('admin.lotto.group_package_bet_settings.create');

            Route::post('group-package-bet-settings/edit', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoGroupPackageBetSettingController@edit')
                ->name('admin.lotto.group_package_bet_settings.edit');

            Route::post('group-package-bet-settings/update', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoGroupPackageBetSettingController@update')
                ->name('admin.lotto.group_package_bet_settings.update');

            Route::post('group-package-bet-settings/delete', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoGroupPackageBetSettingController@delete')
                ->name('admin.lotto.group_package_bet_settings.delete');

            Route::get('default-settings', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoMarketBetSettingController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.default_settings.index',
            ])->name('admin.lotto.default_settings.index');

            Route::get('rate-plans', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoRatePlanController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.rate_plans.index',
            ])->name('admin.lotto.rate_plans.index');

            Route::post('rate-plans/load-market', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoRatePlanController@loadMarket')
                ->name('admin.lotto.rate_plans.load_market');

            Route::post('rate-plans/update-market', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoRatePlanController@updateMarket')
                ->name('admin.lotto.rate_plans.update_market');

            Route::post('rate-plans/copy-from-reference', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoRatePlanController@copyFromReference')
                ->name('admin.lotto.rate_plans.copy_from_reference');

            Route::get('bet-limits', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoBetLimitController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.bet_limits.index',
            ])->name('admin.lotto.bet_limits.index');

            Route::post('bet-limits/load-market', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoBetLimitController@loadMarket')
                ->name('admin.lotto.bet_limits.load_market');

            Route::post('bet-limits/update-market', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoBetLimitController@updateMarket')
                ->name('admin.lotto.bet_limits.update_market');

            Route::post('bet-limits/copy-from-template', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoBetLimitController@copyFromTemplate')
                ->name('admin.lotto.bet_limits.copy_from_template');

            Route::post('default-settings/create', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoMarketBetSettingController@create')
                ->name('admin.lotto.default_settings.create');

            Route::post('default-settings/loaddata', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoMarketBetSettingController@loadData')
                ->name('admin.lotto.default_settings.loaddata');

            Route::post('default-settings/edit', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoMarketBetSettingController@edit')
                ->name('admin.lotto.default_settings.edit');

            Route::post('default-settings/update', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoMarketBetSettingController@update')
                ->name('admin.lotto.default_settings.update');

            Route::get('member-permissions', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\MemberLottoPermissionController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.member_permissions.index',
            ])->name('admin.lotto.member_permissions.index');

            Route::post('member-permissions/create', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\MemberLottoPermissionController@create')
                ->name('admin.lotto.member_permissions.create');

            Route::post('member-permissions/loaddata', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\MemberLottoPermissionController@loadData')
                ->name('admin.lotto.member_permissions.loaddata');

            Route::post('member-permissions/edit', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\MemberLottoPermissionController@edit')
                ->name('admin.lotto.member_permissions.edit');

            Route::post('member-permissions/update', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\MemberLottoPermissionController@update')
                ->name('admin.lotto.member_permissions.update');

            Route::get('draws', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.draws.index',
            ])->name('admin.lotto.draws.index');

            Route::get('auto-result-sources', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoResultSourceController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.result_sources.index',
            ])->name('admin.lotto.result_sources.index');

            Route::get('auto-result-sources/list', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoResultSourceController@list')
                ->name('admin.lotto.result_sources.list');

            Route::post('auto-result-sources/loaddata', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoResultSourceController@loadData')
                ->name('admin.lotto.result_sources.loaddata');

            Route::post('auto-result-sources/create', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoResultSourceController@create')
                ->name('admin.lotto.result_sources.create');

            Route::post('auto-result-sources/edit', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoResultSourceController@edit')
                ->name('admin.lotto.result_sources.edit');

            Route::post('auto-result-sources/delete', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoResultSourceController@delete')
                ->name('admin.lotto.result_sources.delete');

            Route::post('auto-result-sources/update', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoResultSourceController@update')
                ->name('admin.lotto.result_sources.update');

            Route::post('auto-result-sources/preview-config', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoResultSourceController@previewConfig')
                ->name('admin.lotto.result_sources.preview_config');

            Route::post('auto-result-sources/validate-config', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoResultSourceController@validateConfig')
                ->name('admin.lotto.result_sources.validate_config');

            Route::post('auto-result-sources/validate-cutover', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoResultSourceController@validateCutover')
                ->name('admin.lotto.result_sources.validate_cutover');

            Route::post('auto-result-sources/test-fetch-by-date', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoResultSourceController@testFetchByDate')
                ->name('admin.lotto.result_sources.test_fetch_by_date');

            Route::get('auto-result-sources/test-fetch-logs-by-date', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoResultSourceController@testFetchLogsByDate')
                ->name('admin.lotto.result_sources.test_fetch_logs_by_date');

            Route::post('auto-result-sources/browser-test-dispatch', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoResultSourceController@browserTestDispatch')
                ->name('admin.lotto.result_sources.browser_test_dispatch');

            Route::get('auto-result-sources/browser-test-status', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoResultSourceController@browserTestStatus')
                ->name('admin.lotto.result_sources.browser_test_status');

            Route::post('draws/create', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@create')
                ->name('admin.lotto.draws.create');

            Route::post('draws/loaddata', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@loadData')
                ->name('admin.lotto.draws.loaddata');

            Route::post('draws/blocked-numbers', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@loadBlockedNumbers')
                ->name('admin.lotto.draws.blocked_numbers');

            Route::post('draws/tickets-summary', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@loadTicketsSummary')
                ->name('admin.lotto.draws.tickets_summary');

            Route::post('draws/edit', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@edit')
                ->name('admin.lotto.draws.edit');

            Route::post('draws/update', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@update')
                ->name('admin.lotto.draws.update');

            Route::post('draws/open', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@open')
                ->name('admin.lotto.draws.open');

            Route::post('draws/close', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@close')
                ->name('admin.lotto.draws.close');

            Route::post('draws/settle', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@settle')
                ->name('admin.lotto.draws.settle');
            Route::post('draws/mark-no-result', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@markNoResult')
                ->name('admin.lotto.draws.mark_no_result');
            Route::post('draws/cancel-all-refund', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@cancelAllRefund')
                ->name('admin.lotto.draws.cancel_all_refund');

            Route::post('draws/generate-auto', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@generateAuto')
                ->name('admin.lotto.draws.generate_auto');

            Route::get('draws/auto-result-metrics', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@autoResultMetrics')
                ->name('admin.lotto.draws.auto_result_metrics');

            Route::post('draws/auto-result-test-fetch', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@autoResultTestFetch')
                ->name('admin.lotto.draws.auto_result_test_fetch');

            Route::post('draws/auto-result-manual-retry', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@autoResultManualRetry')
                ->name('admin.lotto.draws.auto_result_manual_retry');

            Route::get('draws/auto-result-logs', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@autoResultLogs')
                ->name('admin.lotto.draws.auto_result_logs');

            Route::get('number-blocks', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoNumberBlockController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.number_blocks.index',
            ])->name('admin.lotto.number_blocks.index');

            Route::post('number-blocks/create', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoNumberBlockController@create')
                ->name('admin.lotto.number_blocks.create');

            Route::post('number-blocks/loaddata', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoNumberBlockController@loadData')
                ->name('admin.lotto.number_blocks.loaddata');

            Route::post('number-blocks/edit', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoNumberBlockController@edit')
                ->name('admin.lotto.number_blocks.edit');

            Route::post('number-blocks/delete', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoNumberBlockController@delete')
                ->name('admin.lotto.number_blocks.delete');

            Route::post('number-blocks/bulk-delete', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoNumberBlockController@bulkDelete')
                ->name('admin.lotto.number_blocks.bulk_delete');

            Route::post('number-blocks/update', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoNumberBlockController@update')
                ->name('admin.lotto.number_blocks.update');

            Route::get('tickets', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoTicketController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.tickets.index',
            ])->name('admin.lotto.tickets.index');

            Route::post('tickets/loaddata', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoTicketController@loadData')
                ->name('admin.lotto.tickets.loaddata');

            Route::get('reports/exposure', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoExposureReportController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.exposure_report.index',
            ])->name('admin.lotto.reports.exposure');

            Route::get('reports/revenue', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoRevenueReportController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.revenue_report.index',
            ])->name('admin.lotto.reports.revenue');

            Route::get('settings/bet-types', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\SectionController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.section',
                'title' => 'ประเภทหวย',
                'description' => 'Mockup: หน้าจัดการประเภทหวย',
                'section' => 'settings.bet_types',
            ])->name('admin.lotto.settings.bet_types');

            Route::get('reports/pending-bets', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\SectionController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.reports.mockup',
                'title' => 'รอผลเดิมพัน',
                'description' => 'Mockup: รายงานรายการเดิมพันที่รอผล',
                'section' => 'reports.pending_bets',
                'filters' => ['วันที่เริ่ม', 'วันที่สิ้นสุด', 'ตลาด', 'สถานะโพย'],
                'columns' => ['เวลา', 'สมาชิก', 'ตลาด', 'ประเภท', 'เลข', 'ยอดแทง', 'สถานะ'],
            ])->name('admin.lotto.reports.pending_bets');

            Route::get('reports/profit-loss-forecast', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\SectionController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.reports.mockup',
                'title' => 'ดูของรวม/คาดคะเน ได้-เสีย',
                'description' => 'Mockup: รายงานภาพรวมและคาดคะเนผลได้-เสีย',
                'section' => 'reports.profit_loss_forecast',
                'filters' => ['วันงวด', 'ตลาด', 'ประเภท'],
                'columns' => ['ตลาด', 'ยอดแทงรวม', 'ความเสี่ยงจ่าย', 'คาดการณ์ได้/เสีย'],
            ])->name('admin.lotto.reports.profit_loss_forecast');

            Route::get('reports/member-bet-types', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\SectionController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.reports.mockup',
                'title' => 'ดูของสมาชิก/ประเภท',
                'description' => 'Mockup: รายงานตามสมาชิกและประเภทหวย',
                'section' => 'reports.member_bet_types',
                'filters' => ['สมาชิก', 'วันที่เริ่ม', 'วันที่สิ้นสุด', 'ตลาด', 'ประเภท'],
                'columns' => ['สมาชิก', 'ตลาด', 'ประเภท', 'จำนวนโพย', 'ยอดแทง', 'ได้/เสีย'],
            ])->name('admin.lotto.reports.member_bet_types');

            Route::get('reports/tickets-cancel', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\SectionController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.reports.mockup',
                'title' => 'รายการโพย/ยกเลิกโพย',
                'description' => 'Mockup: รายงานรายการโพยและรายการยกเลิกโพย',
                'section' => 'reports.tickets_cancel',
                'filters' => ['วันที่เริ่ม', 'วันที่สิ้นสุด', 'ตลาด', 'สถานะโพย'],
                'columns' => ['เวลา', 'เลขโพย', 'สมาชิก', 'ยอดแทง', 'สถานะ', 'ผู้ยกเลิก'],
            ])->name('admin.lotto.reports.tickets_cancel');

            Route::get('reports/blocked-numbers', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\SectionController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.reports.mockup',
                'title' => 'เลขปิดรับ/เลขอั้น',
                'description' => 'Mockup: รายงานเลขปิดรับและเลขอั้น',
                'section' => 'reports.blocked_numbers',
                'filters' => ['วันงวด', 'ตลาด', 'ประเภท', 'โหมดบล็อก'],
                'columns' => ['ตลาด', 'ประเภท', 'เลข', 'โหมด', 'เวลาเริ่ม', 'เวลาแก้ไขล่าสุด'],
            ])->name('admin.lotto.reports.blocked_numbers');

            Route::get('reports/results-by-date', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoResultsByDateReportController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.reports.results_by_date',
            ])->name('admin.lotto.reports.results_by_date');
        });
    });
});
