<?php

use Gametech\CenterOA\Http\Controllers\Admin\ImgController;
use Gametech\CenterOA\Http\Controllers\Admin\MemberGameLogController;
use Illuminate\Support\Facades\Route;

// admin.xxx.com
Route::domain(
    config('app.admin_url').'.'.(
        is_null(config('app.admin_domain_url'))
            ? config('app.domain_url')
            : config('app.admin_domain_url')
    )
)->group(function () {

    Route::group(['middleware' => ['web', 'admin', 'auth', '2fa']], function () {

        Route::post('pic/upload', [ImgController::class, 'upload'])->defaults('_config', [
            'view' => 'wallet::customer.game.redirect',
        ])->name('admin.upload.pic');

        Route::post('pic/delete/{id}', [ImgController::class, 'delete'])->defaults('_config', [
            'view' => 'wallet::customer.game.redirect',
        ])->name('admin.delete.pic');

        Route::get('gamelog/log', [MemberGameLogController::class, 'gamelog'])->defaults('_config', [
            'view' => 'wallet::customer.game.redirect',
        ])->name('admin.gamelog.log');
    });


});
