<?php
$apiRoute = config('gametech.api_url') ?? 'api';


Route::domain("$apiRoute." . (is_null(config('app.admin_domain_url')) ? config('app.domain_url') : config('app.admin_domain_url')))->group(function () {

    Route::prefix('api')->group(function () {

        Route::group(['namespace' => 'Gametech\Payment\Http\Controllers', 'middleware' => ['api']], function () {

            Route::post('wildpay/deposit/callback', 'WildPayController@deposit_callback')->name('api.wildpay.deposit.callback');
            Route::post('wildpay/withdraw/callback', 'WildPayController@withdraw_callback')->name('api.wildpay.withdraw.callback');

            // ===== XEPAY =====
            Route::post('xepay/deposit/callback', 'XEPayController@deposit_callback')->name('api.xepay.deposit.callback');
            Route::post('xepay/withdraw/callback', 'XEPayController@withdraw_callback')->name('api.xepay.withdraw.callback');

//            Route::post('payment/deposit/callback/usd', 'SulifuPayController@deposit_callback_usd')->name('api.payment.deposit.callback.usd');
//            Route::post('payment/withdraw/callback/usd', 'SulifuPayController@withdraw_callback_usd')->name('api.payment.withdraw.callback.usd');

//            Route::post('payment/deposit/callback/khr', 'SulifuPayController@deposit_callback_khr')->name('api.payment.deposit.callback.khr');
//            Route::post('payment/withdraw/callback/khr', 'SulifuPayController@withdraw_callback_khr')->name('api.payment.withdraw.callback.khr');

//	        Route::post('payment/deposit/callback', 'MatePayController@deposit_callback')->name('api.payment.deposit.callback');
//	        Route::post('payment/withdraw/callback', 'MatePayController@withdraw_callback')->name('api.payment.withdraw.callback');

            Route::post('kingpay/deposit/callback', 'KingPayController@deposit_callback')->name('api.kingpay.deposit.callback');
            Route::post('kingpay/withdraw/callback', 'KingPayController@withdraw_callback')->name('api.kingpay.withdraw.callback');

            Route::post('wellpay/deposit/callback', 'WellPayController@deposit_callback')->name('api.wellpay.deposit.callback');
            Route::post('wellpay/withdraw/callback', 'WellPayController@withdraw_callback')->name('api.wellpay.withdraw.callback');

//            Route::post('deposit/callback', 'APayController@deposit_callback')->name('api.apay.deposit.callback');
            Route::post('apay/deposit/callback', 'APayController@deposit_callback')->name('api.apay.deposit.callback');
            Route::post('apay/withdraw/callback', 'APayController@withdraw_callback')->name('api.apay.withdraw.callback');

            Route::post('tlconnectpay/deposit/callback', 'TlConnectPayController@deposit_callback')->name('api.tlconnectpay.deposit.callback');
            Route::post('tlconnectpay/withdraw/callback', 'TlConnectPayController@withdraw_callback')->name('api.tlconnectpay.withdraw.callback');


            Route::post('onpay/deposit/callback', 'OnPayController@deposit_callback')->name('api.onpay.deposit.callback');
            Route::post('onpay/withdraw/callback', 'OnPayController@withdraw_callback')->name('api.onpay.withdraw.callback');

            Route::post('maxpay/deposit/callback', 'MaxPayController@deposit_callback')->name('api.maxpay.deposit.callback');
            Route::post('maxpay/withdraw/callback', 'MaxPayController@withdraw_callback')->name('api.maxpay.withdraw.callback');

            // ===== AutoTransfer =====
            Route::get('autotransfer/check_ma', 'AutoTransferController@check_ma')->name('api.autotransfer.check_ma');
            Route::post('autotransfer/deposit/callback', 'AutoTransferController@deposit_callback')->name('api.autotransfer.deposit.callback');
            Route::post('autotransfer/withdraw/callback', 'AutoTransferController@withdraw_callback')->name('api.autotransfer.withdraw.callback');

            // ===== SMKPAY =====
            Route::post('smkpay/deposit/callback', 'SmkPayController@deposit_callback')->name('api.smkpay.deposit.callback');
            Route::post('smkpay/withdraw/callback', 'SmkPayController@withdraw_callback')->name('api.smkpay.withdraw.callback');

            Route::post('payonex/callback', 'PayoneXController@callback')->name('api.payonex.callback');
            Route::post('payonex/deposit/callback', 'PayoneXController@deposit_callback')->name('api.payonex.deposit.callback');
            Route::post('payonex/withdraw/callback', 'PayoneXController@withdraw_callback')->name('api.payonex.withdraw.callback');

            // ===== DEEPPAY =====
            Route::post('deeppay/deposit/callback', 'DeepPayController@deposit_callback')->name('api.deeppay.deposit.callback');
            Route::post('deeppay/withdraw/callback', 'DeepPayController@withdraw_callback')->name('api.deeppay.withdraw.callback');


        });

    });

});

