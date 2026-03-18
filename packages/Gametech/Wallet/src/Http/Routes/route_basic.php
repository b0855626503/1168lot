<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;

Route::post('tw/{mobile}/webhook', 'Gametech\Admin\Http\Controllers\WebhookController@index');

Route::post('pompay/callback', 'Gametech\Admin\Http\Controllers\PomPayController@callback');
Route::post('pompay/payout_callback', 'Gametech\Admin\Http\Controllers\PomPayController@payout_callback');

Route::get('pompay/return', 'Gametech\Admin\Http\Controllers\PomPayController@returns')->defaults('_config', [
    'view' => 'wallet::customer.pompay.callback',
]);

Route::post('commspay/callback', 'Gametech\Admin\Http\Controllers\CommSpayController@callback');
Route::post('hengpay/callback', 'Gametech\Admin\Http\Controllers\HengPayController@callback');
Route::post('luckypay/callback', 'Gametech\Admin\Http\Controllers\LuckyPayController@callback');
Route::post('luckypay/payout_callback', 'Gametech\Admin\Http\Controllers\LuckyPayController@payout_callback');
Route::post('papayapay/callback', 'Gametech\Admin\Http\Controllers\PapayaPayController@callback');
Route::post('papayapay/payout_callback', 'Gametech\Admin\Http\Controllers\PapayaPayController@payout_callback');
Route::post('superrich/callback', 'Gametech\Admin\Http\Controllers\SuperrichPayController@callback');
Route::post('ezpay/callback', 'Gametech\Admin\Http\Controllers\EzPayController@callback');
//        Route::get('/', 'Gametech\Wallet\Http\Controllers\Controller@redirectToLogin');

//	Route::get('/lang-{lang}.js', 'Gametech\Wallet\Http\Controllers\LoginController@loadlang');

Route::post('/locale/switch', function (Request $request) {
    $locale = $request->input('locale');
    $available = array_keys(Config::get('languages.available', []));
    if (!in_array($locale, $available, true)) {
        abort(400, 'Unsupported locale');
    }

    App::setLocale($locale);
    Session::put('locale', $locale);

    return response()->json(['success' => true, 'locale' => $locale]);
})->name('locale.switch');

// --- ให้ไฟล์ JSON ของข้อความแปล ---
Route::get('/lang-{locale}.js', [\Gametech\Wallet\Http\Controllers\LoginController::class, 'loadlang'])
    ->name('locale.js');

Route::get('/lang-{locale}.json', [\Gametech\Wallet\Http\Controllers\LoginController::class, 'langJson'])
    ->name('locale.json');

Route::get('/', 'Gametech\Wallet\Http\Controllers\LoginController@show')->defaults('_config', [
    'view' => 'wallet::customer.sessions.create',
])->name('customer.session.index');

//	Route::get('/login/{id}', 'Gametech\Wallet\Http\Controllers\LoginController@shownew')->defaults('_config', [
//		'view' => 'wallet::customer.sessions.login_new',
//	])->name('customer.session.login_new');

Route::get('/login', 'Gametech\Wallet\Http\Controllers\LoginController@show')->defaults('_config', [
    'view' => 'wallet::customer.sessions.create',
])->name('customer.session.login');

Route::get('/logins', 'Gametech\Wallet\Http\Controllers\LoginController@show')->defaults('_config', [
    'view' => 'wallet::customer.sessions.creates',
])->name('customer.session.indexs');

Route::post('login', 'Gametech\Wallet\Http\Controllers\LoginController@login')->defaults('_config', [
    'redirect' => 'customer.home.index',
])->name('customer.session.create');

Route::get('lang/{lang}', 'Gametech\Wallet\Http\Controllers\LoginController@lang')->defaults('_config', [
    'redirect' => 'customer.home.index',
])->name('customer.home.lang');

//        Route::get('register/{id?}', 'Gametech\Wallet\Http\Controllers\LoginController@store')->defaults('_config', [
//            'view' => 'wallet::customer.sessions.store'
//        ])->name('customer.session.store');

//        Route::redirect('register/{id}', '/contributor/{id}');

// Route::get('register', 'Gametech\Wallet\Http\Controllers\LoginController@store')->defaults('_config', [
//    'view' => 'wallet::customer.sessions.store',
// ])->name('customer.session.store');

Route::get('contributor/{id}', 'Gametech\Wallet\Http\Controllers\LoginController@store')->defaults('_config', [
    'view' => 'wallet::customer.contributor.register',
])->name('customer.contributor.register');

