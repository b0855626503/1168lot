<?php

Route::group(['middleware' => ['web', 'theme', 'locale', 'currency']], function () {

    Route::get('/cashback', 'Gametech\Cashback\Http\Controllers\Shop\CashbackController@index')->defaults('_config', [
        'view' => 'cashback::shop.index',
    ])->name('cashback.shop.index');

});