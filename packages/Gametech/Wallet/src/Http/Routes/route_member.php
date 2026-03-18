<?php

Route::post('broadcasting/auth', 'Gametech\Wallet\Http\Controllers\BroadcastController@authenticate')->name('auth.broadcasting.member')->middleware('authuser:customer');
Route::post('redeem', 'Gametech\Wallet\Http\Controllers\CouponController@redeem')->name('customer.coupon.redeem');
Route::post('getbonus', 'Gametech\Wallet\Http\Controllers\CouponController@getBonus')->name('customer.coupon.getbonus');
Route::get('bonuslist', 'Gametech\Wallet\Http\Controllers\CouponController@bonusList')->name('customer.coupon.bonuslist');


Route::get('/games/{type}/{provider}', 'Gametech\Wallet\Http\Controllers\GameController@getGames')->name('api.games.get');
Route::get('/list/provider/{type}', 'Gametech\Wallet\Http\Controllers\GameController@getProviders')->name('api.providers.get');



Route::get('logout', 'Gametech\Wallet\Http\Controllers\LoginController@logout')->defaults('_config', [
    'redirect' => 'customer.session.index',
])->name('customer.session.destroy');

Route::get('/', 'Gametech\Wallet\Http\Controllers\HomeController@index')->defaults('_config', [
    'view' => 'wallet::customer.home.index',
])->name('customer.home.index');

Route::get('/loadcredit', 'Gametech\Wallet\Http\Controllers\HomeController@loadCredit')->name('customer.home.credit');

Route::get('/loadbonus', 'Gametech\Wallet\Http\Controllers\HomeController@loadBonus')->name('customer.home.bonus');

Route::get('/loadcreditmin', 'Gametech\Wallet\Http\Controllers\HomeController@loadCreditMin')->name('customer.home.creditmin');

//                Route::get('/loadfreecredit', 'Gametech\Wallet\Http\Controllers\HomeController@loadFreeCredit')->name('customer.home.creditfree');

Route::get('/loadprofile', 'Gametech\Wallet\Http\Controllers\HomeController@loadProfile')->name('customer.home.profile');

Route::get('/loadprofilemin', 'Gametech\Wallet\Http\Controllers\HomeController@loadProfileMin')->name('customer.home.profilemin');

Route::get('/loadgame/{id}', 'Gametech\Wallet\Http\Controllers\HomeController@loadGameID')->name('customer.home.loadgameid');

Route::get('/loadgamefree/{id}', 'Gametech\Wallet\Http\Controllers\HomeController@loadGameFreeID')->name('customer.home.loadgamefreeid');

Route::post('create', 'Gametech\Wallet\Http\Controllers\HomeController@create')->name('customer.home.create');

Route::post('createfree', 'Gametech\Wallet\Http\Controllers\HomeController@createfree')->name('customer.home.createfree');

Route::post('game/autologin', 'Gametech\Wallet\Http\Controllers\ProfileController@gameListLogin')->name('customer.game.listlogin');

Route::post('game/newlogin', 'Gametech\Wallet\Http\Controllers\ProfileController@gameListLoginNew')->name('customer.game.listloginnew');

Route::get('game/login', 'Gametech\Wallet\Http\Controllers\ProfileController@loginGame')->name('customer.game.login');

Route::get('game', 'Gametech\Wallet\Http\Controllers\ProfileController@game')->defaults('_config', [
    'view' => 'wallet::customer.game.index',
])->name('customer.game.index');

//                Route::get('game/{id}', 'Gametech\Wallet\Http\Controllers\ProfileController@gameList')->defaults('_config', [
//                    'view' => 'wallet::customer.home.index',
//                ])->name('customer.game.list');

Route::get('game/{type}/{id}/{name}', 'Gametech\Wallet\Http\Controllers\ProfileController@gameList')->defaults('_config', [
    'view' => 'wallet::customer.game.list',
])->name('customer.game.list');

Route::get('cats/{id}', 'Gametech\Wallet\Http\Controllers\ProfileController@cats')->defaults('_config', [
    'view' => 'wallet::customer.cats.index',
])->name('customer.cats.list');