Route::post('checkacc', 'Gametech\Wallet\Http\Controllers\LoginController@checkAcc')->defaults('_config', [
    'redirect' => 'customer.home.index',
])->name('customer.checkacc.index');

Route::post('check-bank', 'Gametech\Wallet\Http\Controllers\LoginController@checkBank')->defaults('_config', [
    'redirect' => 'customer.home.index',
])->name('customer.check.bank');

Route::post('check-phone', 'Gametech\Wallet\Http\Controllers\LoginController@checkPhone')->defaults('_config', [
    'redirect' => 'customer.home.index',
])->name('customer.check.phone');

Route::post('register/api', 'Gametech\Wallet\Http\Controllers\LoginController@register_api')->defaults('_config', [
    'redirect' => 'customer.home.index',
    'verify' => 'customer.verify.index',
])->name('customer.session.register_api');

// Route::post('register', 'Gametech\Wallet\Http\Controllers\LoginController@register')->defaults('_config', [
//    'redirect' => 'customer.home.index',
//    'verify' => 'customer.verify.index',
// ])->name('customer.session.register');

Route::get('test', 'Gametech\Wallet\Http\Controllers\TestController@index')->defaults('_config', [
    'view' => 'wallet::customer.test.index',
])->name('customer.test.index');

Route::get('promotion', 'Gametech\Wallet\Http\Controllers\PromotionController@show')->defaults('_config', [
    'view' => 'wallet::customer.promotion.show',
])->name('customer.promotion.show');

Route::get('download', 'Gametech\Wallet\Http\Controllers\LoginController@download')->defaults('_config', [
    'view' => 'wallet::customer.download.home',
])->name('customer.home.download');

Route::get('verify', 'Gametech\Wallet\Http\Controllers\VerifyController@index')->defaults('_config', [
    'view' => 'wallet::customer.verify.index',
])->name('customer.verify.index')->middleware('customer', 'authuser');

Route::post('verify', 'Gametech\Wallet\Http\Controllers\VerifyController@update')->name('customer.verify.update')->middleware('customer', 'authuser');

Route::post('check/mobile', 'Gametech\Wallet\Http\Controllers\VerifyController@checkMobile')->name('customer.verify.mobile');

Route::post('request/otp', 'Gametech\Wallet\Http\Controllers\VerifyController@requestOtp')->name('customer.verify.request_otp');
//
//        Route::get('/seamless/{id?}/{name?}', 'Gametech\Wallet\Http\Controllers\ProfileController@gameRedirect')->defaults('_config', [
//            'view' => 'wallet::customer.game.redirect',
//        ])->name('customer.game.redirect');

//	Route::get('/play/{id?}/{name?}', 'Gametech\Wallet\Http\Controllers\ProfileController@gameRedirectSingle')->defaults('_config', [
//		'view' => 'wallet::customer.game.redirect',
//	])->name('customer.game.redirect_single');

Route::get('/play/{type?}/{provider?}/{id?}', 'Gametech\Wallet\Http\Controllers\ProfileController@gameRedirectSingleAmb')->defaults('_config', [
    'view' => 'wallet::customer.game.redirect',
])->name('customer.game.redirect_single');

//        Route::get('/play/{method?}/{id?}/{name?}', 'Gametech\Wallet\Http\Controllers\ProfileController@gameRedirectApi')->defaults('_config', [
//            'view' => 'wallet::customer.game.redirect',
//        ])->name('customer.game.redirect_single');

Route::get('play/{method?}/{id?}/{name?}', 'Gametech\Wallet\Http\Controllers\ProfileController@gameRedirect')->defaults('_config', [
    'view' => 'wallet::customer.game.redirect',
])->name('customer.game.redirect');

Route::get('playfree/{method?}/{id?}/{name?}', 'Gametech\Wallet\Http\Controllers\ProfileController@gameCreditRedirect')->defaults('_config', [
    'view' => 'wallet::customer.credit.game.redirect',
])->name('customer.credit.game.redirect');

Route::post('check/step01', 'Gametech\Wallet\Http\Controllers\LoginController@step01')->name('customer.check.step01');
Route::post('check/step02', 'Gametech\Wallet\Http\Controllers\LoginController@step02')->name('customer.check.step02');
Route::get('request/slide', 'Gametech\Wallet\Http\Controllers\SlideController@loadSlide')->name('customer.slide.load');

Route::get('cats/{id}', 'Gametech\Wallet\Http\Controllers\LoginController@cats')->defaults('_config', [
    'view' => 'wallet::customer.cats.show',
])->name('customer.cats.show_list');

Route::get('/list/public/{type}', 'Gametech\Wallet\Http\Controllers\LoginController@getProviders')->name('api.providers.public');
