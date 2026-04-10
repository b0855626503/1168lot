<?php

use Gametech\Admin\Models\Role;
use Gametech\Core\Models\BatchUser;
use Gametech\Core\Models\Config;
use Gametech\Core\Models\Coupon;
use Gametech\Core\Models\CouponList;
use Gametech\Core\Models\Faq;
use Gametech\Core\Models\Refer;
use Gametech\Core\Models\Spin;
use Gametech\Game\Models\Game;
use Gametech\Game\Models\GameSeamless;
use Gametech\Game\Models\GameType;
use Gametech\Game\Models\GameUser;
use Gametech\Game\Models\GameUserEvent;
use Gametech\Game\Models\GameUserFree;
use Gametech\Member\Models\Member;
use Gametech\Member\Models\MemberCashback;
use Gametech\Member\Models\MemberCreditFreeLog;
use Gametech\Member\Models\MemberCreditLog;
use Gametech\Member\Models\MemberIc;
use Gametech\Member\Models\MemberRemark;
use Gametech\Payment\Models\Bank;
use Gametech\Payment\Models\BankAccount;
use Gametech\Payment\Models\BankPayment;
use Gametech\Payment\Models\BankRule;
use Gametech\Payment\Models\Bill;
use Gametech\Payment\Models\Bonus;
use Gametech\Payment\Models\PaymentWaiting;
use Gametech\Payment\Models\Withdraw;
use Gametech\Promotion\Models\Promotion;
use Gametech\Promotion\Models\PromotionAmount;
use Gametech\Promotion\Models\PromotionContent;
use Gametech\Promotion\Models\PromotionTime;

return [

    /*
    |--------------------------------------------------------------------------
    | Disabling cache
    |--------------------------------------------------------------------------
    |
    | By setting this value to false, the cache will be disabled completely.
    | This may be useful for debugging purposes.
    |
    */
    'active' => env('LADA_CACHE_ACTIVE', true),

    /*
    |--------------------------------------------------------------------------
    | Redis prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be prepended to all items in Redis store.
    | Do not change this value in production, it will cause unexpected behavior.
    |
    */
    'prefix' => 'gametech:',

    /*
    |--------------------------------------------------------------------------
    | Expiration time
    |--------------------------------------------------------------------------
    |
    | By default, if this value is set to null, cached items will never expire.
    | If you are afraid of dead data or if you care about disk space, it may
    | be a good idea to set this value to something like 604800 (7 days).
    |
    */
    'expiration-time' => 600,

    /*
    |--------------------------------------------------------------------------
    | Cache granularity
    |--------------------------------------------------------------------------
    |
    | If you experience any issues while using the cache, try to set this value
    | to false. This will tell the cache to use a lower granularity and not
    | consider the row primary keys when creating the tags for a database query.
    | Since this will dramatically reduce the efficiency of the cache, it is
    | not recommended to do so in production environment.
    |
    */
    'consider-rows' => true,

    /*
    |--------------------------------------------------------------------------
    | Include tables
    |--------------------------------------------------------------------------
    |
    | If you want to cache only specific tables, put the table names into this
    | array. Then as soon as a query contains a table which is not specified in
    | here, it will not be cached. If you have this feature enabled, the value
    | of "exclude-tables" will be ignored and has no effect.
    |
    | Instead of hard coding table names in the configuration, it is a good
    | practice to initialize a new model instance and get the table name from
    | there like in the following example:
    |
    | 'include-tables' => [
    |     (new \App\Models\User())->getTable(),
    |     (new \App\Models\Post())->getTable(),
    | ],
    |
    */
    'include-tables' => [
        (new Config)->getTable(),
        (new Spin)->getTable(),
        (new BatchUser)->getTable(),
        (new Faq)->getTable(),
        (new Refer)->getTable(),
        (new Coupon)->getTable(),
        (new CouponList)->getTable(),
        (new Role)->getTable(),
        (new Game)->getTable(),
        (new GameSeamless)->getTable(),
        (new GameType)->getTable(),
        (new GameUserEvent)->getTable(),
        (new MemberCashback)->getTable(),
        (new MemberIc)->getTable(),
        (new MemberRemark)->getTable(),
        (new MemberCreditLog)->getTable(),
        (new MemberCreditFreeLog)->getTable(),
        (new Bank)->getTable(),
        (new BankAccount)->getTable(),
        (new BankPayment)->getTable(),
        (new BankRule)->getTable(),
        (new PaymentWaiting)->getTable(),
        (new Withdraw)->getTable(),
        //        (new \Gametech\Payment\Models\WithdrawFree())->getTable(),
        //        (new \Gametech\Payment\Models\WithdrawSeamless())->getTable(),
        //        (new \Gametech\Payment\Models\WithdrawSeamlessFree())->getTable(),
        (new Promotion)->getTable(),
        (new PromotionContent)->getTable(),
        (new PromotionAmount)->getTable(),
        (new PromotionTime)->getTable(),
        (new Bill)->getTable(),
        (new Bonus)->getTable(),

    ],

    /*
    |--------------------------------------------------------------------------
    | Exclude tables
    |--------------------------------------------------------------------------
    |
    | If you want to cache all tables but some specific ones, put them into this
    | array. As soon as a query contains at least one table specified in here, it
    | will not be cached.
    |
    */
    'exclude-tables' => [
        (new GameUser)->getTable(),
        (new GameUserFree)->getTable(),
        (new Member)->getTable(),
    ],

];
