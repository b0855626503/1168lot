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

