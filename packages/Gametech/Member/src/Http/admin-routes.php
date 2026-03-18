<?php

Route::group(['middleware' => ['web', 'admin']], function () {

    Route::get('/admin/member', 'Gametech\Member\Http\Controllers\Admin\MemberController@index')->defaults('_config', [
        'view' => 'member::admin.index',
    ])->name('member.admin.index');

});