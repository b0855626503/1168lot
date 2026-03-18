<?php

Route::group(['middleware' => ['web', 'theme', 'locale', 'currency']], function () {

    Route::get('/ui', 'Gametech\Ui\Http\Controllers\Shop\UiController@index')->defaults('_config', [
        'view' => 'ui::shop.index',
    ])->name('ui.shop.index');

});