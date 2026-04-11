<?php

use Illuminate\Support\Facades\Route;

$apiDomain = trim((string) config('app.api_domain_url', ''));
$adminDomain = trim((string) config('app.admin_domain_url', ''));
$domain = $apiDomain !== '' ? $apiDomain : $adminDomain;
$apiSubdomain = trim((string) config('gametech.api_url', 'api'), '.');

$publicApiRoute = Route::middleware(['api', 'throttle:120,1']);
$internalResultsRoute = Route::middleware(['api', 'throttle:120,1', 'lotto.internal_results']);
if ($domain !== '') {
    $host = $apiSubdomain !== '' ? ($apiSubdomain.'.'.ltrim($domain, '.')) : ltrim($domain, '.');
    $publicApiRoute = $publicApiRoute->domain($host);
    $internalResultsRoute = $internalResultsRoute->domain($host);
}

$publicApiRoute
    ->prefix('api/v1')
    ->group(function () {
        Route::get('get_lottery', 'Gametech\Lotto\Http\Controllers\Api\CentralLotteryResultController@show')
            ->name('lotto.api.get_lottery');
    });

$internalResultsRoute
    ->prefix('internal/lottery/results')
    ->group(function () {
        Route::get('exphuay/{type}', 'Gametech\Lotto\Http\Controllers\Api\InternalResultController@exphuay')
            ->name('lotto.internal.results.exphuay');

        Route::get('dowjones-midnight', 'Gametech\Lotto\Http\Controllers\Api\InternalResultController@dowjonesMidnight')
            ->name('lotto.internal.results.dowjones_midnight');

        Route::get('dowjones-extra', 'Gametech\Lotto\Http\Controllers\Api\InternalResultController@dowjonesExtra')
            ->name('lotto.internal.results.dowjones_extra');
    });