//                Route::post('game/seamless/{id}/{game}', 'Gametech\Wallet\Http\Controllers\ProfileController@gameRedirect')->defaults('_config', [
//                    'view' => 'wallet::customer.game.redirect',
//                ])->name('customer.game.redirect');

//                Route::post('game/autologin', 'Gametech\Wallet\Http\Controllers\ProfileController@gameListLogin')->name('customer.game.listlogin');
//
//                Route::post('game/newlogin', 'Gametech\Wallet\Http\Controllers\ProfileController@gameListLoginNew')->name('customer.game.listloginnew');
//
//                Route::get('game/login', 'Gametech\Wallet\Http\Controllers\ProfileController@loginGame')->name('customer.game.login');

Route::get('game/login/{id?}', 'Gametech\Wallet\Http\Controllers\ProfileController@loginGameID')->name('customer.game.loginid');

Route::get('topup', 'Gametech\Wallet\Http\Controllers\TopupController@index')->defaults('_config', [
    'view' => 'wallet::customer.topup.index',
])->name('customer.topup.index');

Route::get('topup/pompay', 'Gametech\Wallet\Http\Controllers\TopupController@index')->defaults('_config', [
    'view' => 'wallet::customer.topup.pompay',
])->name('customer.topup.index_pompay');

Route::get('topup/hengpay', 'Gametech\Wallet\Http\Controllers\TopupController@index')->defaults('_config', [
    'view' => 'wallet::customer.topup.hengpay',
])->name('customer.topup.index_hengpay');

Route::get('topup/luckypay', 'Gametech\Wallet\Http\Controllers\TopupController@index')->defaults('_config', [
    'view' => 'wallet::customer.topup.luckypay',
])->name('customer.topup.index_luckypay');

Route::get('topup/papayapay', 'Gametech\Wallet\Http\Controllers\TopupController@index')->defaults('_config', [
    'view' => 'wallet::customer.topup.papayapay',
])->name('customer.topup.index_papayapay');

Route::get('topup/superrich', 'Gametech\Wallet\Http\Controllers\TopupController@index')->defaults('_config', [
    'view' => 'wallet::customer.topup.superrich',
])->name('customer.topup.index_superrich');

Route::get('topup/ezpay', 'Gametech\Wallet\Http\Controllers\TopupController@index')->defaults('_config', [
    'view' => 'wallet::customer.topup.ezpay',
])->name('customer.topup.index_ezpay');

Route::post('topup/truewallet', 'Gametech\Wallet\Http\Controllers\TopupController@trueWallet')->name('customer.topup.tw');

Route::post('topup/luckypay', 'Gametech\Wallet\Http\Controllers\TopupController@lucky_create')->name('customer.topup.luckypay');

Route::post('topup/payment', 'Gametech\Wallet\Http\Controllers\TopupController@commspay_create')->name('customer.topup.payment');

Route::post('topup/pompay', 'Gametech\Wallet\Http\Controllers\TopupController@pompay_create')->defaults('_config', [
    'view' => 'wallet::customer.pompay.index',
])->name('customer.topup.pompay');

Route::post('topup/hengpay', 'Gametech\Wallet\Http\Controllers\TopupController@hengpay_create')->defaults('_config', [
    'view' => 'wallet::customer.hengpay.index',
])->name('customer.topup.hengpay');

Route::post('topup/papayapay', 'Gametech\Wallet\Http\Controllers\TopupController@papaya_create')->defaults('_config', [
    'view' => 'wallet::customer.papayapay.index',
])->name('customer.topup.papayapay');

Route::post('topup/superrich', 'Gametech\Wallet\Http\Controllers\TopupController@superrich_create')->defaults('_config', [
    'view' => 'wallet::customer.superrich.index',
])->name('customer.topup.superrich');

Route::post('topup/ezpay', 'Gametech\Wallet\Http\Controllers\TopupController@ezpay_create')->defaults('_config', [
    'view' => 'wallet::customer.ezpay.index',
])->name('customer.topup.ezpay');

