<?php

use App\Services\Dashboard\DashboardWebCodeResolver;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/


//Broadcast::channel('Gametech.Member.Models.Member.{code}', function ($user, $code) {
//    return (int) $user->code === (int) $code;
//});

Broadcast::channel(config('app.name') . '_members.{id}', function ($user, $id) {
    return (int)$user->code === (int)$id;
});

Broadcast::channel(config('app.name') . '_members', function ($user) {
    return !is_null($user);
});

Broadcast::channel(config('app.name') . '_admins.{id}', function ($user, $id) {
    return (int)$user->code === (int)$id;
});


Broadcast::channel(config('app.name') . '_events', function ($user) {
    return true;
});

Broadcast::channel('dashboard.summary.{webCode}', function ($admin, $webCode) {
    if (!$admin) {
        return false;
    }

    $currentWebCode = app(DashboardWebCodeResolver::class)->resolve();

    return (string) $webCode === (string) $currentWebCode;
}, ['guards' => ['admin']]);
//
