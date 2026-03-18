<?php

Route::group(['middleware' => ['web', 'theme', 'locale', 'currency']], function () {

    Route::get('/promotion', 'Gametech\Promotion\Http\Controllers\Shop\PromotionController@index')->defaults('_config', [
        'view' => 'promotion::shop.index',
    ])->name('promotion.shop.index');

});