Route::get('topuptest', 'Gametech\Wallet\Http\Controllers\TopupController@indextest')->defaults('_config', [
    'view' => 'wallet::customer.topup.indextest',
])->name('customer.topup.indextest');

Route::post('topup/upload', 'Gametech\Wallet\Http\Controllers\OcrController@readImage')->defaults('_config', [
    'view' => 'wallet::customer.hengpay.index',
])->name('customer.topup.upload');

Route::get('history', 'Gametech\Wallet\Http\Controllers\HistoryController@index')->defaults('_config', [
    'view' => 'wallet::customer.history.index',
])->name('customer.history.index');

Route::post('history', 'Gametech\Wallet\Http\Controllers\HistoryController@store')->name('customer.history.store');

Route::get('changepass', 'Gametech\Wallet\Http\Controllers\ProfileController@changemain')->defaults('_config', [
    'view' => 'wallet::customer.profile.change',
])->name('customer.profile.changemain');

Route::get('profile', 'Gametech\Wallet\Http\Controllers\ProfileController@index')->defaults('_config', [
    'view' => 'wallet::customer.profile.index',
])->name('customer.profile.index');

Route::post('profile/view', 'Gametech\Wallet\Http\Controllers\ProfileController@view')->name('customer.profile.view');

Route::post('profile/viewfree', 'Gametech\Wallet\Http\Controllers\ProfileController@viewfree')->name('customer.profile.viewfree');

Route::post('profile/changepro', 'Gametech\Wallet\Http\Controllers\ProfileController@changepro')->name('customer.profile.changepro');

Route::post('profile/changepass/api', 'Gametech\Wallet\Http\Controllers\ProfileController@changepass_api')->name('customer.profile.changepassapi');

Route::post('profile/changepass', 'Gametech\Wallet\Http\Controllers\ProfileController@changepass')->name('customer.profile.changepass');

Route::post('profile/resetgamepass', 'Gametech\Wallet\Http\Controllers\ProfileController@resetgamepass')->name('customer.profile.resetgamepass');

Route::post('profile/resetgamefreepass', 'Gametech\Wallet\Http\Controllers\ProfileController@resetgamefreepass')->name('customer.profile.resetgamefreepass');

Route::post('profile/change', 'Gametech\Wallet\Http\Controllers\ProfileController@change')->name('customer.profile.change');

Route::post('profile/changefree', 'Gametech\Wallet\Http\Controllers\ProfileController@changefree')->name('customer.profile.changefree');

Route::get('point', 'Gametech\Wallet\Http\Controllers\PointController@index')->defaults('_config', [
    'view' => 'wallet::customer.reward.index',
])->name('customer.reward.index');

Route::post('point', 'Gametech\Wallet\Http\Controllers\PointController@store')->defaults('_config', [

])->name('customer.reward.store');

Route::get('point/history', 'Gametech\Wallet\Http\Controllers\PointController@history')->defaults('_config', [
    'view' => 'wallet::customer.reward_history.index',
])->name('customer.reward_history.index');

Route::get('reward', 'Gametech\Wallet\Http\Controllers\SpinController@index')->defaults('_config', [
    'view' => 'wallet::customer.spin.index',
])->name('customer.spin.index');

Route::post('reward', 'Gametech\Wallet\Http\Controllers\SpinController@store')->defaults('_config', [

])->name('customer.spin.store');

Route::get('reward/history', 'Gametech\Wallet\Http\Controllers\SpinController@history')->defaults('_config', [
    'view' => 'wallet::customer.spin_history.index',
])->name('customer.spin_history.index');

Route::get('manual', 'Gametech\Wallet\Http\Controllers\ManualController@index')->defaults('_config', [
    'view' => 'wallet::customer.manual.index',
])->name('customer.manual.index');

Route::get('download', 'Gametech\Wallet\Http\Controllers\DownloadController@index')->defaults('_config', [
    'view' => 'wallet::customer.download.index',
])->name('customer.download.index');

Route::get('download', 'Gametech\Wallet\Http\Controllers\DownloadController@index')->defaults('_config', [
    'view' => 'wallet::customer.download.index',
    'view_single' => 'wallet::customer.download.index_single',
])->name('customer.download.index');

