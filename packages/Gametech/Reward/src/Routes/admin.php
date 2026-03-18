<?php

use Gametech\Reward\Http\Controllers\Admin\RewardListController;
use Gametech\Reward\Http\Controllers\Admin\RewardRedemptionController;
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

        Route::prefix('reward_list')->group(function () {
            Route::get('/', [RewardListController::class, 'index'])->defaults('_config', [
                'view' => 'admin::module.reward_list.index',
            ])->name('admin.reward_list.index');
            Route::post('create', [RewardListController::class, 'create'])->name('admin.reward_list.create');
            Route::post('loaddata', [RewardListController::class, 'loadData'])->name('admin.reward_list.loaddata');
            Route::post('edit', [RewardListController::class, 'edit'])->name('admin.reward_list.edit');
            Route::post('update/{id?}', [RewardListController::class, 'update'])->name('admin.reward_list.update');
            Route::post('delete', [RewardListController::class, 'destroy'])->name('admin.reward_list.delete');
        });

        Route::prefix('reward_redemption')->group(function () {
            Route::get('/', [RewardRedemptionController::class, 'index'])->defaults('_config', [
                'view' => 'admin::module.reward_redemption.index',
            ])->name('admin.reward_redemption.index');
            Route::post('create', [RewardRedemptionController::class, 'create'])->name('admin.reward_redemption.create');
            Route::post('loaddata', [RewardRedemptionController::class, 'loadData'])->name('admin.reward_redemption.loaddata');
            Route::post('edit', [RewardRedemptionController::class, 'edit'])->name('admin.reward_redemption.edit');
            Route::post('process', [RewardRedemptionController::class, 'process'])->name('admin.reward_redemption.process');
            Route::post('update/{id?}', [RewardRedemptionController::class, 'update'])->name('admin.reward_redemption.update');
            Route::post('delete', [RewardRedemptionController::class, 'destroy'])->name('admin.reward_redemption.delete');
        });

    });

});
