<?php

namespace Gametech\Admin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FALaravel\Google2FA;
use PragmaRX\Google2FALaravel\Support\Authenticator;


class Google2FAController extends Controller
{

    protected $_config;

    public function __construct()
    {
        $this->_config = request('_config');

//        $this->middleware('admin');

        $this->middleware('auth');
    }

    public function show2faForm(Request $request){
        $user = Auth::guard('admin')->user();

        $google2fa_url = "";
        $secret_key = "";

        if(config('google2fa.enabled') == false){
            (new Authenticator(request()))->login();
            return redirect()->route('admin.home.index');
        }

        if($user->superadmin === 'Y'){
            (new Authenticator(request()))->login();
            return redirect()->route('admin.home.index');
        }

        if($user->google2fa_secret && $user->google2fa_enable){
            return redirect()->route('admin.home.index');
        }

        $google2fa = (new \PragmaRX\Google2FAQRCode\Google2FA());

        if(!$user->google2fa_secret){
            $secret_key = $google2fa->generateSecretKey();
            $user->google2fa_secret = $secret_key;
            $user->save();
        }

        $google2fa_url = $google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $user->google2fa_secret
        );

        $secret_key = $user->google2fa_secret;


        $data = array(
            'user' => $user,
            'secret' => $secret_key,
            'google2fa_url' => $google2fa_url
        );

        return view($this->_config['view'], ['image' => $google2fa_url,
            'secret' => $secret_key])->with('data', $data);
    }

    public function enable2fa(Request $request){
        $user = Auth::guard('admin')->user();
        $google2fa = (new \PragmaRX\Google2FAQRCode\Google2FA());

        $secret = $request->input('secret');
        $valid = $google2fa->verifyKey($user->google2fa_secret, $secret);

        if($valid){
            $user->google2fa_enable = 1;
            $user->save();
            (new Authenticator(request()))->login();
            return redirect()->route('admin.home.index');
        }else{
            return redirect()->route('admin.2fa.setting');
        }
    }

    public function reActivate(Request $request){
        $user = Auth::guard('admin')->user();
        $user->google2fa_secret = null;
        $user->google2fa_enable = 0;
        $user->save();

        return redirect()->route('admin.2fa.setting');
    }

    public function index()
    {
        return redirect(URL()->previous());
    }



}