Route::get('promotion/api', 'Gametech\Wallet\Http\Controllers\PromotionController@loadPromotion')->name('customer.promotion.loadPromotion');

Route::get('promotion', 'Gametech\Wallet\Http\Controllers\PromotionController@index')->defaults('_config', [
    'view' => 'wallet::customer.promotion.index',
])->name('customer.promotion.index');

Route::get('promotions', 'Gametech\Wallet\Http\Controllers\PromotionController@indextest')->defaults('_config', [
    'view' => 'wallet::customer.promotion.index',
])->name('customer.promotions.index');

Route::post('promotion/api', 'Gametech\Wallet\Http\Controllers\PromotionController@store_api')->defaults('_config', [

])->name('customer.promotion.storeapi');

Route::post('promotion/cancel', 'Gametech\Wallet\Http\Controllers\PromotionController@cancel')->defaults('_config', [

])->name('customer.promotion.cancel');

Route::post('promotion', 'Gametech\Wallet\Http\Controllers\PromotionController@store')->defaults('_config', [

])->name('customer.promotion.store');

Route::post('promotion/select', 'Gametech\Wallet\Http\Controllers\PromotionController@selectPromotion')->defaults('_config', [

])->name('customer.promotion.select');

Route::post('promotion/deselect', 'Gametech\Wallet\Http\Controllers\PromotionController@deselectPromotion')->defaults('_config', [

])->name('customer.promotion.deselect');

//                Route::get('cashback', 'Gametech\Wallet\Http\Controllers\CashbackController@index')->defaults('_config', [
//                    'view' => 'wallet::customer.cashback.index',
//                ])->name('customer.cashback.index');

Route::get('checkin', 'Gametech\Wallet\Http\Controllers\CheckinController@index')->defaults('_config', [
    'view' => 'wallet::customer.checkin.index',
])->name('customer.checkin.index');

Route::post('checkin', 'Gametech\Wallet\Http\Controllers\CheckinController@store')->defaults('_config', [

])->name('customer.checkin.store');

Route::get('checkin/history', 'Gametech\Wallet\Http\Controllers\CheckinController@history')->defaults('_config', [
    'view' => 'wallet::customer.checkin.history',
])->name('customer.checkin.history');

Route::get('contributor', 'Gametech\Wallet\Http\Controllers\ContributorController@index')->defaults('_config', [
    'view' => 'wallet::customer.contributor.index',
])->name('customer.contributor.index');

Route::get('contributortest', 'Gametech\Wallet\Http\Controllers\ContributorController@indextest')->defaults('_config', [
    'view' => 'wallet::customer.contributor.indextest',
])->name('customer.contributor.indextest');

Route::post('contributor', 'Gametech\Wallet\Http\Controllers\ContributorController@store')->name('customer.contributor.store');

Route::get('contributor/api', 'Gametech\Wallet\Http\Controllers\ContributorController@contributor')->name('customer.contributor.getapi');

Route::get('withdraw', 'Gametech\Wallet\Http\Controllers\WithdrawController@index')->defaults('_config', [
    'view' => 'wallet::customer.withdraw.index',
])->name('customer.withdraw.index');

Route::post('withdraw/request', 'Gametech\Wallet\Http\Controllers\WithdrawController@store')->defaults('_config', [
    'redirect' => 'customer.withdraw.index',
])->name('customer.withdraw.store')->block(30, 30);

Route::post('withdraw/requestapi', 'Gametech\Wallet\Http\Controllers\WithdrawController@store_api')->defaults('_config', [
    'redirect' => 'customer.withdraw.index',
])->name('customer.withdraw.storeapi')->block(30, 30);

Route::get('money', 'Gametech\Wallet\Http\Controllers\MoneyController@index')->defaults('_config', [
    'view' => 'wallet::customer.money.index',
])->name('customer.money.index');

Route::post('money', 'Gametech\Wallet\Http\Controllers\MoneyController@store')->name('customer.money.store');

