<?php
//
//
//Route::group(['middleware' => ['web','admin'], 'namespace' => 'Gametech\LogUser\Http\Controllers'], function () {
//    Route::prefix(config('app.admin_url').'/log-user')->group(function () {
//        // Dashboards
//        Route::get('/', 'LaravelLoggerController@showAccessLog')->name('log-user');
//        Route::get('/cleared', ['uses' => 'LaravelLoggerController@showClearedActivityLog'])->name('log-user.cleared');
//
//        // Drill Downs
//        Route::get('/log/{id}', 'LaravelLoggerController@showAccessLogEntry');
//        Route::get('/cleared/log/{id}', 'LaravelLoggerController@showClearedAccessLogEntry');
//
//        // Forms
//        Route::delete('/clear-activity', ['uses' => 'LaravelLoggerController@clearActivityLog'])->name('log-user.clear-activity');
//        Route::delete('/destroy-activity', ['uses' => 'LaravelLoggerController@destroyActivityLog'])->name('log-user.destroy-activity');
//        Route::post('/restore-log', ['uses' => 'LaravelLoggerController@restoreClearedActivityLog'])->name('log-user.restore-activity');
//
//    });
//});
