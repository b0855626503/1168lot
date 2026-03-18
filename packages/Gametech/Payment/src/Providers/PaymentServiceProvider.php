<?php

namespace Gametech\Payment\Providers;

use Gametech\Payment\Models\AcledaProxy;
use Gametech\Payment\Models\BankAccountProxy;
use Gametech\Payment\Models\BankPaymentProxy;
use Gametech\Payment\Models\BillProxy;
use Gametech\Payment\Models\PaymentWaitingProxy;
use Gametech\Payment\Models\PaymentPromotionProxy;
use Gametech\Payment\Models\WithdrawFreeProxy;
use Gametech\Payment\Models\WithdrawProxy;
use Gametech\Payment\Models\WithdrawSeamlessFreeProxy;
use Gametech\Payment\Models\WithdrawSeamlessProxy;
use Gametech\Payment\Observers\AcledaObserver;
use Gametech\Payment\Observers\BankAccountObserver;
use Gametech\Payment\Observers\BankPaymentObserver;
use Gametech\Payment\Observers\BillObserver;
use Gametech\Payment\Observers\PaymentWaitingObserver;
use Gametech\Payment\Observers\PaymentPromotionObserver;
use Gametech\Payment\Observers\WithdrawFreeObserver;
use Gametech\Payment\Observers\WithdrawObserver;
use Gametech\Payment\Observers\WithdrawSeamlessFreeObserver;
use Gametech\Payment\Observers\WithdrawSeamlessObserver;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        WithdrawProxy::observe(WithdrawObserver::class);
        WithdrawSeamlessProxy::observe(WithdrawSeamlessObserver::class);
        WithdrawFreeProxy::observe(WithdrawFreeObserver::class);
        WithdrawSeamlessFreeProxy::observe(WithdrawSeamlessFreeObserver::class);
        BankPaymentProxy::observe(BankPaymentObserver::class);
        PaymentWaitingProxy::observe(PaymentWaitingObserver::class);
        BankAccountProxy::observe(BankAccountObserver::class);
        PaymentPromotionProxy::observe(PaymentPromotionObserver::class);
        BillProxy::observe(BillObserver::class);
//        AcledaProxy::observe(AcledaObserver::class);

        $this->loadRoutesFrom(__DIR__.'/../Routes/routes.php');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register() {

        foreach (glob(dirname(__DIR__) . '/Config/*.php') as $file) {
//            Log::debug($file);
            $name = pathinfo($file, PATHINFO_FILENAME);
            $this->mergeConfigFrom($file, $name);
        }

    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/admin-menu.php', 'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php', 'acl'
        );
    }
}
