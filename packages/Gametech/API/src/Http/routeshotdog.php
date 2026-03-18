<?php

Route::prefix('hotdog')->group(function () {
    Route::post('balance', 'HotDogController@getBalance');
    Route::post('bet', 'HotDogController@transferOut');
    Route::post('win', 'HotDogController@transferIn');
    Route::post('refund', 'HotDogController@cancelBets');
    Route::post('cashbonus', 'HotDogController@winReward');
    Route::post('exit', 'HotDogController@kickOut');

});

Route::prefix('single')->group(function () {
    Route::post('transaction/iog_get_balance', 'SingleController@getBalance');
    Route::post('transaction/iog_bet', 'SingleController@bet');
    Route::post('transaction/iog_bet_list', 'SingleController@betLotto');
    Route::post('transaction/iog_set_result', 'SingleController@setBetResult');
    Route::post('transaction/iog_set_result_lotto90', 'SingleController@setBetResultLotto90');
    Route::post('transaction/iog_set_result_lotto12', 'SingleController@setBetResultLotto12');
    Route::post('transaction/iog_cancel_round', 'SingleController@cancelRound');
    Route::post('transaction/iog_cancel_bet', 'SingleController@cancelBet');
    Route::post('transaction/iog_reset_result', 'SingleController@resetResult');
});

//Route::prefix('rb7lotto')->group(function () {
//    Route::post('getbalance', 'RbLottoController@getBalance');
//    Route::middleware('ensure.in.game')->group(function () {
//        Route::post('bet', 'RbLottoController@placeBet');
//    });
//
//    Route::post('settle', 'RbLottoController@settleBet');
//    Route::post('rollback-settle', 'RbLottoController@rollBack');
//    Route::post('cancelbet', 'RbLottoController@cancelBets');
//});