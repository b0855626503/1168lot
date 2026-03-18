<?php

namespace Gametech\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;


class SessionController extends Controller
{

    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('admin')->except(['create','store']);

        $this->_config = request('_config');

        $this->middleware('guest', ['except' => 'destroy']);
    }



    public function create()
    {
        if (auth()->guard('admin')->check()) {
            return redirect()->route('admin.dashboard.index');
        } else {
            if (strpos(url()->previous(), 'admin') !== false) {
                $intendedUrl = url()->previous();
            } else {
                $intendedUrl = route('admin.dashboard.index');
            }

            session()->put('url.intended', $intendedUrl);

            return view($this->_config['view']);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {

        $this->validate(request(), [
            'user_name' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = request()->input('user_name');

        $chk = app('Gametech\Admin\Repositories\AdminRepository')->findOneByField('user_name',$username);
//        dd($chk);
        if(!$chk){
            session()->flash('error', 'ไม่สามารถเข้าสู่ระบบได้ โปรดตรวจสอบ');

            return redirect()->back();
        }
        if(is_null($chk->password)){
            $dataadd['password'] = Hash::make($chk->user_pass);
            app('Gametech\Admin\Repositories\AdminRepository')->update($dataadd, $chk->code);
        }

        if (! auth()->guard('admin')->attempt(request(['user_name', 'password']))) {
            session()->flash('error', 'ID หรือ Pass ไม่ถูกต้อง ไม่สามารถเข้าสู่ระบบได้ โปรดตรวจสอบ');

            return redirect()->back();
        }


        if (auth()->guard('admin')->user()->enable != 'Y') {
            session()->flash('warning', 'ไม่สามารถเข้าสู่ระบบได้ เนื่องจากปิดการใช้งาน');

            auth()->guard('admin')->logout();

            return redirect()->route('admin.session.create');
        }

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
        auth()->guard('admin')->logout();

        return redirect()->route($this->_config['redirect']);
    }
}
