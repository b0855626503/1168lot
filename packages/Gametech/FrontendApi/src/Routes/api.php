<?php

use Gametech\FrontendApi\Http\Controllers\Api\V1\AuthController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\ContactChannelController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\DepositController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\GameController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\LottoController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\MemberController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\OnlineController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\PromotionController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\RealtimeController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\SlideController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\WithdrawController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\WheelController;
use Gametech\FrontendApi\Http\Middleware\AuthenticateFrontendToken;
use Gametech\FrontendApi\Http\Middleware\ResolveFrontendLanguage;
use Illuminate\Support\Facades\Route;

$apiSubdomain = config('gametech.api_url') ?? config('app.admin_url');
$apiDomain = is_null(config('app.admin_domain_url'))
    ? config('app.domain_url')
    : config('app.admin_domain_url');

Route::domain($apiSubdomain . '.' . $apiDomain)
    ->prefix('api/v1')
    ->group(function () {
        Route::middleware(['api', ResolveFrontendLanguage::class])->group(function () {
            Route::get('auth/register/banks', [AuthController::class, 'registerBanks'])
                ->name('frontend.api.v1.auth.register.banks');
            Route::post('auth/register', [AuthController::class, 'register'])
                ->name('frontend.api.v1.auth.register');
            Route::post('auth/login', [AuthController::class, 'login'])
                ->name('frontend.api.v1.auth.login');

            Route::get('games/types', [GameController::class, 'types'])
                ->name('frontend.api.v1.games.types');
            Route::get('games/providers/{type}', [GameController::class, 'providers'])
                ->name('frontend.api.v1.games.providers');
            Route::get('games/{type}/{provider}', [GameController::class, 'games'])
                ->name('frontend.api.v1.games.list');
            Route::get('slides', [SlideController::class, 'list'])
                ->name('frontend.api.v1.slides.list');
            Route::get('meta/online-members', [OnlineController::class, 'count'])
                ->name('frontend.api.v1.meta.online_members');
            Route::get('meta/contact-channels', [ContactChannelController::class, 'list'])
                ->name('frontend.api.v1.meta.contact_channels');
            Route::get('realtime/config', [RealtimeController::class, 'config'])
                ->name('frontend.api.v1.realtime.config');

            Route::get('lotto/draws', [LottoController::class, 'draws'])
                ->name('frontend.api.v1.lotto.draws');
            Route::get('lotto/draws/{id}', [LottoController::class, 'draw'])
                ->name('frontend.api.v1.lotto.draw');
            Route::get('lotto/markets/latest', [LottoController::class, 'marketsLatestByGroup'])
                ->name('frontend.api.v1.lotto.markets.latest');
        });

        Route::middleware(['api', ResolveFrontendLanguage::class, AuthenticateFrontendToken::class])->group(function () {
            Route::post('auth/logout', [AuthController::class, 'logout'])
                ->name('frontend.api.v1.auth.logout');

            Route::get('member/profile', [MemberController::class, 'profile'])
                ->name('frontend.api.v1.member.profile');
            Route::get('member/balance', [MemberController::class, 'balance'])
                ->name('frontend.api.v1.member.balance');
            Route::get('member/loadbalance', [MemberController::class, 'loadBalance'])
                ->name('frontend.api.v1.member.loadbalance');
            Route::get('member/realtime-context', [RealtimeController::class, 'memberContext'])
                ->name('frontend.api.v1.member.realtime_context');
            Route::post('member/heartbeat', [OnlineController::class, 'heartbeat'])
                ->name('frontend.api.v1.member.heartbeat');
            Route::post('realtime/auth', [RealtimeController::class, 'authenticate'])
                ->name('frontend.api.v1.realtime.auth');

            Route::post('wallet/withdraw', [WithdrawController::class, 'store'])
                ->name('frontend.api.v1.wallet.withdraw');

            Route::get('deposit/channels', [DepositController::class, 'channels'])
                ->name('frontend.api.v1.deposit.channels');
            Route::post('deposit/loadbank', [DepositController::class, 'loadBank'])
                ->name('frontend.api.v1.deposit.loadbank');

            Route::get('promotion/list', [PromotionController::class, 'list'])
                ->name('frontend.api.v1.promotion.list');
            Route::post('promotion/select', [PromotionController::class, 'select'])
                ->name('frontend.api.v1.promotion.select');
            Route::post('promotion/deselect', [PromotionController::class, 'deselect'])
                ->name('frontend.api.v1.promotion.deselect');

            Route::post('games/login', [GameController::class, 'login'])
                ->name('frontend.api.v1.games.login');
            Route::get('games/login/{game}/{code}', [GameController::class, 'loginByPath'])
                ->name('frontend.api.v1.games.login.path');

            Route::post('lotto/bet', [LottoController::class, 'bet'])
                ->name('frontend.api.v1.lotto.bet');
            Route::get('lotto/tickets', [LottoController::class, 'tickets'])
                ->name('frontend.api.v1.lotto.tickets');
            Route::get('lotto/tickets/{id}', [LottoController::class, 'ticket'])
                ->name('frontend.api.v1.lotto.ticket');
            Route::post('lotto/tickets/{id}/cancel', [LottoController::class, 'cancel'])
                ->name('frontend.api.v1.lotto.cancel');

            Route::get('wheel/list', [WheelController::class, 'list'])
                ->name('frontend.api.v1.wheel.list');
            Route::post('wheel/spin', [WheelController::class, 'spin'])
                ->name('frontend.api.v1.wheel.spin');
            Route::get('wheel/history', [WheelController::class, 'history'])
                ->name('frontend.api.v1.wheel.history');
        });
    });
