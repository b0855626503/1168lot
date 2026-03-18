<?php

use Illuminate\Support\Facades\Route;

Route::domain(config('app.admin_url').'.'.(is_null(config('app.admin_domain_url')) ? config('app.domain_url') : config('app.admin_domain_url')))->group(function () {

    Route::group(['middleware' => ['web', 'admin', 'auth', '2fa'], 'namespace' => 'Gametech\Marketing\Http\Controllers\Admin'], function () {

        $route = ['name' => 'marketing_team', 'controller' => 'MarketingTeamController'];
        Route::group(['prefix' => $route['name']], function () use ($route) {
            Route::get('/', $route['controller'].'@index')->defaults('_config', [
                'view' => 'admin::module.'.$route['name'].'.index',
            ])->name('admin.'.$route['name'].'.index');

            Route::post('create', $route['controller'].'@create')->name('admin.'.$route['name'].'.create');

            Route::post('loaddata', $route['controller'].'@loadData')->name('admin.'.$route['name'].'.loaddata');

            Route::post('loadBank', $route['controller'].'@loadBank')->name('admin.'.$route['name'].'.loadBank');

            Route::post('edit', $route['controller'].'@edit')->name('admin.'.$route['name'].'.edit');

            Route::post('update/{id?}', $route['controller'].'@update')->name('admin.'.$route['name'].'.update');

            Route::post('delete', $route['controller'].'@destroy')->name('admin.'.$route['name'].'.delete');
        });

        $route = ['name' => 'marketing_campaign', 'controller' => 'MarketingCampaignController'];
        Route::group(['prefix' => $route['name']], function () use ($route) {
            Route::get('/', $route['controller'].'@index')->defaults('_config', [
                'view' => 'admin::module.'.$route['name'].'.index',
            ])->name('admin.'.$route['name'].'.index');

            Route::get('/view/{id}', $route['controller'].'@store')->defaults('_config', [
                'view' => 'admin::module.'.$route['name'].'.view',
            ])->name('admin.'.$route['name'].'.view');

            Route::post('create', $route['controller'].'@create')->name('admin.'.$route['name'].'.create');

            Route::post('loaddata', $route['controller'].'@loadData')->name('admin.'.$route['name'].'.loaddata');

            Route::post('loadTeam', $route['controller'].'@loadTeam')->name('admin.'.$route['name'].'.loadTeam');

            Route::post('loadReport', $route['controller'].'@loadReport')->name('admin.'.$route['name'].'.loadReport');

            Route::post('edit', $route['controller'].'@edit')->name('admin.'.$route['name'].'.edit');

            Route::post('update/{id?}', $route['controller'].'@update')->name('admin.'.$route['name'].'.update');

            Route::post('delete', $route['controller'].'@destroy')->name('admin.'.$route['name'].'.delete');
        });

        Route::get('marketing_member', 'MarketingMemberController@index')->defaults('_config', [
            'view' => 'admin::module.marketing_member.index',
        ])->name('admin.marketing_member.index');

    });

});
