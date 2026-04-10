<?php

use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\RouteServiceProvider;
use Collective\Html\FormFacade;
use Collective\Html\HtmlFacade;
use Gametech\Admin\Providers\AdminServiceProvider;
use Gametech\API\Providers\APIServiceProvider;
use Gametech\CenterOA\Providers\CenterOAServiceProvider;
use Gametech\Core\Facades\Core;
use Gametech\Core\Providers\CoreServiceProvider;
use Gametech\FrontendApi\Providers\FrontendApiServiceProvider;
use Gametech\Game\Providers\GameServiceProvider;
use Gametech\LineOA\Providers\LineOAServiceProvider;
use Gametech\LogAdmin\Providers\LogAdminServiceProvider;
use Gametech\LogUser\Providers\LogUserServiceProvider;
use Gametech\Lotto\Providers\LottoServiceProvider;
use Gametech\Marketing\Providers\MarketingServiceProvider;
use Gametech\Member\Providers\MemberServiceProvider;
use Gametech\Payment\Providers\PaymentServiceProvider;
use Gametech\Promotion\Providers\PromotionServiceProvider;
use Gametech\Reward\Providers\RewardServiceProvider;
use Gametech\Ui\Providers\UiServiceProvider;
use Gametech\Wallet\Providers\WalletServiceProvider;
use Illuminate\Auth\AuthServiceProvider;
use Illuminate\Broadcasting\BroadcastServiceProvider;
use Illuminate\Bus\BusServiceProvider;
use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Cookie\CookieServiceProvider;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\EncryptionServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Providers\ConsoleSupportServiceProvider;
use Illuminate\Foundation\Providers\FoundationServiceProvider;
use Illuminate\Hashing\HashServiceProvider;
use Illuminate\Notifications\NotificationServiceProvider;
use Illuminate\Pipeline\PipelineServiceProvider;
use Illuminate\Queue\QueueServiceProvider;
use Illuminate\Redis\RedisServiceProvider;
use Illuminate\Session\SessionServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\ViewServiceProvider;
use Konekt\Concord\ConcordServiceProvider;
use Konekt\Concord\Facades\Concord;
use Konekt\Concord\Facades\Helper;
use Maatwebsite\Excel\ExcelServiceProvider;
use Maatwebsite\Excel\Facades\Excel;
use MongoDB\Laravel\MongoDBServiceProvider;
use PragmaRX\Google2FALaravel\Facade;
use PragmaRX\Google2FALaravel\ServiceProvider;
use Prettus\Repository\Providers\RepositoryServiceProvider;
use Rap2hpoutre\FastExcel\Facades\FastExcel;
use Yajra\DataTables\DataTablesServiceProvider;
use Yajra\DataTables\Facades\DataTables;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    'checkfaststart' => env('CHECKFASTSTART', false),

    'checkpromotion' => env('CHECKPROMOTION', false),

    'pompay_url_payment' => env('POMPAY_URL_PAYMENT', 'https://pompay.asia/payment'),

    'pompay_url_payout' => env('POMPAY_URL_PAYOUT', 'https://pompay.asia/v2/payout'),

    'pompay_clientId' => env('POMPAY_CLIENTID', ''),

    'pompay_clientSecret' => env('POMPAY_CLIENTSECRET', ''),

    'hengpay_secret' => env('HENGPAY_SECRET', null),

    'hengpay_shop' => env('HENGPAY_SHOP', null),

    'luckypay_private' => env('LUCKYPAY_PRIVATE', null),

    'luckypay_client' => env('LUCKYPAY_CLIENT', null),

    'luckypay_url' => env('LUCKYPAY_URL', null),

    'papayapay_url' => env('PAPAYAPAY_URL', null),

    'papayapay_token' => env('PAPAYAPAY_TOKEN', null),

    'superrich_user' => env('SUPERRICH_USER', null),

    'superrich_apikey' => env('SUPERRICH_KEY', null),

    'superrich_secertkey' => env('SUPERRICH_SECERT', null),

    'superrich_apiurl' => env('SUPERRICH_URL', null),

    'ezpay_apiurl' => env('EZPAY_URL', null),

    'ezpay_secertkey' => env('EZPAY_SECERTKEY', null),

    'ezpay_merid' => env('EZPAY_MERID', null),

    'ezpay_subid' => env('EZPAY_SUBID', null),

    'ezpay_hash' => env('EZPAY_HASH', null),

    'commspay_merchant_code' => env('COMMSPAY_MERCHANT_CODE', null),

    'commspay_api_url' => env('COMMSPAY_API_URL', null),

    'commspay_api_key' => env('COMMSPAY_API_KEY', null),

    'commspay_secret_key' => env('COMMSPAY_SECERT_KEY', null),

    'commspay_payment_code' => env('COMMSPAY_PAYMENT_CODE', null),

    'commspay_payout_code' => env('COMMSPAY_PAYOUT_CODE', null),

    'commspay_settlement_code' => env('COMMSPAY_SETTLEMENT_CODE', null),

    'binance_url' => env('BINANCE_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL', null),

    'admin_url' => env('APP_ADMIN_URL', 'admin'),

    'user_url' => env('APP_USER_URL', 'wallet'),

    'mix_url' => env('MIX_ASSET_URL', null),

    'domain_url' => env('APP_DOMAIN_URL', null),

    'admin_domain_url' => env('APP_ADMIN_DOMAIN_URL', null),

    'api_domain_url' => env('APP_API_DOMAIN_URL', null),

    'user_domain_url' => env('APP_USER_DOMAIN_URL', null),

    'user_domain_addon_url' => env('APP_USER_DOMAIN_ADDON_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'Asia/Bangkok'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => env('APP_LOCALE', 'th'),

    'currency' => env('APP_CURRENCY', 'USD'),

    'channel' => 'default',

    'version' => env('APP_VERSION'),

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'th',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    'debug_blacklist' => [
        '_ENV' => [
            'APP_KEY',
            'DB_PASSWORD',
        ],

        '_SERVER' => [
            'APP_KEY',
            'DB_PASSWORD',
        ],

        '_POST' => [
            'password',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        AuthServiceProvider::class,
        BroadcastServiceProvider::class,
        BusServiceProvider::class,
        CacheServiceProvider::class,
        ConsoleSupportServiceProvider::class,
        CookieServiceProvider::class,
        DatabaseServiceProvider::class,
        EncryptionServiceProvider::class,
        FilesystemServiceProvider::class,
        FoundationServiceProvider::class,
        HashServiceProvider::class,
        //        Illuminate\Mail\MailServiceProvider::class,
        NotificationServiceProvider::class,
        //        Illuminate\Pagination\PaginationServiceProvider::class,
        PipelineServiceProvider::class,
        QueueServiceProvider::class,
        RedisServiceProvider::class,
        SessionServiceProvider::class,
        //        Very\Redis\RedisServiceProvider::class,
        //        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        //        Rairlie\LockingSession\LockingSessionServiceProvider::class,
        TranslationServiceProvider::class,
        ValidationServiceProvider::class,
        ViewServiceProvider::class,
        RepositoryServiceProvider::class,
        /*
         * Package Service Providers...
         */
        DataTablesServiceProvider::class,
        //        Pimlie\DataTables\MongodbDataTablesServiceProvider::class,
        //        Barryvdh\Debugbar\ServiceProvider::class,
        //        Intervention\Image\ImageServiceProvider::class,
        //        Rainwater\Active\ActiveServiceProvider::class,
        ServiceProvider::class,
        ExcelServiceProvider::class,
        ConcordServiceProvider::class,
        MongoDBServiceProvider::class,
        //        Alimranahmed\LaraOCR\LaraOCRServiceProvider::class,
        //        Rap2hpoutre\LaravelLogViewer\LaravelLogViewerServiceProvider::class,
        /*
         * Application Service Providers...
         */
        AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\BroadcastServiceProvider::class,
        EventServiceProvider::class,
        HorizonServiceProvider::class,
        RouteServiceProvider::class,

        /*
         * Gametech Service Providers...
         */
        CoreServiceProvider::class,
        AdminServiceProvider::class,
        MemberServiceProvider::class,
        APIServiceProvider::class,
        UiServiceProvider::class,
        WalletServiceProvider::class,
        GameServiceProvider::class,
        LogAdminServiceProvider::class,
        LogUserServiceProvider::class,
        PaymentServiceProvider::class,
        PromotionServiceProvider::class,
        //        Gametech\TelegramBot\Providers\TelegramBotServiceProvider::class,
        MarketingServiceProvider::class,
        CenterOAServiceProvider::class,
        LineOAServiceProvider::class,
        //        Gametech\FacebookOA\Providers\FacebookOAServiceProvider::class,
        //        Gametech\Sms\Providers\SmsServiceProvider::class,
        RewardServiceProvider::class,
        LottoServiceProvider::class,
        FrontendApiServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [

        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Arr::class,
        'Artisan' => Artisan::class,
        'Auth' => Auth::class,
        'Blade' => Blade::class,
        'Broadcast' => Broadcast::class,
        'Bus' => Bus::class,
        'Cache' => Cache::class,
        'Config' => Config::class,
        'Cookie' => Cookie::class,
        'Crypt' => Crypt::class,
        'DB' => DB::class,
        'Eloquent' => Model::class,
        'Event' => Event::class,
        'File' => File::class,
        'Gate' => Gate::class,
        'Hash' => Hash::class,
        'Http' => Http::class,
        'Lang' => Lang::class,
        'Log' => Log::class,
        'Mail' => Mail::class,
        'Notification' => Notification::class,
        'Password' => Password::class,
        'Queue' => Queue::class,
        'Redirect' => Redirect::class,
        'Redis' => Redis::class,
        'Request' => Request::class,
        'Response' => Response::class,
        'Route' => Route::class,
        'Schema' => Schema::class,
        'Session' => Session::class,
        'Storage' => Storage::class,
        'Str' => Str::class,
        'URL' => URL::class,
        'Validator' => Validator::class,
        'View' => View::class,
        'Form' => FormFacade::class,
        'Html' => HtmlFacade::class,
        'DataTables' => DataTables::class,
        'Core' => Core::class,
        'Concord' => Concord::class,
        'Helper' => Helper::class,
        'Google2FA' => Facade::class,
        'FastExcel' => FastExcel::class,
        'Excel' => Excel::class,
        'RedisManager' => Redis::class,
    ],

];
