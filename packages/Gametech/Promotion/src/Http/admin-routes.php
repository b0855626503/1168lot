<?php

Route::group(['middleware' => ['web', 'admin']], function () {

    Route::get('/admin/promotion', 'Gametech\Promotion\Http\Controllers\Admin\PromotionController@index')->defaults('_config', [
        'view' => 'promotion::admin.index',
    ])->name('promotion.admin.index');

});