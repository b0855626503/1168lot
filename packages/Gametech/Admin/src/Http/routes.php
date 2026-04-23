<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;

Route::domain(config('app.admin_url') . '.' . (is_null(config('app.admin_domain_url')) ? config('app.domain_url') : config('app.admin_domain_url')))->group(function () {


//Route::prefix('admin')->group(function () {


    Route::group(['middleware' => ['web'], 'namespace' => 'Gametech\Admin\Http\Controllers'], function () {

        Route::any('tw/{mobile}/webhook', 'WebhookController@index');
//        Route::get('tw/{mobile}/webhook', 'WebhookController@tw');
        Route::post('tw/{mobile}/webhooks', 'WebhookController@index_2');
        Route::post('tw/{mobile}/webhook1', 'WebhookController@index_king89slot');
        Route::post('tw/{mobile}/webhook2', 'WebhookController@index_grand999slot');
        Route::post('ttb/{mobile}/webhook', 'WebhookController@ttb');
        Route::post('ktb/{mobile}/webhook', 'WebhookController@ktb_app');
        Route::post('aba/{mobile}/webhook', 'WebhookController@aba');
        Route::post('wing/{mobile}/webhook', 'WebhookController@wing');
        Route::post('acleda/{mobile}/webhook', 'WebhookController@acleda');
        Route::post('scb/login', 'WebhookController@scb_login');
        Route::post('scb/{mobile}/webhook', 'WebhookController@scb');
        Route::post('scb/{mobile}/webhooks', 'WebhookController@scb_app');
        Route::post('scb/{mobile}/webhook_superrich', 'WebhookController@scb_superrich');
        Route::post('scb/{mobile}/webhook_superrich69', 'WebhookController@scb_superrich69');

        Route::get('texttest/webhook', 'WebhookController@texttest');
        Route::get('scb/{mobile}/webhook2', 'WebhookController@scb2');

        Route::get('payment/kissvips/create', 'WebhookController@kissvips_create');
        Route::get('payment/kissvips/update/{id}', 'WebhookController@kissvips_update');
        Route::get('payment/kissvips/webhook', 'WebhookController@kissvips_get_url');
        Route::post('payment/kissvips/webhook', 'WebhookController@kissvips_post_url');
        Route::post('payment/kissvips/callback', 'WebhookController@kissvips_callback_url');
        Route::get('payment/kissvips/bank', 'WebhookController@kissvips_bank');
        Route::get('payment/kissvips/transfer', 'WebhookController@kissvips_transfer');
        Route::get('payment/kissvips/balance', 'WebhookController@kissvips_balance');

        Route::get('payment/pompay/create', 'WebhookController@pompay_create')->defaults('_config', [
            'view' => 'admin::module.pompay.index',
        ]);

        Route::post('scb/webhook', 'WebhookController@scb_callback');

        Route::get('broadcast/{message}', 'TestController@TestBroadcast');


        Route::get('pompay/create', 'PomPayController@create')->defaults('_config', [
            'view' => 'admin::module.pompay.index',
        ]);
        Route::post('pompay/callback', 'PomPayController@callback');
        Route::get('pompay/callback', 'PomPayController@callback')->defaults('_config', [
            'view' => 'admin::module.pompay.callback',
        ]);
        Route::get('pompay/return', 'PomPayController@returns')->defaults('_config', [
            'view' => 'admin::module.pompay.callback',
        ]);

        Route::get('pompay/payout_create', 'PomPayController@payout_create')->defaults('_config', [
            'view' => 'admin::module.pompay.index',
        ]);
        Route::post('pompay/payout_callback', 'PomPayController@payout_callback');
        Route::get('pompay/check_payout', 'PomPayController@check_payout')->defaults('_config', [
            'view' => 'admin::module.pompay.index',
        ]);

        Route::get('/', 'Controller@redirectToLogin')->name('admin');

        Route::get('/getkbank', 'TestController@index')->name('admin.test.index');

        Route::get('/getscb', 'TestController@scb')->name('admin.test.scb');

        Route::get('/gettest', 'TestController@test')->name('admin.test.test');

        Route::get('/getktb', 'TestController@ktb')->name('admin.test.ktb');

        Route::get('/checkip', 'TestController@checkip')->name('admin.test.checkip');

        Route::get('check/kbank/{account}', 'TestController@KbankApi');

        Route::get('check/scb/{account}', 'TestController@ScbApi');

        Route::get('check/bay/{account}', 'TestController@BayApi');

        Route::get('test/kbank', 'TestController@kbank');
        Route::get('test/month', 'TestController@testMonth');
        Route::get('test/mongo', 'TestController@checkMango');
        Route::get('test/date', 'TestController@testDate');


        // Login Routes
        Route::get('/login', 'LoginController@show')->defaults('_config', [
            'view' => 'admin::auth.login',
        ])->name('admin.session.index');

        //login post route to admin auth controller
        Route::post('login', 'LoginController@login')->defaults('_config', [
            'redirect' => 'admin.2fa.validate',
        ])->name('admin.session.create');

        Route::prefix('auth')->group(function () {

            Route::get('/', 'Google2FAController@show2faForm')->defaults('_config', [
                'view' => 'admin::2fa.setting',
            ])->name('admin.2fa.setting')->middleware('auth');

            Route::post('/enable', 'Google2FAController@enable2fa')->name('admin.2fa.enable')->middleware('auth');

            Route::get('/activate', 'Google2FAController@reActivate')->name('admin.2fa.activate')->middleware('auth');

            Route::post('/', 'Google2FAController@index')->name('2fa')->middleware(['auth', '2fa']);


        });


        Route::group(['middleware' => ['admin', 'auth', '2fa']], function () {

            Route::post('pic/upload', 'ImgController@upload')->defaults('_config', [
                'view' => 'wallet::customer.game.redirect',
            ])->name('admin.upload.pic');

            Route::post('pic/delete/{id}', 'ImgController@delete')->defaults('_config', [
                'view' => 'wallet::customer.game.redirect',
            ])->name('admin.delete.pic');

            Route::post('broadcasting/auth', 'BroadcastController@authenticate')->name('auth.broadcast');

            Route::get('/confirm-password', function () {
                return view('admin::auth.passwords.confirm');
            })->middleware('auth')->name('password.confirm');

            Route::post('confirm-password', function (Request $request) {
                if (! Hash::check($request->password, $request->user()->password)) {
                    return back()->withErrors([
                        'password' => ['The provided password does not match our records.']
                    ]);
                }

                $request->session()->passwordConfirmed();

                return redirect()->intended();
            })->middleware(['auth', 'throttle:6,1']);

            Route::get('fix', 'FixController@index')->defaults('_config', [
                'view' => 'admin::module.fix.index',
            ])->name('admin.fix.index');


            Route::get('fix/optimize', 'FixController@optimize')->name('admin.fix.optimize');

            Route::get('chuba8', 'TestController@chuba')->name('admin.test.chuba');

            Route::get('fix/cashback/start', 'FixController@cashbackstart')->name('admin.fix.cashback.start');

            Route::get('fix/cashback/list/{date?}', 'FixController@cashback')->name('admin.fix.cashback');

            Route::get('fix/cashback/topup/{date?}', 'FixController@cashbackTopup')->name('admin.fix.cashbacktopup');

            Route::get('fix/cashback/deltopup/{date?}', 'FixController@cashbackDelTopup')->name('admin.fix.cashbackdeltopup');

            Route::get('fix/cashback/clear/{date}', 'FixController@cashbackClearTopup')->name('admin.fix.cashbackcleartopup');

            Route::get('fix/sum/today', 'FixController@sumtoday')->name('admin.fix.sumtoday');

            Route::get('fix/sum/yesterday', 'FixController@sumyesterday')->name('admin.fix.sumyesterday');

            Route::get('fix/ic/list', 'FixController@ic')->name('admin.fix.ic');

            Route::get('fix/ic/topup', 'FixController@icTopup')->name('admin.fix.ictopup');

            Route::get('fix/ic/deltopup/{date?}', 'FixController@icDelTopup')->name('admin.fix.icdeltopup');

            Route::get('fix/ic/clear/{date}', 'FixController@icClearTopup')->name('admin.fix.iccleartopup');

            Route::get('fix/refill', 'FixController@upspeed')->name('admin.fix.speed');

            Route::get('fix/bank', 'FixController@bank')->name('admin.fix.bank');

            Route::get('fix/member', 'FixController@fixmember')->name('admin.fix.member');

            Route::get('fix/payment', 'FixController@clearParment')->name('admin.fix.payment');

            Route::get('fix/bankname', 'FixController@bankname')->name('admin.fix.bankname');

            Route::get('fix/db', 'FixController@updb')->name('admin.fix.db');

            Route::get('fix/curl', 'FixController@twcurl')->name('admin.fix.curl');

            Route::get('fix/queue', 'FixController@queuerestart')->name('admin.fix.queuerestart');

            Route::get('fix/faststart/{id}', 'FixController@faststart')->name('admin.fix.faststart');

            Route::get('fix/payment', 'FixController@fixpayments')->name('admin.fix.payment');

//            Route::get('/cashback', 'CmdController@cashback')->name('admin.cashback.index');
//
//            Route::get('/ic', 'CmdController@ic')->name('admin.ic.index');

            Route::get('/update', 'CmdController@updatePatch')->name('admin.update.index');

            Route::get('/checkupdate', 'CmdController@checkPatch')->name('admin.checkupdate.index');

            Route::get('/noti', 'TestController@noti');

            Route::get('/alert', 'TestController@alert');

            Route::get('/chkbank', 'TestController@chkbank');

            Route::get('/kbank', 'TestController@kbank');



            Route::get('/test', 'TestController@test');

            Route::get('/test/sign/{id}', 'TestController@getsign');

            Route::get('/link', 'CmdController@storeLink');

            Route::get('/webservice/start', 'CmdController@webServiceStart');

            Route::get('/webservice/stop', 'CmdController@webServiceStop');

            Route::get('/optimize/clear', 'CmdController@optimizeClear');

            Route::get('/optimize', 'CmdController@optimize');

            Route::get('/optimize/view', 'CmdController@viewCmd');

            Route::get('/cache/clear', 'CmdController@cacheCmd');

            Route::get('/reset/pro', 'CmdController@resetPro')->name('admin.cmd.resetpro');

            Route::get('/reset/diamond', 'CmdController@resetDiamond')->name('admin.cmd.resetdiamond');

            Route::get('/reset/point', 'CmdController@resetPoint')->name('admin.cmd.resetpoint');

            Route::get('/ping', 'GameController@gameCheck');


            Route::get('/logout', 'LoginController@logout')->defaults('_config', [
                'redirect' => 'admin.session.index',
            ])->name('admin.session.destroy')->withoutMiddleware(['2fa']);

            // Dashboard Route
            Route::get('dashboard', 'DashboardController@index')->defaults('_config', [
                'view' => 'admin::module.dashboard.index',
            ])->name('admin.home.index');

            Route::get('docs/api/frontend-v1', 'ApiDocsController@frontendApiV1')->defaults('_config', [
                'view' => 'admin::module.docs.frontend_api_v1',
            ])->name('admin.docs.api.frontend_v1');
            Route::get('docs/api/frontend-v1/raw', 'ApiDocsController@frontendApiV1Raw')
                ->name('admin.docs.api.frontend_v1.raw');

            Route::get('docs/api/laravel-echo-nextjs-install', 'ApiDocsController@laravelEchoNextjsInstall')->defaults('_config', [
                'view' => 'admin::module.docs.frontend_api_v1',
            ])->name('admin.docs.api.laravel_echo_nextjs_install');
            Route::get('docs/api/laravel-echo-nextjs-install/raw', 'ApiDocsController@laravelEchoNextjsInstallRaw')
                ->name('admin.docs.api.laravel_echo_nextjs_install.raw');

            Route::get('loadcnt', 'DashboardController@loadCnt')->name('admin.home.loadcnt');
            Route::post('dashboard/edit', 'DashboardController@edit')->name('admin.dashboard.edit');
            Route::post('dashboard/loadsum', 'DashboardController@loadSum')->name('admin.dashboard.loadsum');
            Route::post('dashboard/loadsumall', 'DashboardController@loadSumAll')->name('admin.dashboard.loadsumall');
            Route::post('dashboard/loadbank', 'DashboardController@loadBank')->name('admin.dashboard.loadbank');
            Route::post('dashboard/loadlogin', 'DashboardController@loadLogin')->name('admin.dashboard.loadlogin');
            Route::post('dashboard/options', 'DashboardController@options')->name('admin.dashboard.options');
            Route::post('dashboard/summary', 'DashboardController@summary')->name('admin.dashboard.summary');
            Route::post('dashboard/conversion', 'DashboardController@conversion')->name('admin.dashboard.conversion');
            Route::post('dashboard/trends', 'DashboardController@trends')->name('admin.dashboard.trends');
            Route::post('dashboard/funnel', 'DashboardController@funnel')->name('admin.dashboard.funnel');
            Route::post('dashboard/activity', 'DashboardController@activity')->name('admin.dashboard.activity');
            Route::post('dashboard/alerts', 'DashboardController@alerts')->name('admin.dashboard.alerts');
            Route::post('dashboard/member-list', 'DashboardController@memberList')->name('admin.dashboard.member-list');
            Route::post('dashboard/sync-summary', 'DashboardController@syncSummary')->name('admin.dashboard.sync-summary');


            Route::get('rp_log_cashback', 'ReportController@rp_log_cashback')->defaults('_config', [
                'view' => 'admin::module.rp_log_cashback.index',
            ])->name('admin.rp_log_cashback.index');

            Route::get('rp_log_ic', 'ReportController@rp_log_ic')->defaults('_config', [
                'view' => 'admin::module.rp_log_ic.index',
            ])->name('admin.rp_log_ic.index');

            Route::get('rp_wallet', 'ReportController@rp_wallet')->defaults('_config', [
                'view' => 'admin::module.rp_wallet.index',
            ])->name('admin.rp_wallet.index');

            Route::get('rp_member_ref', 'ReportController@rp_member_ref')->defaults('_config', [
                'view' => 'admin::module.rp_member_ref.index',
            ])->name('admin.rp_member_ref.index');

            Route::post('rp_member_ref/loaddata', 'ReportController@loadDataRef')->name('admin.rp_member_ref.loaddata');

            Route::get('rp_member_edit', 'ReportController@rp_member_edit')->defaults('_config', [
                'view' => 'admin::module.rp_member_edit.index',
            ])->name('admin.rp_member_edit.index');

            Route::get('rp_credit', 'ReportController@rp_credit')->defaults('_config', [
                'view' => 'admin::module.rp_credit.index',
            ])->name('admin.rp_credit.index');

            Route::get('rp_credit_old', 'ReportController@rp_credit_old')->defaults('_config', [
                'view' => 'admin::module.rp_credit_old.index',
            ])->name('admin.rp_credit_old.index');

            Route::get('rp_bill', 'ReportController@rp_bill')->defaults('_config', [
                'view' => 'admin::module.rp_bill.index',
            ])->name('admin.rp_bill.index');

            Route::get('rp_top_promotion', 'ReportController@rp_top_promotion')->defaults('_config', [
                'view' => 'admin::module.rp_top_promotion.index',
            ])->name('admin.rp_top_promotion.index');

            Route::get('rp_online_behavior', 'ReportController@rp_online_behavior')->defaults('_config', [
                'view' => 'admin::module.rp_online_behavior.index',
            ])->name('admin.rp_online_behavior.index');

            Route::get('rp_bill_free', 'ReportController@rp_bill_free')->defaults('_config', [
                'view' => 'admin::module.rp_bill_free.index',
            ])->name('admin.rp_bill_free.index');

            Route::get('rp_user_log', 'ReportController@rp_user_log')->defaults('_config', [
                'view' => 'admin::module.rp_user_log.index',
            ])->name('admin.rp_user_log.index');

            Route::get('rp_staff_log', 'ReportController@rp_staff_log')->defaults('_config', [
                'view' => 'admin::module.rp_staff_log.index',
            ])->name('admin.rp_staff_log.index');


            Route::get('rp_deposit', 'ReportController@rp_deposit')->defaults('_config', [
                'view' => 'admin::module.rp_deposit.index',
            ])->name('admin.rp_deposit.index');

            Route::get('rp_deposit/export', 'RpDepositController@export')->name('admin.rp_deposit.export');


            Route::post('rp_deposit/edit', 'RpDepositController@edit')->name('admin.rp_deposit.edit');

            Route::post('rp_deposit/delete', 'BankinController@destroy')->name('admin.rp_deposit.delete');



            Route::get('rp_withdraw', 'ReportController@rp_withdraw')->defaults('_config', [
                'view' => 'admin::module.rp_withdraw.index',
            ])->name('admin.rp_withdraw.index');


            Route::post('rp_withdraw/delete', 'BankoutController@destroy')->name('admin.rp_withdraw.delete');


            Route::get('rp_withdraw_seamless', 'ReportController@rp_withdraw_seamless')->defaults('_config', [
                'view' => 'admin::module.rp_withdraw_seamless.index',
            ])->name('admin.rp_withdraw_seamless.index');

            Route::get('rp_withdraw_seamless_free', 'ReportController@rp_withdraw_seamless_free')->defaults('_config', [
                'view' => 'admin::module.rp_withdraw_seamless_free.index',
            ])->name('admin.rp_withdraw_seamless_free.index');

            Route::get('rp_withdraw_free', 'ReportController@rp_withdraw_free')->defaults('_config', [
                'view' => 'admin::module.rp_withdraw_free.index',
            ])->name('admin.rp_withdraw_free.index');

            Route::get('rp_spin', 'ReportController@rp_spin')->defaults('_config', [
                'view' => 'admin::module.rp_spin.index',
            ])->name('admin.rp_spin.index');

            Route::get('rp_billturn', 'ReportController@rp_billturn')->defaults('_config', [
                'view' => 'admin::module.rp_billturn.index',
            ])->name('admin.rp_billturn.index');

            Route::get('rp_cashback', 'ReportController@rp_cashback')->defaults('_config', [
                'view' => 'admin::module.rp_cashback.index',
            ])->name('admin.rp_cashback.index');

            Route::post('rp_cashback', 'CashbackICController@Cashback')->defaults('_config', [
                'view' => 'admin::module.rp_cashback.index',
            ])->name('admin.rp_cashback.store');

            Route::get('rp_member_ic', 'ReportController@rp_member_ic')->defaults('_config', [
                'view' => 'admin::module.rp_member_ic.index',
            ])->name('admin.rp_member_ic.index');

            Route::post('rp_member_ic', 'CashbackICController@MemberIC')->defaults('_config', [
                'view' => 'admin::module.rp_member_ic.index',
            ])->name('admin.rp_member_ic.store');

            Route::get('rp_sponsor', 'ReportController@rp_sponsor')->defaults('_config', [
                'view' => 'admin::module.rp_sponsor.index',
            ])->name('admin.rp_sponsor.index');

            Route::get('rp_recommender', 'ReportController@rp_recommender')->defaults('_config', [
                'view' => 'admin::module.rp_recommender.index',
            ])->name('admin.rp_recommender.index');

            Route::get('rp_reward_point', 'ReportController@rp_sponsor')->defaults('_config', [
                'view' => 'admin::module.rp_sponsor.index',
            ])->name('admin.rp_sponsor.index');

            Route::get('rp_alllog', 'ReportController@rp_alllog')->defaults('_config', [
                'view' => 'admin::module.rp_alllog.index',
            ])->name('admin.rp_alllog.index');

            Route::get('rp_alllog_free', 'ReportController@rp_alllog_free')->defaults('_config', [
                'view' => 'admin::module.rp_alllog_free.index',
            ])->name('admin.rp_alllog_free.index');

            Route::get('rp_sum_game', 'ReportController@rp_sum_game')->defaults('_config', [
                'view' => 'admin::module.rp_sum_game.index',
            ])->name('admin.rp_sum_game.index');

            Route::get('rp_sm_payment', 'ReportController@rp_sm_payment')->defaults('_config', [
                'view' => 'admin::module.rp_summary.index',
            ])->name('admin.rp_summary.index');

            Route::get('rp_sm_withdraw', 'ReportController@rp_sm_withdraw')->defaults('_config', [
                'view' => 'admin::module.rp_sm_withdraw.index',
            ])->name('admin.rp_sm_withdraw.index');

            Route::get('rp_sm_withdraw_seamless', 'ReportController@rp_sm_withdraw_seamless')->defaults('_config', [
                'view' => 'admin::module.rp_sm_withdraw_seamless.index',
            ])->name('admin.rp_sm_withdraw_seamless.index');

            Route::get('rp_sm_setwallet', 'ReportController@rp_sm_setwallet')->defaults('_config', [
                'view' => 'admin::module.rp_sm_setwallet.index',
            ])->name('admin.rp_sm_setwallet.index');

            Route::get('rp_sm_log', 'ReportController@rp_sm_log')->defaults('_config', [
                'view' => 'admin::module.rp_sm_log.index',
            ])->name('admin.rp_sm_log.index');

            Route::get('rp_no_refill', 'ReportController@rp_no_refill')->defaults('_config', [
                'view' => 'admin::module.rp_no_refill.index',
            ])->name('admin.rp_no_refill.index');

            Route::get('rp_first_time', 'ReportController@rp_first_time')->defaults('_config', [
                'view' => 'admin::module.rp_first_time.index',
            ])->name('admin.rp_first_time.index');

            Route::get('rp_member_pro', 'ReportController@rp_member_pro')->defaults('_config', [
                'view' => 'admin::module.rp_member_pro.index',
            ])->name('admin.rp_member_pro.index');


            Route::get('rp_top_payment', 'ReportController@rp_top_payment')->defaults('_config', [
                'view' => 'admin::module.rp_top_payment.index',
            ])->name('admin.rp_top_payment.index');

            Route::get('rp_top_withdraw', 'ReportController@rp_top_withdraw')->defaults('_config', [
                'view' => 'admin::module.rp_top_withdraw.index',
            ])->name('admin.rp_top_withdraw.index');

            Route::get('rp_sum_stat', 'ReportController@rp_sum_stat')->defaults('_config', [
                'view' => 'admin::module.rp_sum_stat.index',
            ])->name('admin.rp_sum_stat.index');

            Route::post('rp_sum_stat/loaddata', 'ReportController@loadData')->name('admin.rp_sum_stat.loaddata');


            Route::get('rp_sum_payment', 'ReportController@rp_sum_payment')->defaults('_config', [
                'view' => 'admin::module.rp_sum_payment.index',
            ])->name('admin.rp_sum_payment.index');

            Route::get('rp_setpoint', 'ReportController@rp_setpoint')->defaults('_config', [
                'view' => 'admin::module.rp_setpoint.index',
            ])->name('admin.rp_setpoint.index');

            Route::get('rp_setdiamond', 'ReportController@rp_setdiamond')->defaults('_config', [
                'view' => 'admin::module.rp_setdiamond.index',
            ])->name('admin.rp_setdiamond.index');

            Route::get('rp_log', 'ReportController@rp_log')->defaults('_config', [
                'view' => 'admin::module.rp_log.index',
            ])->name('admin.rp_log.index');

            Route::get('member_log', 'MemberLogController@index')->defaults('_config', [
                'view' => 'admin::module.member_log.index',
            ])->name('admin.member_log.index');

            Route::get('transaction', 'ReportController@transaction')->defaults('_config', [
                'view' => 'admin::module.transaction.index',
            ])->name('admin.transaction.index');


            $route = ['name' => 'bank', 'controller' => 'BankController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadgame', $route['controller'] . '@loadGame')->name('admin.' . $route['name'] . '.loadgame');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'bank_rule', 'controller' => 'BankRuleController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loadbank', $route['controller'] . '@loadBank')->name('admin.' . $route['name'] . '.loadbank');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadgame', $route['controller'] . '@loadGame')->name('admin.' . $route['name'] . '.loadgame');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });


            $route = ['name' => 'refer', 'controller' => 'ReferController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadgame', $route['controller'] . '@loadGame')->name('admin.' . $route['name'] . '.loadgame');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'spin', 'controller' => 'SpinController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadgame', $route['controller'] . '@loadGame')->name('admin.' . $route['name'] . '.loadgame');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });


            $route = ['name' => 'confirm_wallet', 'controller' => 'ConfirmwalletController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadgame', $route['controller'] . '@loadGame')->name('admin.' . $route['name'] . '.loadgame');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });


            $route = ['name' => 'employees', 'controller' => 'AdminController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadrole', $route['controller'] . '@loadRole')->name('admin.' . $route['name'] . '.loadrole');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

	        $route = ['name' => 'check_case', 'controller' => 'CheckCaseController'];
	        Route::group(['prefix' => $route['name']], function () use ($route) {
		        Route::get('/', $route['controller'].'@index')->defaults('_config', [
			        'view' => 'admin::module.'.$route['name'].'.index',
		        ])->name('admin.'.$route['name'].'.index');

	        });


            $route = ['name' => 'roles', 'controller' => 'RoleController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadgame', $route['controller'] . '@loadGame')->name('admin.' . $route['name'] . '.loadgame');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'announces', 'controller' => 'AnnounceController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadgame', $route['controller'] . '@loadGame')->name('admin.' . $route['name'] . '.loadgame');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->defaults('_config', [
                    'redirect' => 'admin.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });


            $route = ['name' => 'batch_user', 'controller' => 'BatchUserController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadgame', $route['controller'] . '@loadGame')->name('admin.' . $route['name'] . '.loadgame');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'setting', 'controller' => 'ConfigController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::get('getrule', $route['controller'] . '@getrule')->name('admin.' . $route['name'] . '.getrule');

                Route::post('loadbank', $route['controller'] . '@loadBank')->name('admin.' . $route['name'] . '.loadbank');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });


            $route = ['name' => 'withdraw', 'controller' => 'WithdrawController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loaduser', $route['controller'] . '@loadUser')->name('admin.' . $route['name'] . '.loaduser');

                Route::post('loadbank', $route['controller'] . '@loadBank')->name('admin.' . $route['name'] . '.loadbank');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

                Route::post('fix', $route['controller'] . '@fixSubmit')->name('admin.' . $route['name'] . '.fix');

            });