Route::post('credit/game/autologin', 'Gametech\Wallet\Http\Controllers\ProfileController@gameFreeListLogin')->name('customer.credit.game.listlogin');

//                Route::get('credit/game/login', 'Gametech\Wallet\Http\Controllers\ProfileController@loginGameCredit')->name('customer.credit.game.login');

Route::get('credit/game/login/{id?}', 'Gametech\Wallet\Http\Controllers\ProfileController@loginGameCreditID')->name('customer.credit.game.login');

Route::get('credit', 'Gametech\Wallet\Http\Controllers\CreditController@index')->defaults('_config', [
    'view' => 'wallet::customer.credit.index',
])->name('customer.credit.index');

Route::get('credit/game', 'Gametech\Wallet\Http\Controllers\ProfileController@games')->defaults('_config', [
    'view' => 'wallet::customer.credit.game.index',
])->name('customer.credit.game.index');

Route::get('credit/game/{id}', 'Gametech\Wallet\Http\Controllers\ProfileController@gameListfree')->defaults('_config', [
    'view' => 'wallet::customer.credit.game.list',
])->name('customer.credit.game.list');

Route::get('credit/cats/{id}', 'Gametech\Wallet\Http\Controllers\ProfileController@catsfree')->defaults('_config', [
    'view' => 'wallet::customer.credit.cats.index',
])->name('customer.credit.cats.list');

Route::get('credit/history', 'Gametech\Wallet\Http\Controllers\CreditHistoryController@index')->defaults('_config', [
    'view' => 'wallet::customer.credit.history.index',
])->name('customer.credit.history.index');

Route::post('credit/history', 'Gametech\Wallet\Http\Controllers\CreditHistoryController@store')->name('customer.credit.history.store');

Route::get('credit/withdraw', 'Gametech\Wallet\Http\Controllers\CreditWithdrawController@index')->defaults('_config', [
    'view' => 'wallet::customer.credit.withdraw.index',
])->name('customer.credit.withdraw.index');

Route::post('credit/withdraw/request', 'Gametech\Wallet\Http\Controllers\CreditWithdrawController@store')->defaults('_config', [
    'redirect' => 'customer.credit.withdraw.index',
])->name('customer.credit.withdraw.store')->middleware('throttle:1,1');

Route::get('credit/transfer/game', 'Gametech\Wallet\Http\Controllers\CreditTransferGameController@index')->defaults('_config', [
    'view' => 'wallet::customer.credit.transfer.game.index',
])->name('customer.credit.transfer.game.index');

Route::post('credit/transfer/game/check', 'Gametech\Wallet\Http\Controllers\CreditTransferGameController@check')->defaults('_config', [
    'redirect' => 'customer.credit.transfer.game.index',
    'view' => 'wallet::customer.credit.transfer.game.check',
])->name('customer.credit.transfer.game.check');

Route::post('credit/transfer/game/confirm', 'Gametech\Wallet\Http\Controllers\CreditTransferGameController@confirm')->defaults('_config', [

])->name('customer.credit.transfer.game.confirm')->middleware('throttle:1,0.1');

Route::get('credit/transfer/game/complete', 'Gametech\Wallet\Http\Controllers\CreditTransferGameController@complete')->defaults('_config', [
    'view' => 'wallet::customer.credit.transfer.game.complete',
])->name('customer.credit.transfer.game.complete');

Route::get('credit/transfer/wallet', 'Gametech\Wallet\Http\Controllers\CreditTransferWalletController@index')->defaults('_config', [
    'view' => 'wallet::customer.credit.transfer.wallet.index',
])->name('customer.credit.transfer.wallet.index');

Route::post('credit/transfer/wallet/check', 'Gametech\Wallet\Http\Controllers\CreditTransferWalletController@check')->defaults('_config', [
    'redirect' => 'customer.credit.transfer.wallet.index',
    'view' => 'wallet::customer.credit.transfer.wallet.check',
])->name('customer.credit.transfer.wallet.check');

Route::post('credit/transfer/wallet/confirm', 'Gametech\Wallet\Http\Controllers\CreditTransferWalletController@confirm')->defaults('_config', [

])->name('customer.credit.transfer.wallet.confirm')->middleware('throttle:1,0.1');

