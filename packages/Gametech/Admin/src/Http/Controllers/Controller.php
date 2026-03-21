<?php

namespace Gametech\Admin\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function user(): ?Authenticatable
    {
        return Auth::guard('admin')->user();
    }

    public function id()
    {
        return Auth::guard('admin')->id();
    }

    public function redirectToLogin(): RedirectResponse
    {
        return redirect()->route('admin.session.index');
    }

    protected function getCoreConfig()
    {
        if (app()->bound('request')) {
            $request = app('request');
            $cacheKey = '_admin_controller.core_config';

            if ($request->attributes->has($cacheKey)) {
                return $request->attributes->get($cacheKey);
            }

            $config = core()->getConfigData();
            $request->attributes->set($cacheKey, $config);

            return $config;
        }

        return core()->getConfigData();
    }
}
