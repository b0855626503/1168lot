<?php

use Gametech\Marketing\Http\Controllers\Customer\MarketingController;
use Illuminate\Support\Facades\Route;

$domain = config('app.user_url') === ''
    ? (config('app.user_domain_url') ?? config('app.domain_url'))
    : config('app.user_url') . '.' . (config('app.user_domain_url') ?? config('app.domain_url'));

Route::domain($domain)->group(function () {
    Route::middleware('web')->group(function () {
        Route::get('register/{id?}/{refer?}', [MarketingController::class, 'store'])->name('customer.session.store');
//        Route::get('register/{id?}', [MarketingController::class, 'storeUser'])->name('customer.session.store');

        Route::post('register', [MarketingController::class, 'register'])->defaults('_config', [
            'redirect' => 'customer.home.index',
            'verify' => 'customer.verify.index',
        ])->name('customer.session.register');


//        Route::get('staff/{id?}/{refer?}', [MarketingController::class, 'staff'])->name('customer.session.staff');
//
//        Route::post('staff', [MarketingController::class, 'register_staff'])->defaults('_config', [
//            'redirect' => 'customer.session.staff',
//            'verify' => 'customer.verify.index',
//        ])->name('customer.session.register_staff');

//        Route::post('register', [MarketingController::class, 'registerUser'])->defaults('_config', [
//            'redirect' => 'customer.home.index',
//            'verify' => 'customer.verify.index',
//        ])->name('customer.session.register');

        Route::post('check-user', [MarketingController::class, 'checkUser'])->defaults('_config', [
            'redirect' => 'customer.home.index',
        ])->name('customer.check.user');

        Route::post('check-bank', [MarketingController::class, 'checkBank'])->defaults('_config', [
            'redirect' => 'customer.home.index',
        ])->name('customer.check.bank');

        Route::post('check-phone', [MarketingController::class, 'checkPhone'])->defaults('_config', [
            'redirect' => 'customer.home.index',
        ])->name('customer.check.phone');
    });
});

