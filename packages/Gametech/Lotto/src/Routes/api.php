<?php

use Illuminate\Support\Facades\Route;

/**
 * Member API Routes - /api/lotto/*
 * Middleware: api, authuser (Customer must be authenticated)
 */

Route::middleware(['api', 'authuser:customer'])->prefix('api/lotto')->group(function () {

    // List available draws
    Route::get('draws', 'Gametech\Lotto\Http\Controllers\Api\DrawController@index')
        ->name('lotto.api.draws.index');

    // Get draw details
    Route::get('draws/{id}', 'Gametech\Lotto\Http\Controllers\Api\DrawController@show')
        ->name('lotto.api.draws.show');

    // Place bet
    Route::post('bet', 'Gametech\Lotto\Http\Controllers\Api\BetController@store')
        ->name('lotto.api.bet.store');

    // List group packages (active only)
    Route::get('groups/{groupId}/packages', 'Gametech\Lotto\Http\Controllers\Api\PackageController@available')
        ->name('lotto.api.packages.available');

    // Helper-only package selection in current flow
    Route::post('groups/{groupId}/select-package', 'Gametech\Lotto\Http\Controllers\Api\PackageController@select')
        ->name('lotto.api.packages.select');

    // Helper-only selected package state
    Route::get('groups/{groupId}/selected-package', 'Gametech\Lotto\Http\Controllers\Api\PackageController@selected')
        ->name('lotto.api.packages.selected');

    // Get member's tickets (history)
    Route::get('tickets', 'Gametech\Lotto\Http\Controllers\Api\TicketController@index')
        ->name('lotto.api.tickets.index');

    // Get ticket details
    Route::get('tickets/{id}', 'Gametech\Lotto\Http\Controllers\Api\TicketController@show')
        ->name('lotto.api.tickets.show');

    // Cancel ticket
    Route::post('tickets/{id}/cancel', 'Gametech\Lotto\Http\Controllers\Api\TicketController@cancel')
        ->name('lotto.api.tickets.cancel');

});

$apiDomain = trim((string) config('app.api_domain_url', ''));
$adminDomain = trim((string) config('app.admin_domain_url', ''));
$domain = $apiDomain !== '' ? $apiDomain : $adminDomain;
$apiSubdomain = trim((string) config('gametech.api_url', 'api'), '.');

$internalResultsRoute = Route::middleware(['api', 'throttle:120,1', 'lotto.internal_results']);
if ($domain !== '') {
    $host = $apiSubdomain !== '' ? ($apiSubdomain . '.' . ltrim($domain, '.')) : ltrim($domain, '.');
    $internalResultsRoute = $internalResultsRoute->domain($host);
}

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
