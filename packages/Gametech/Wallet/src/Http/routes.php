<?php

use Illuminate\Support\Facades\Route;

if (config('app.user_url') === '') {
    $baseurl = (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
} else {
    $baseurl = config('app.user_url').'.'.(is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
}

Route::domain($baseurl)->group(function () {

    require __DIR__.'/routesub.php';

});

//if (file_exists(__DIR__.'/routes_addon.php')) {
//    require __DIR__.'/routes_addon.php';
//}
