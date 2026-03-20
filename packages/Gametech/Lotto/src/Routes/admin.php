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

            Route::get('rate-plans', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoRatePlanController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.rate_plans.index',
            ])->name('admin.lotto.rate_plans.index');

            Route::post('rate-plans/create', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoRatePlanController@create')
                ->name('admin.lotto.rate_plans.create');

            Route::post('rate-plans/loaddata', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoRatePlanController@loadData')
                ->name('admin.lotto.rate_plans.loaddata');

            Route::post('rate-plans/edit', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoRatePlanController@edit')
                ->name('admin.lotto.rate_plans.edit');

            Route::post('rate-plans/update', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoRatePlanController@update')
                ->name('admin.lotto.rate_plans.update');

            Route::get('default-settings', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoMarketBetSettingController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.default_settings.index',
            ])->name('admin.lotto.default_settings.index');

            Route::post('default-settings/create', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoMarketBetSettingController@create')
                ->name('admin.lotto.default_settings.create');

            Route::post('default-settings/loaddata', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoMarketBetSettingController@loadData')
                ->name('admin.lotto.default_settings.loaddata');

            Route::post('default-settings/edit', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoMarketBetSettingController@edit')
                ->name('admin.lotto.default_settings.edit');

            Route::post('default-settings/update', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoMarketBetSettingController@update')
                ->name('admin.lotto.default_settings.update');

            Route::get('member-permissions', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\SectionController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.member_permissions.index',
                'title' => 'สิทธิ์การเล่น',
                'description' => 'กำหนดการมองเห็นและสิทธิ์เข้าเล่นหวยของสมาชิก',
                'section' => 'member_permissions',
            ])->name('admin.lotto.member_permissions.index');

            Route::get('member-rate-plans', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\MemberLottoSettingController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.member_rate_plans.index',
            ])->name('admin.lotto.member_rate_plans.index');

            Route::post('member-rate-plans/create', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\MemberLottoSettingController@create')
                ->name('admin.lotto.member_rate_plans.create');

            Route::post('member-rate-plans/loaddata', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\MemberLottoSettingController@loadData')
                ->name('admin.lotto.member_rate_plans.loaddata');

            Route::post('member-rate-plans/edit', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\MemberLottoSettingController@edit')
                ->name('admin.lotto.member_rate_plans.edit');

            Route::post('member-rate-plans/update', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\MemberLottoSettingController@update')
                ->name('admin.lotto.member_rate_plans.update');

            Route::get('draws', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.draws.index',
            ])->name('admin.lotto.draws.index');

            Route::post('draws/create', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@create')
                ->name('admin.lotto.draws.create');

            Route::post('draws/loaddata', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoDrawController@loadData')
                ->name('admin.lotto.draws.loaddata');

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

            Route::get('number-blocks', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoNumberBlockController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.number_blocks.index',
            ])->name('admin.lotto.number_blocks.index');

            Route::post('number-blocks/create', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoNumberBlockController@create')
                ->name('admin.lotto.number_blocks.create');

            Route::post('number-blocks/loaddata', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoNumberBlockController@loadData')
                ->name('admin.lotto.number_blocks.loaddata');

            Route::post('number-blocks/edit', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoNumberBlockController@edit')
                ->name('admin.lotto.number_blocks.edit');

            Route::post('number-blocks/update', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoNumberBlockController@update')
                ->name('admin.lotto.number_blocks.update');

            Route::get('tickets', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoTicketController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.tickets.index',
            ])->name('admin.lotto.tickets.index');

            Route::post('tickets/loaddata', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoTicketController@loadData')
                ->name('admin.lotto.tickets.loaddata');

            Route::get('reports/exposure', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\SectionController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.exposure_report.index',
                'title' => 'รายงาน Exposure',
                'description' => 'ดูยอดสะสมต่อเลขเพื่อประเมินความเสี่ยงของแต่ละ draw',
                'section' => 'exposure_report',
            ])->name('admin.lotto.reports.exposure');

            Route::get('reports/revenue', 'Gametech\\Lotto\\Http\\Controllers\\Admin\\LottoRevenueReportController@index')->defaults('_config', [
                'view' => 'admin::module.lotto.revenue_report.index',
            ])->name('admin.lotto.reports.revenue');
        });
    });
});