Route::get('credit/transfer/wallet/complete', 'Gametech\Wallet\Http\Controllers\CreditTransferWalletController@complete')->defaults('_config', [
    'view' => 'wallet::customer.credit.transfer.wallet.complete',
])->name('customer.credit.transfer.wallet.complete');

// Transfer Wallet to Game
// Transfer Wallet to Game
Route::get('transfer/game', 'Gametech\Wallet\Http\Controllers\TransferGameController@index')->defaults('_config', [
    'view' => 'wallet::customer.transfer.game.index',
])->name('customer.transfer.game.index');

Route::get('transfer/gametest', 'Gametech\Wallet\Http\Controllers\TransferGameController@indextest')->defaults('_config', [
    'view' => 'wallet::customer.transfer.game.indextest',
])->name('customer.transfer.game.indextest');

Route::post('transfer/game/check', 'Gametech\Wallet\Http\Controllers\TransferGameController@check')->defaults('_config', [
    'redirect' => 'customer.transfer.game.index',
    'view' => 'wallet::customer.transfer.game.check',
])->name('customer.transfer.game.check');

Route::post('transfer/game/confirm', 'Gametech\Wallet\Http\Controllers\TransferGameController@confirm')->defaults('_config', [

])->name('customer.transfer.game.confirm')->middleware('throttle:1,0.1');

Route::get('transfer/game/complete', 'Gametech\Wallet\Http\Controllers\TransferGameController@complete')->defaults('_config', [
    'view' => 'wallet::customer.transfer.game.complete',
])->name('customer.transfer.game.complete');

// Transfer Game to Wallet
Route::get('transfer/wallet', 'Gametech\Wallet\Http\Controllers\TransferWalletController@index')->defaults('_config', [
    'view' => 'wallet::customer.transfer.wallet.index',
])->name('customer.transfer.wallet.index');

Route::post('transfer/wallet/check', 'Gametech\Wallet\Http\Controllers\TransferWalletController@check')->defaults('_config', [
    'redirect' => 'customer.transfer.wallet.index',
    'view' => 'wallet::customer.transfer.wallet.check',
])->name('customer.transfer.wallet.check');

Route::post('transfer/wallet/confirm', 'Gametech\Wallet\Http\Controllers\TransferWalletController@confirm')->defaults('_config', [

])->name('customer.transfer.wallet.confirm')->middleware('throttle:1,0.1');

Route::get('transfer/wallet/complete', 'Gametech\Wallet\Http\Controllers\TransferWalletController@complete')->defaults('_config', [
    'view' => 'wallet::customer.transfer.wallet.complete',
])->name('customer.transfer.wallet.complete');

Route::post('transfer/bonus/confirm', 'Gametech\Wallet\Http\Controllers\TransferWalletController@bonus')->defaults('_config', [

])->name('customer.transfer.bonus.confirm')->middleware('throttle:1,0.1');

Route::get('credit/transfer/loadgame', 'Gametech\Wallet\Http\Controllers\CreditTransferGameController@loadGame')->name('customer.credit.transfer.load.game');
//                Route::post('transfer/game/checkpro', 'Gametech\Wallet\Http\Controllers\TransferGameController@checkTransfer')->defaults('_config', [
//                    'redirect' => 'customer.transfer.game.index',
//                    'view' => 'wallet::customer.transfer.game.check',
//                ])->name('customer.transfer.game.checkpro')->block(30, 30);
//                Route::post('credit/transfer/game/checkpro', 'Gametech\Wallet\Http\Controllers\CreditTransferGameController@checkTransfer')->defaults('_config', [
//                    'redirect' => 'customer.credit.transfer.game.index',
//                    'view' => 'wallet::customer.credit.transfer.game.check',
//                ])->name('customer.credit.transfer.game.checkpro')->block(30, 30);

Route::post('credit/transfer/game/getbonus', 'Gametech\Wallet\Http\Controllers\CreditTransferGameController@getBonus')->name('customer.credit.transfer.game.getbonus')->block(30, 30);
