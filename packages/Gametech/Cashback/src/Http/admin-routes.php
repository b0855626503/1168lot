<?php

Route::group(['middleware' => ['web', 'admin']], function () {

    Route::get('/admin/cashback', 'Gametech\Cashback\Http\Controllers\Admin\CashbackController@index')->defaults('_config', [
        'view' => 'cashback::admin.index',
    ])->name('cashback.admin.index');

});