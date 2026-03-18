<?php


//Route::group(['middleware' => ['web','admin'], 'namespace' => 'Gametech\LogAdmin\Http\Controllers'], function () {
//    Route::prefix(config('app.admin_url').'/log-admin')->group(function () {
//        // Dashboards
//        Route::get('/', 'LaravelLoggerController@showAccessLog')->name('log-admin');
//        Route::get('/cleared', ['uses' => 'LaravelLoggerController@showClearedActivityLog'])->name('log-admin.cleared');
//
//        // Drill Downs
//        Route::get('/log/{id}', 'LaravelLoggerController@showAccessLogEntry');
//        Route::get('/cleared/log/{id}', 'LaravelLoggerController@showClearedAccessLogEntry');
//
//        // Forms
//        Route::delete('/clear-activity', ['uses' => 'LaravelLoggerController@clearActivityLog'])->name('log-admin.clear-activity');
//        Route::delete('/destroy-activity', ['uses' => 'LaravelLoggerController@destroyActivityLog'])->name('log-admin.destroy-activity');
//        Route::post('/restore-log', ['uses' => 'LaravelLoggerController@restoreClearedActivityLog'])->name('log-admin.restore-activity');
//
//    });
//});
