<?php

Route::group(['middleware' => ['web', 'theme', 'locale', 'currency']], function () {

    Route::get('/member', 'Gametech\Member\Http\Controllers\Shop\MemberController@index')->defaults('_config', [
        'view' => 'member::shop.index',
    ])->name('member.shop.index');

});