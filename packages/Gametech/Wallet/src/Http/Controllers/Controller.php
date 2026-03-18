<?php

namespace Gametech\Wallet\Http\Controllers;

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
        return Auth::guard('customer')->user();
    }

    public function id()
    {
        return Auth::guard('customer')->id();
    }

    /**
     * Display a listing of the resource.
     *
     * @return RedirectResponse
     */
    public function redirectToLogin(): RedirectResponse
    {
        return redirect()->route('customer.session.index');
    }
}
