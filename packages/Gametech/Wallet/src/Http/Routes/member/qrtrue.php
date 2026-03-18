<?php



Route::post('qr/upload', 'Gametech\Wallet\Http\Controllers\SlipController@uploadQr')->defaults('_config', [
    'view' => 'wallet::customer.game.redirect',
])->name('customer.qr.upload');
