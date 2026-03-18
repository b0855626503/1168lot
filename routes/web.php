<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//    return redirect()->route('customer.session.index');
// });

//Route::get('/', 'PageController@index')->defaults('_config', [
//    'view' => 'module.index',
//])->name('welcome.index');
//


$domain = config('app.admin_url') === ''
    ? (config('app.admin_domain_url') ?? config('app.domain_url'))
    : config('app.admin_url') . '.' . (config('app.admin_domain_url') ?? config('app.domain_url'));

Route::domain($domain)->group(function () {
    Route::get('/status', function () {
        return view('status.index');
    })->name('status.index');

    Route::get('/ping', function () {
        return response()->json(['pong' => true, 'time' => now()]);
    })->name('api.ping');
});

//
$domain = config('app.user_url') === ''
    ? (config('app.user_domain_url') ?? config('app.domain_url'))
    : config('app.user_url') . '.' . (config('app.user_domain_url') ?? config('app.domain_url'));

Route::domain($domain)->group(function () {
    Route::middleware('web')->group(function () {

        Route::get('/gate/open', function (Request $req) {
            $open = $req->query('open'); // deposit | withdraw
            $allowed = in_array($open, ['deposit','withdraw'], true);

            $target = url('/').'?open='.($allowed ? $open : '').'&utm_source=gate';
            if (!Auth::guard('customer')->check()) {
                // เก็บ intended url ไว้ใน session เพื่อนำกลับหลังล็อกอิน
                $req->session()->put('url.intended', $target);
                return redirect()->route('customer.marketing.login'); // ชี้ไปหน้าล็อกอินของคุณ
            }
            return redirect($target);
        })->name('gate.open');

        Route::get('/contact', 'HomeController@getcontact')->name('contact.current');

//
        Route::prefix('api')->group(function () {

            Route::any('tw/{mobile}/webhook', 'WebhookController@index');

//            Route::post('/push/test', 'PushController@test')->name('api.app.test');


            Route::middleware(['customer', 'authuser'])->group(function () {
                // ไว้สำหรับ future route ถ้าจะเพิ่ม

                Route::post('/push/subscribe', 'PushController@subscribe')->name('api.app.subscribe');
                Route::delete('/push/unsubscribe', 'PushController@unsubscribe')->name('api.app.unsubscribe');

//                Route::post('/track/presence', 'TrackController@presence')->name('api.track.presence');

//                Route::post('/track/event', 'TrackController@event')->name('api.track.event');
//
//                Route::match(['GET','POST'], 'track/presence', 'TrackController@presence')
//                    ->name('api.track.presence')
//                    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]); // กัน 419

            });


        });

    });

    Route::prefix('api')->group(function () {
        Route::post('/track/presence', 'TrackController@presence')->name('api.track.presence');

        Route::post('/track/event', 'TrackController@event')->name('api.track.event');

    });
});