$domain = config('app.user_url') === ''
    ? (config('app.user_domain_url') ?? config('app.domain_url'))
    : config('app.user_url') . '.' . (config('app.user_domain_url') ?? config('app.domain_url'));

Route::domain($domain)->group(function () {
    Route::middleware('web')->group(function () {

        Route::prefix('member')->group(function () {

            Route::middleware(['customer', 'authuser', 'online'])->group(function () {
                // ไว้สำหรับ future route ถ้าจะเพิ่ม

                Route::get('wildpay/deposit/status/{txid}', 'Gametech\Payment\Http\Controllers\WildPayController@checkStatus')->name('api.wildpay.deposit.status');
                Route::post('wildpay/deposit/expire/{txid}', 'Gametech\Payment\Http\Controllers\WildPayController@expire')->name('api.wildpay.deposit.expire');
                Route::post('wildpay/deposit/create', 'Gametech\Payment\Http\Controllers\WildPayController@deposit')->name('api.wildpay.deposit');
                Route::get('wildpay/qrcode/{id}', 'Gametech\Payment\Http\Controllers\WildPayController@index')->name('api.wildpay.index');

                Route::get('kingpay/deposit/status/{txid}', 'Gametech\Payment\Http\Controllers\KingPayController@checkStatus')->name('api.kingpay.deposit.status');
                Route::post('kingpay/deposit/expire/{txid}', 'Gametech\Payment\Http\Controllers\KingPayController@expire')->name('api.kingpay.deposit.expire');
                Route::post('kingpay/deposit/create', 'Gametech\Payment\Http\Controllers\KingPayController@deposit')->name('api.kingpay.deposit');
                Route::get('kingpay/qrcode/{id}', 'Gametech\Payment\Http\Controllers\KingPayController@index')->name('api.kingpay.index');

                Route::get('wellpay/deposit/status/{txid}', 'Gametech\Payment\Http\Controllers\WellPayController@checkStatus')->name('api.wellpay.deposit.status');
                Route::post('wellpay/deposit/expire/{txid}', 'Gametech\Payment\Http\Controllers\WellPayController@expire')->name('api.wellpay.deposit.expire');
                Route::post('wellpay/deposit/create', 'Gametech\Payment\Http\Controllers\WellPayController@deposit')->name('api.wellpay.deposit');

                Route::get('wellpay/qrcode/{id}', 'Gametech\Payment\Http\Controllers\WellPayController@index')->name('api.wellpay.index');
                Route::get('wellpaytest/qrcode/{id}', 'Gametech\Payment\Http\Controllers\WellPayController@indextest')->name('api.wellpay.indextest');

                Route::get('apay/deposit/status/{txid}', 'Gametech\Payment\Http\Controllers\APayController@checkStatus')->name('api.apay.deposit.status');
                Route::post('apay/deposit/expire/{txid}', 'Gametech\Payment\Http\Controllers\APayController@expire')->name('api.apay.deposit.expire');
                Route::post('apay/deposit/create', 'Gametech\Payment\Http\Controllers\APayController@deposit')->name('api.apay.deposit');
                Route::get('apay/qrcode/{id}', 'Gametech\Payment\Http\Controllers\APayController@index')->name('api.apay.index');

                Route::get('tlconnectpay/deposit/status/{txid}', 'Gametech\Payment\Http\Controllers\TlConnectPayController@checkStatus')->name('api.tlconnectpay.deposit.status');
                Route::post('tlconnectpay/deposit/expire/{txid}', 'Gametech\Payment\Http\Controllers\TlConnectPayController@expire')->name('api.tlconnectpay.deposit.expire');
                Route::post('tlconnectpay/deposit/create', 'Gametech\Payment\Http\Controllers\TlConnectPayController@deposit')->name('api.tlconnectpay.deposit');

                Route::get('tlconnectpay/qrcode/{id}', 'Gametech\Payment\Http\Controllers\TlConnectPayController@index')->name('api.tlconnectpay.index');

                Route::get('onpay/deposit/status/{txid}', 'Gametech\Payment\Http\Controllers\OnPayController@checkStatus')->name('api.onpay.deposit.status');
                Route::post('onpay/deposit/expire/{txid}', 'Gametech\Payment\Http\Controllers\OnPayController@expire')->name('api.onpay.deposit.expire');
                Route::post('onpay/deposit/create', 'Gametech\Payment\Http\Controllers\OnPayController@deposit')->name('api.onpay.deposit');

                Route::get('onpay/qrcode/{id}', 'Gametech\Payment\Http\Controllers\OnPayController@index')->name('api.onpay.index');


                Route::get('maxpay/deposit/status/{txid}', 'Gametech\Payment\Http\Controllers\MaxPayController@checkStatus')->name('api.maxpay.deposit.status');
                Route::post('maxpay/deposit/expire/{txid}', 'Gametech\Payment\Http\Controllers\MaxPayController@expire')->name('api.maxpay.deposit.expire');
                Route::post('maxpay/deposit/create', 'Gametech\Payment\Http\Controllers\MaxPayController@deposit')->name('api.maxpay.deposit');

                Route::get('maxpay/qrcode/{id}', 'Gametech\Payment\Http\Controllers\MaxPayController@index')->name('api.maxpay.index');


                Route::get('payonex/deposit/status/{txid}', 'Gametech\Payment\Http\Controllers\PayoneXController@checkStatus')->name('api.payonex.deposit.status');
                Route::post('payonex/deposit/expire/{txid}', 'Gametech\Payment\Http\Controllers\PayoneXController@expire')->name('api.payonex.deposit.expire');
                Route::post('payonex/deposit/create', 'Gametech\Payment\Http\Controllers\PayoneXController@deposit')->name('api.payonex.deposit');
                Route::get('payonex/qrcode/{id}', 'Gametech\Payment\Http\Controllers\PayoneXController@index')->name('api.payonex.index');

                Route::get('xepay/deposit/status/{txid}', 'Gametech\Payment\Http\Controllers\XEPayController@checkStatus')->name('api.xepay.deposit.status');
                Route::post('xepay/deposit/expire/{txid}', 'Gametech\Payment\Http\Controllers\XEPayController@expire')->name('api.xepay.deposit.expire');
                Route::post('xepay/deposit/create', 'Gametech\Payment\Http\Controllers\XEPayController@deposit')->name('api.xepay.deposit');
                Route::get('xepay/qrcode/{id}', 'Gametech\Payment\Http\Controllers\XEPayController@index')->name('api.xepay.index');


//                Route::get('deeppay/deposit/status/{txid}', 'Gametech\Payment\Http\Controllers\DeepPayController@checkStatus')->name('api.deeppay.deposit.status');
//                Route::post('deeppay/deposit/expire/{txid}', 'Gametech\Payment\Http\Controllers\DeepPayController@expire')->name('api.deeppay.deposit.expire');
//                Route::post('deeppay/deposit/create', 'Gametech\Payment\Http\Controllers\DeepPayController@deposit')->name('api.deeppay.deposit');
//                Route::get('deeppay/qrcode/{id}', 'Gametech\Payment\Http\Controllers\DeepPayController@index')->name('api.deeppay.index');

//	            Route::get('payment/deposit/status/{txid}', 'Gametech\Payment\Http\Controllers\MatePayController@checkStatus')->name('api.payment.deposit.status');
//	            Route::post('payment/deposit/expire/{txid}', 'Gametech\Payment\Http\Controllers\MatePayController@expire')->name('api.payment.deposit.expire');
//	            Route::post('payment/deposit/create', 'Gametech\Payment\Http\Controllers\MatePayController@deposit')->name('api.payment.deposit');
//	            Route::get('payment/qrcode/{id}', 'Gametech\Payment\Http\Controllers\MatePayController@index')->name('api.payment.index');

//                                Route::get('payment/deposit/status/{txid}', 'Gametech\Payment\Http\Controllers\WildPayController@checkStatus')->name('api.payment.deposit.status');
//                                Route::post('payment/deposit/expire/{txid}', 'Gametech\Payment\Http\Controllers\WildPayController@expire')->name('api.payment.deposit.expire');
//                                Route::post('payment/deposit/create', 'Gametech\Payment\Http\Controllers\WildPayController@deposit')->name('api.payment.deposit');
//                                Route::get('payment/qrcode/{id}', 'Gametech\Payment\Http\Controllers\WildPayController@index')->name('api.payment.index');

//                Route::get('payment/deposit/status/{txid}', 'Gametech\Payment\Http\Controllers\SulifuPayController@checkStatus')->name('api.payment.deposit.status');
//                Route::post('payment/deposit/expire/{txid}', 'Gametech\Payment\Http\Controllers\SulifuPayController@expire')->name('api.payment.deposit.expire');
//                Route::post('payment/deposit/create/usd', 'Gametech\Payment\Http\Controllers\SulifuPayController@deposit_usd')->name('api.payment.deposit.usd');
//                Route::post('payment/deposit/create/khr', 'Gametech\Payment\Http\Controllers\SulifuPayController@deposit_khr')->name('api.payment.deposit.khr');
//                Route::get('payment/qrcode/{id}', 'Gametech\Payment\Http\Controllers\SulifuPayController@index')->name('api.payment.index');

            });
        });
    });
});
