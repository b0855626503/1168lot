<?php

Route::post('slip/loadbank', 'Gametech\Wallet\Http\Controllers\SlipController@loadBank')->defaults('_config', [
    'view' => 'wallet::customer.game.redirect',
])->name('customer.slip.loadbank');

Route::post('slip/upload', 'Gametech\Wallet\Http\Controllers\SlipController@verifySlip')->defaults('_config', [
    'view' => 'wallet::customer.game.redirect',
])->name('customer.slip.upload');