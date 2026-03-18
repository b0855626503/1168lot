<?php


use Illuminate\Support\Facades\Route;

$domain = config('app.user_url') === ''
    ? (config('app.user_domain_url') ?? config('app.domain_url'))
    : config('app.user_url') . '.' . (config('app.user_domain_url') ?? config('app.domain_url'));

Route::domain($domain)->group(function () {
    Route::middleware('web')->group(function () {

        Route::prefix('member')->group(function () {

            Route::middleware(['customer', 'authuser', 'online'])->group(function () {
                // ไว้สำหรับ future route ถ้าจะเพิ่ม

                Route::post('reward/list', 'Gametech\Reward\Http\Controllers\Wallet\RewardListController@rewardList')->name('customer.reward.list');
                Route::post('reward/redeem', 'Gametech\Reward\Http\Controllers\Wallet\RewardListController@redeem')->name('customer.reward.redeem');
                Route::post('reward/history', 'Gametech\Reward\Http\Controllers\Wallet\RewardListController@rewardHistory')->name('customer.reward.history');

            });
        });
    });
});
