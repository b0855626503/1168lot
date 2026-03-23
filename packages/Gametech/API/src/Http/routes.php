<?php

Route::domain('api.' . (is_null(config('app.admin_domain_url')) ? config('app.domain_url') : config('app.admin_domain_url')))->group(function () {

    Route::prefix('api')->group(function () {

        Route::group(['namespace' => 'Gametech\API\Http\Controllers', 'middleware' => ['api','whitelist']], function () {

            include 'routessub.php';

        });

    });

    Route::prefix('api')->group(function () {

        Route::group(['namespace' => 'Gametech\API\Http\Controllers', 'middleware' => ['api']], function () {
            Route::get('/games/{type}/{provider}', 'GameController@getGames')->name('api.games.get');
            Route::get('/list/provider/{type}', 'GameController@getProviders')->name('api.providers.get');

//            include 'routeshotdog.php';

        });

    });


});