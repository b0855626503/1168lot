<?php

namespace Gametech\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;


class SessionController extends Controller
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    /**
     * Create a new Repository instance.
     *
     * @return void
    */
    public function __construct()
    {
        $this->middleware('customer')->except(['show','create']);

        $this->_config = request('_config');
    }

    /**
     * Display the resource.
     *
     * @return View
     */
    public function show()
    {
        if (auth()->guard('customer')->check()) {
            return redirect()->route('customer.home.index');
        } else {
            return view($this->_config['view']);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(Request $request)
    {
        $request->validate([
            'user_name' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('user_name', 'password');

        if (Auth::guard('customer')->attempt($credentials)) {

        }
        if (! auth()->guard('customer')->attempt(request(['user_name', 'password']))) {
            session()->flash('error', 'ไม่สามารถเข้าสู่ระบบได้ โปรดตรวจสอบ');

            return redirect()->back();
        }

        if (auth()->guard('customer')->user()->enable != 'Y') {
            auth()->guard('customer')->logout();

            session()->flash('warning', 'ไม่สามารถเข้าสู่ระบบได้ เนื่องจากปิดการใช้งาน');

            return redirect()->back();
        }
        request()->session()->regenerate();


        //Event passed to prepare cart after login
        Event::dispatch('customer.after.login', request('email'));

        return redirect()->intended(route($this->_config['redirect']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        auth()->guard('customer')->logout();

        request()->session()->invalidate();

        request()->session()->regenerateToken();

        Event::dispatch('customer.after.logout', $id);

        return redirect()->route($this->_config['redirect']);
    }
}