//            $route = ['name' => 'freegame', 'controller' => 'FreeGameController'];
//            Route::group(['prefix' => $route['name']], function () use ($route) {
//                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
//                    'view' => 'admin::module.' . $route['name'] . '.index',
//                ])->name('admin.' . $route['name'] . '.index');
//
//                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');
//
//                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');
//
//                Route::post('loadProduct', $route['controller'] . '@loadProduct')->name('admin.' . $route['name'] . '.loadProduct');
//
//                Route::post('loadGame', $route['controller'] . '@loadGame')->name('admin.' . $route['name'] . '.loadGame');
//
//                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');
//
//                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');
//
//                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');
//
//                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');
//
//                Route::post('fix', $route['controller'] . '@fixSubmit')->name('admin.' . $route['name'] . '.fix');
//
//            });


            $route = ['name' => 'withdraw_free', 'controller' => 'WithdrawfreeController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadbank', $route['controller'] . '@loadBank')->name('admin.' . $route['name'] . '.loadbank');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

                Route::post('fix', $route['controller'] . '@fixSubmit')->name('admin.' . $route['name'] . '.fix');

            });

            $route = ['name' => 'member', 'controller' => 'MemberController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {

                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

//                Route::post('sample/export', 'SampleController@index');
//                Route::get('export', $route['controller'] . '@export')->name('admin.' . $route['name'] . '.export');
                Route::get('export', $route['controller'] . '@export')->name('admin.' . $route['name'] . '.export');

//                Route::post('export', $route['controller'] . '@export');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('createsub', $route['controller'] . '@createsub')->name('admin.' . $route['name'] . '.createsub');

                Route::get('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::get('loadbank', $route['controller'] . '@loadBank')->name('admin.' . $route['name'] . '.loadbank');

                Route::get('loadaf', $route['controller'] . '@loadAf')->name('admin.' . $route['name'] . '.loadaf');

                Route::get('loadrefer', $route['controller'] . '@loadRefer')->name('admin.' . $route['name'] . '.loadrefer');

                Route::get('loadbankaccount', $route['controller'] . '@loadBankAccount')->name('admin.' . $route['name'] . '.loadbankaccount');

                Route::get('gamelog', $route['controller'] . '@gameLog')->name('admin.' . $route['name'] . '.gamelog');

                Route::get('balance', $route['controller'] . '@balance')->name('admin.' . $route['name'] . '.balance');

                Route::get('remark', $route['controller'] . '@remark')->name('admin.' . $route['name'] . '.remark');

                Route::post('setwallet', $route['controller'] . '@setWallet')->name('admin.' . $route['name'] . '.setwallet');

                Route::post('setpoint', $route['controller'] . '@setPoint')->name('admin.' . $route['name'] . '.setpoint');

                Route::post('setdiamond', $route['controller'] . '@setDiamond')->name('admin.' . $route['name'] . '.setdiamond');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('editsub', $route['controller'] . '@editsub')->name('admin.' . $route['name'] . '.editsub');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

                Route::post('deletesub', $route['controller'] . '@destroysub')->name('admin.' . $route['name'] . '.deletesub');

                Route::post('changegamepass', $route['controller'] . '@changegamepass')->name('admin.' . $route['name'] . '.changegamepass');

                Route::post('addevent', $route['controller'] . '@addEvent')->name('admin.' . $route['name'] . '.addevent');

                Route::get('loadevents', $route['controller'] . '@loadEvents')->name('admin.' . $route['name'] . '.loadevents');

                Route::post('refill', $route['controller'] . '@refill')->name('admin.' . $route['name'] . '.refill');

            });

            $route = ['name' => 'member_free', 'controller' => 'MemberfreeController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::get('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::get('loadbank', $route['controller'] . '@loadBank')->name('admin.' . $route['name'] . '.loadbank');

                Route::get('loadbankaccount', $route['controller'] . '@loadBankAccount')->name('admin.' . $route['name'] . '.loadbankaccount');

                Route::get('gamelog', $route['controller'] . '@gameLog')->name('admin.' . $route['name'] . '.gamelog');

                Route::get('balance', $route['controller'] . '@balance')->name('admin.' . $route['name'] . '.balance');


                Route::post('setwallet', $route['controller'] . '@setWallet')->name('admin.' . $route['name'] . '.setwallet');

                Route::post('setpoint', $route['controller'] . '@setPoint')->name('admin.' . $route['name'] . '.setpoint');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('editsub', $route['controller'] . '@editsub')->name('admin.' . $route['name'] . '.editsub');


                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'member_confirm', 'controller' => 'MemberConfirmController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'bank_in', 'controller' => 'BankinController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

                Route::post('approve', $route['controller'] . '@approve')->name('admin.' . $route['name'] . '.approve');

            });

            $route = ['name' => 'bank_in_old', 'controller' => 'BankinOldController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

                Route::post('approve', $route['controller'] . '@approve')->name('admin.' . $route['name'] . '.approve');

            });

            $route = ['name' => 'game_user', 'controller' => 'GameUserController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::get('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::get('lastpayment', $route['controller'] . '@lastPayment')->name('admin.' . $route['name'] . '.lastpayment');

                Route::post('loadpromotion', $route['controller'] . '@loadPromotion')->name('admin.' . $route['name'] . '.loadpromotion');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'game_user_free', 'controller' => 'GameUserFreeController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::get('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'bank_out', 'controller' => 'BankoutController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'bank_account_in', 'controller' => 'BankAccountInController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadbank', $route['controller'] . '@loadBank')->name('admin.' . $route['name'] . '.loadbank');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'bank_account_out', 'controller' => 'BankAccountOutController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadbank', $route['controller'] . '@loadBank')->name('admin.' . $route['name'] . '.loadbank');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });


            $route = ['name' => 'faq', 'controller' => 'FaqController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'contact_channel', 'controller' => 'ContactChannelController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'].'@index')->defaults('_config', [
                    'view' => 'admin::module.'.$route['name'].'.index',
                ])->name('admin.'.$route['name'].'.index');

                Route::post('create', $route['controller'].'@create')->name('admin.'.$route['name'].'.create');

                Route::post('loaddata', $route['controller'].'@loadData')->name('admin.'.$route['name'].'.loaddata');

                Route::post('loadgame', $route['controller'].'@loadGame')->name('admin.'.$route['name'].'.loadgame');

                Route::post('edit', $route['controller'].'@edit')->name('admin.'.$route['name'].'.edit');

                Route::post('clear', $route['controller'].'@clear')->name('admin.'.$route['name'].'.clear');

                Route::post('update/{id?}', $route['controller'].'@update')->name('admin.'.$route['name'].'.update');

                Route::post('delete', $route['controller'].'@destroy')->name('admin.'.$route['name'].'.delete');

            });

            $route = ['name' => 'notice', 'controller' => 'NoticeController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'notice_new', 'controller' => 'NoticeNewController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'game', 'controller' => 'GameController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loaddebug', $route['controller'] . '@loadDebug')->name('admin.' . $route['name'] . '.loaddebug');

                Route::post('loaddebugfree', $route['controller'] . '@loadDebugFree')->name('admin.' . $route['name'] . '.loaddebugfree');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });


            $route = ['name' => 'game_type', 'controller' => 'GameTypeController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loaddebug', $route['controller'] . '@loadDebug')->name('admin.' . $route['name'] . '.loaddebug');

                Route::post('loaddebugfree', $route['controller'] . '@loadDebugFree')->name('admin.' . $route['name'] . '.loaddebugfree');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'game_seamless', 'controller' => 'GameSeamlessController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadBetLimit', $route['controller'] . '@loadBetLimit')->name('admin.' . $route['name'] . '.loadBetLimit');

                Route::post('loaddebug', $route['controller'] . '@loadDebug')->name('admin.' . $route['name'] . '.loaddebug');

                Route::post('loaddebugfree', $route['controller'] . '@loadDebugFree')->name('admin.' . $route['name'] . '.loaddebugfree');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'game_list', 'controller' => 'GameListController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loaddebug', $route['controller'] . '@loadDebug')->name('admin.' . $route['name'] . '.loaddebug');

                Route::post('loaddebugfree', $route['controller'] . '@loadDebugFree')->name('admin.' . $route['name'] . '.loaddebugfree');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'game_single', 'controller' => 'GameSingleController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadprovider', $route['controller'] . '@loadProvider')->name('admin.' . $route['name'] . '.loadprovider');

                Route::post('loaddebug', $route['controller'] . '@loadDebug')->name('admin.' . $route['name'] . '.loaddebug');

                Route::post('loaddebugfree', $route['controller'] . '@loadDebugFree')->name('admin.' . $route['name'] . '.loaddebugfree');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'gamelog', 'controller' => 'GameLogController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {

                Route::get('/seamless', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::get('/local', $route['controller'] . '@local')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . 'data.index',
                ])->name('admin.' . $route['name'] . '.local');

            });


            $route = ['name' => 'promotion', 'controller' => 'PromotionController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('createsub', $route['controller'] . '@createsub')->name('admin.' . $route['name'] . '.createsub');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadpro', $route['controller'] . '@loadPro')->name('admin.' . $route['name'] . '.loadpro');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

                Route::post('deletesub', $route['controller'] . '@destroysub')->name('admin.' . $route['name'] . '.deletesub');

                Route::post('selectpromotion', $route['controller'] . '@selectPromotion')->name('admin.' . $route['name'] . '.selectpromotion');

                Route::post('deselectpromotion', $route['controller'] . '@deselectPromotion')->name('admin.' . $route['name'] . '.deselectpromotion');

                Route::post('loadpromotion', $route['controller'] . '@loadPromotion')->name('admin.' . $route['name'] . '.loadpromotion');

                Route::post('applyretro', $route['controller'] . '@applyRetro')->name('admin.' . $route['name'] . '.applyretro');

            });

            $route = ['name' => 'pro_content', 'controller' => 'PromotionContentController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadpro', $route['controller'] . '@loadPro')->name('admin.' . $route['name'] . '.loadpro');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'reward', 'controller' => 'RewardController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadgame', $route['controller'] . '@loadGame')->name('admin.' . $route['name'] . '.loadgame');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'rp_reward_point', 'controller' => 'RewardPointController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });


            $route = ['name' => 'payment', 'controller' => 'PaymentController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });

            $route = ['name' => 'slide', 'controller' => 'SlideController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadpro', $route['controller'] . '@loadPro')->name('admin.' . $route['name'] . '.loadpro');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

            });


            $route = ['name' => 'withdraw_new', 'controller' => 'WithdrawNewController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('refill', $route['controller'] . '@refill')->name('admin.' . $route['name'] . '.refill');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('loadbank', $route['controller'] . '@loadBank')->name('admin.' . $route['name'] . '.loadbank');

                Route::post('loadbanks', $route['controller'] . '@loadBanks')->name('admin.' . $route['name'] . '.loadbanks');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('clear', $route['controller'] . '@clear')->name('admin.' . $route['name'] . '.clear');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

                Route::post('fix', $route['controller'] . '@fixSubmit')->name('admin.' . $route['name'] . '.fix');

            });

            $route = ['name' => 'coupon', 'controller' => 'CouponController'];
            Route::group(['prefix' => $route['name']], function () use ($route) {
                Route::get('/', $route['controller'] . '@index')->defaults('_config', [
                    'view' => 'admin::module.' . $route['name'] . '.index',
                ])->name('admin.' . $route['name'] . '.index');

                Route::post('create', $route['controller'] . '@create')->name('admin.' . $route['name'] . '.create');

                Route::post('loaddata', $route['controller'] . '@loadData')->name('admin.' . $route['name'] . '.loaddata');

                Route::post('edit', $route['controller'] . '@edit')->name('admin.' . $route['name'] . '.edit');

                Route::post('update/{id?}', $route['controller'] . '@update')->name('admin.' . $route['name'] . '.update');

                Route::post('delete', $route['controller'] . '@destroy')->name('admin.' . $route['name'] . '.delete');

                Route::post('gen', $route['controller'] . '@gen')->name('admin.' . $route['name'] . '.gen');

                Route::get('couponlist', $route['controller'] . '@couponlist')->name('admin.' . $route['name'] . '.couponlist');


            });


        });


    });

//    });

});
