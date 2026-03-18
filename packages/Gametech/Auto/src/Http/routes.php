<?php

Route::group(['middleware' => ['web'] , 'namespace' => 'Gametech\Auto\Http\Controllers'], function () {

    Route::prefix('auto')->group(function () {
        Route::get('check/{id?}', 'JobController@checkPayment');
        Route::get('topup', 'JobController@topup');
        Route::get('cashback', 'JobController@memberCashback');
    });

    Route::prefix('payment')->group(function () {
        Route::get('get/{id?}', 'JobController@getBank');
        Route::get('get-account/{id}/{account}', 'JobController@getAccount');

    });


});
