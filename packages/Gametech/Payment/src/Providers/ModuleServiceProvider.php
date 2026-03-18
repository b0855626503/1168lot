<?php

namespace Gametech\Payment\Providers;

use Gametech\Payment\Models\Acleda;
use Gametech\Payment\Models\Bank;
use Gametech\Payment\Models\BankAccount;
use Gametech\Payment\Models\BankHengpay;
use Gametech\Payment\Models\BankPayment;
use Gametech\Payment\Models\BankRule;
use Gametech\Payment\Models\Bill;
use Gametech\Payment\Models\BillFree;
use Gametech\Payment\Models\Bonus;
use Gametech\Payment\Models\BonusSpin;
use Gametech\Payment\Models\Payment;
use Gametech\Payment\Models\PaymentLog;
use Gametech\Payment\Models\PaymentLogFree;
use Gametech\Payment\Models\PaymentPromotion;
use Gametech\Payment\Models\PaymentWaiting;
use Gametech\Payment\Models\Withdraw;
use Gametech\Payment\Models\WithdrawDetail;
use Gametech\Payment\Models\WithdrawFree;
use Gametech\Payment\Models\WithdrawNew;
use Gametech\Payment\Models\WithdrawSeamless;
use Gametech\Payment\Models\WithdrawSeamlessFree;
use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        Bank::class,
        BankAccount::class,
        BankPayment::class,
        Bill::class,
        BillFree::class,
        Bonus::class,
        BonusSpin::class,
        Payment::class,
        PaymentLog::class,
        PaymentLogFree::class,
        PaymentPromotion::class,
        PaymentWaiting::class,
        Withdraw::class,
        WithdrawFree::class,
        BankRule::class,
        WithdrawDetail::class,
        WithdrawSeamless::class,
        WithdrawSeamlessFree::class,
        WithdrawNew::class,
        BankHengpay::class,
        Acleda::class,
    ];
}
