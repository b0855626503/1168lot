<?php

use Gametech\FrontendApi\Http\Controllers\Api\V1\AuthController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\ContactChannelController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\CouponController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\DepositController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\FrontendThemeController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\GameController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\LottoController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\LottoNavbarConfigController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\LottoResultArchiveController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\LottoResultArchiveLegacyController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\MarketingClickController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\MemberController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\OnlineController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\PromotionController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\RealtimeController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\RewardController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\SiteMetaController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\SlideController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\WalletController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\WheelController;
use Gametech\FrontendApi\Http\Controllers\Api\V1\WithdrawController;
use Gametech\FrontendApi\Http\Middleware\AuthenticateFrontendToken;
use Gametech\FrontendApi\Http\Middleware\ResolveFrontendLanguage;
use Gametech\Payment\Http\Controllers\DeepPayController;
use Gametech\Payment\Http\Controllers\SmkPayController;
use Gametech\Payment\Http\Controllers\WealthPayController;
use Illuminate\Support\Facades\Route;

$apiSubdomain = config('gametech.api_url') ?? config('app.admin_url');
$apiDomain = is_null(config('app.admin_domain_url'))
    ? config('app.domain_url')
    : config('app.admin_domain_url');

Route::domain($apiSubdomain.'.'.$apiDomain)
    ->prefix('api/v1')
    ->group(function () {
        Route::middleware(['api', ResolveFrontendLanguage::class])->group(function () {
            Route::get('auth/register/banks', [AuthController::class, 'registerBanks'])
                ->name('frontend.api.v1.auth.register.banks');
            Route::post('auth/register/bank-account-name', [AuthController::class, 'resolveRegisterBankAccount'])
                ->name('frontend.api.v1.auth.register.bank_account_name');
            Route::post('auth/register', [AuthController::class, 'register'])
                ->name('frontend.api.v1.auth.register');
            Route::post('auth/register-with-username', [AuthController::class, 'registerWithUsername'])
                ->name('frontend.api.v1.auth.register_with_username');
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
            Route::get('meta/site', [SiteMetaController::class, 'info'])
                ->name('frontend.api.v1.meta.site');
            Route::get('theme', [FrontendThemeController::class, 'show'])
                ->name('frontend.api.v1.theme');
            Route::get('realtime/config', [RealtimeController::class, 'config'])
                ->name('frontend.api.v1.realtime.config');

            Route::get('lotto/draws', [LottoController::class, 'draws'])
                ->name('frontend.api.v1.lotto.draws');
            Route::get('lotto/draws/{id}', [LottoController::class, 'draw'])
                ->name('frontend.api.v1.lotto.draw');
            Route::get('lotto/markets/latest', [LottoController::class, 'marketsLatestByGroup'])
                ->name('frontend.api.v1.lotto.markets.latest');
            Route::get('lotto/markets/{marketId}/content', [LottoController::class, 'marketContent'])
                ->name('frontend.api.v1.lotto.market_content');
            Route::get('lotto/markets/{marketId}/betting-context', [LottoController::class, 'bettingContext'])
                ->name('frontend.api.v1.lotto.betting_context');
            Route::get('lotto/markets/{marketId}/results', [LottoResultArchiveLegacyController::class, 'marketResults'])
                ->name('frontend.api.v1.lotto.market_results');
            Route::get('lotto/markets/{marketId}/draws/{drawId}/result', [LottoController::class, 'drawResult'])
                ->name('frontend.api.v1.lotto.draw_result');
            Route::get('lotto/results/by-date', [LottoResultArchiveLegacyController::class, 'byDate'])
                ->name('frontend.api.v1.lotto.results_by_date');
            Route::get('lotto/navbar-config', [LottoNavbarConfigController::class, 'show'])
                ->name('frontend.api.v1.lotto.navbar_config');

            Route::middleware('throttle:60,1')->group(function (): void {
                Route::get('lotto/result-archive-legacy', [LottoResultArchiveLegacyController::class, 'index'])
                    ->name('frontend.api.v1.lotto.result_archive_legacy');
                Route::get('lotto/results', [LottoResultArchiveController::class, 'allMarkets'])
                    ->name('frontend.api.v1.lotto.results.archive.all');
                Route::get('lotto/results/{marketCode}', [LottoResultArchiveController::class, 'index'])
                    ->where('marketCode', '[A-Za-z0-9_-]+')
                    ->name('frontend.api.v1.lotto.results.archive.index');
                Route::get('lotto/results/{marketCode}/{drawDate}', [LottoResultArchiveController::class, 'show'])
                    ->where('marketCode', '[A-Za-z0-9_-]+')
                    ->where('drawDate', '\d{4}-\d{2}-\d{2}')
                    ->name('frontend.api.v1.lotto.results.archive.show');
                Route::get('lotto/results/{marketCode}/{drawDate}/{drawKey}', [LottoResultArchiveController::class, 'item'])
                    ->where('marketCode', '[A-Za-z0-9_-]+')
                    ->where('drawDate', '\d{4}-\d{2}-\d{2}')
                    ->where('drawKey', '[A-Za-z0-9_-]+')
                    ->name('frontend.api.v1.lotto.results.archive.item');
            });

            Route::post('marketing/clicks', [MarketingClickController::class, 'track'])
                ->middleware('throttle:60,1')
                ->name('frontend.api.v1.marketing.clicks.track');
            Route::post('marketing/clicks/{click_id}/confirm', [MarketingClickController::class, 'confirm'])
                ->middleware('throttle:60,1')
                ->whereNumber('click_id')
                ->name('frontend.api.v1.marketing.clicks.confirm');
            Route::post('marketing/clicks/{click_id}/submitted', [MarketingClickController::class, 'submitted'])
                ->middleware('throttle:60,1')
                ->whereNumber('click_id')
                ->name('frontend.api.v1.marketing.clicks.submitted');
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
            Route::post('member/change-password', [MemberController::class, 'changePassword'])
                ->name('frontend.api.v1.member.change_password');
            Route::post('member/wallet-address', [MemberController::class, 'updateWalletAddress'])
                ->name('frontend.api.v1.member.wallet_address');
            Route::get('member/contributor', [MemberController::class, 'contributor'])
                ->name('frontend.api.v1.member.contributor');
            Route::get('member/history', [MemberController::class, 'history'])
                ->name('frontend.api.v1.member.history');
            Route::get('member/history/{type}', [MemberController::class, 'history'])
                ->name('frontend.api.v1.member.history.type');
            Route::get('member/realtime-context', [RealtimeController::class, 'memberContext'])
                ->name('frontend.api.v1.member.realtime_context');
            Route::post('member/heartbeat', [OnlineController::class, 'heartbeat'])
                ->name('frontend.api.v1.member.heartbeat');
            Route::post('realtime/auth', [RealtimeController::class, 'authenticate'])
                ->name('frontend.api.v1.realtime.auth');

            Route::post('wallet/withdraw', [WithdrawController::class, 'store'])
                ->name('frontend.api.v1.wallet.withdraw');
            Route::post('wallet/claim', [WalletController::class, 'claim'])
                ->name('frontend.api.v1.wallet.claim');
            Route::get('wallet/transactions', [WalletController::class, 'transactions'])
                ->name('frontend.api.v1.wallet.transactions');
            Route::post('coupon/redeem', [CouponController::class, 'redeem'])
                ->name('frontend.api.v1.coupon.redeem');
            Route::get('coupon/my', [CouponController::class, 'myCoupons'])
                ->name('frontend.api.v1.coupon.my');
            Route::post('coupon/my/{code}/claim', [CouponController::class, 'claim'])
                ->name('frontend.api.v1.coupon.claim');

            Route::get('deposit/channels', [DepositController::class, 'channels'])
                ->name('frontend.api.v1.deposit.channels');
            Route::post('deposit/loadbank', [DepositController::class, 'loadBank'])
                ->name('frontend.api.v1.deposit.loadbank');
            Route::post('deposit/loadbank/random', [DepositController::class, 'loadRandomBank'])
                ->name('frontend.api.v1.deposit.loadbank.random');
            Route::get('smkpay/deposit/status/{txid}', [SmkPayController::class, 'checkStatus'])
                ->name('frontend.api.v1.smkpay.deposit.status');
            Route::post('smkpay/deposit/expire/{txid}', [SmkPayController::class, 'expire'])
                ->name('frontend.api.v1.smkpay.deposit.expire');
            Route::post('smkpay/deposit/create', [SmkPayController::class, 'deposit'])
                ->name('frontend.api.v1.smkpay.deposit');
            Route::get('smkpay/qrcode/{id}', [SmkPayController::class, 'index'])
                ->name('frontend.api.v1.smkpay.index');
            Route::post('deeppay/deposit/expire/{txid}', [DeepPayController::class, 'expire'])
                ->name('frontend.api.v1.deeppay.deposit.expire');
            Route::post('deeppay/deposit/create', [DeepPayController::class, 'deposit'])
                ->name('frontend.api.v1.deeppay.deposit');
            Route::get('deeppay/qrcode/{id}', [DeepPayController::class, 'index'])
                ->name('frontend.api.v1.deeppay.index');

            // ===== WEALTHPAY =====
            Route::get('wealthpay/deposit/status/{txid}', [WealthPayController::class, 'checkStatus'])
                ->name('frontend.api.v1.wealthpay.deposit.status');
            Route::post('wealthpay/deposit/expire/{txid}', [WealthPayController::class, 'expire'])
                ->name('frontend.api.v1.wealthpay.deposit.expire');
            Route::post('wealthpay/deposit/create', [WealthPayController::class, 'deposit'])
                ->name('frontend.api.v1.wealthpay.deposit');
            Route::get('wealthpay/qrcode/{id}', [WealthPayController::class, 'index'])
                ->name('frontend.api.v1.wealthpay.index');

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
            Route::get('lotto/groups/{groupId}/packages', [LottoController::class, 'packages'])
                ->name('frontend.api.v1.lotto.packages');
            Route::post('lotto/groups/{groupId}/select-package', [LottoController::class, 'selectPackage'])
                ->name('frontend.api.v1.lotto.select_package');
            Route::get('lotto/groups/{groupId}/selected-package', [LottoController::class, 'selectedPackage'])
                ->name('frontend.api.v1.lotto.selected_package');
            Route::get('lotto/tickets', [LottoController::class, 'tickets'])
                ->name('frontend.api.v1.lotto.tickets');
            Route::get('lotto/tickets/{id}', [LottoController::class, 'ticket'])
                ->name('frontend.api.v1.lotto.ticket');
            Route::post('lotto/tickets/{id}/cancel', [LottoController::class, 'cancel'])
                ->name('frontend.api.v1.lotto.cancel');
            Route::post('lotto/yeekee/rounds/{roundId}/shoot', [LottoController::class, 'submitShoot'])
                ->name('frontend.api.v1.lotto.yeekee.shoot');
            Route::get('lotto/yeekee/rounds', [LottoController::class, 'yeekeeRounds'])
                ->name('frontend.api.v1.lotto.yeekee.rounds.list');
            Route::get('lotto/yeekee/rounds/{roundId}', [LottoController::class, 'yeekeeRound'])
                ->name('frontend.api.v1.lotto.yeekee.round.detail');
            Route::get('lotto/yeekee/markets/{marketId}/current-round', [LottoController::class, 'yeekeeCurrentRound'])
                ->name('frontend.api.v1.lotto.yeekee.current_round');
            Route::get('lotto/yeekee/markets/{marketId}/rounds', [LottoController::class, 'yeekeeMarketRounds'])
                ->name('frontend.api.v1.lotto.yeekee.rounds');
            Route::get('lotto/yeekee/rounds/{roundId}/shoots', [LottoController::class, 'yeekeeShoots'])
                ->name('frontend.api.v1.lotto.yeekee.shoots');
            Route::get('lotto/yeekee/rounds/{roundId}/reward-status', [LottoController::class, 'yeekeeRewardStatus'])
                ->name('frontend.api.v1.lotto.yeekee.reward_status');
            Route::get('lotto/yeekee/rounds/{roundId}/result-proof', [LottoController::class, 'yeekeeResultProof'])
                ->name('frontend.api.v1.lotto.yeekee.result_proof');

            Route::get('wheel/list', [WheelController::class, 'list'])
                ->name('frontend.api.v1.wheel.list');
            Route::post('wheel/spin', [WheelController::class, 'spin'])
                ->name('frontend.api.v1.wheel.spin');
            Route::get('wheel/history', [WheelController::class, 'history'])
                ->name('frontend.api.v1.wheel.history');

            Route::get('reward/list', [RewardController::class, 'list'])
                ->name('frontend.api.v1.reward.list');
            Route::post('reward/redeem', [RewardController::class, 'redeem'])
                ->name('frontend.api.v1.reward.redeem');
            Route::get('reward/history', [RewardController::class, 'history'])
                ->name('frontend.api.v1.reward.history');
        });
    });
