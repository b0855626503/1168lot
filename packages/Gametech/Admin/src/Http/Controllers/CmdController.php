<?php

namespace Gametech\Admin\Http\Controllers;


use Gametech\Admin\Support\SelfUpdateManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;


class CmdController extends AppBaseController
{
    protected $_config;

    public function __construct()
    {
        $this->_config = request('_config');

        $this->middleware('admin');
    }


    public function storeLink()
    {
        Artisan::call('storage:link');
        return 'Store link';
    }

    public function optimizeClear()
    {
        Artisan::call('optimize:clear');
        Artisan::call('lada-cache:flush');
        opcache_reset();
        return 'Optimize Clear & reset';
    }

    public function optimize()
    {
        Artisan::call('optimize');
        return 'Optimize Cache';
    }

    public function webServiceStart()
    {
        Artisan::call('reverb:start', [
            '--host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
            '--port' => env('REVERB_SERVER_PORT', 8080),
        ]);

        return 'Reverb start';
    }

    public function webServiceStop()
    {
        Artisan::call('reverb:restart');

        return 'Reverb restart';
    }

    public function viewCmd()
    {
        Artisan::call('view:clear');
        return 'View Clear';
    }

    public function cacheCmd()
    {
        Artisan::call('cache:clear');
        return 'Cache Clear';
    }

    public function cashback()
    {
        $exit = Artisan::call('cashback:list');
        if($exit){
            return 'Cashback Complete โปรดเชคก่อนอย่า กด f5 , refresh';
        }
        return 'Cashback';
    }

    public function ic()
    {
        $exit = Artisan::call('ic:list');
        if($exit){
            return 'IC Complete โปรดเชคก่อนอย่า กด f5 , refresh';
        }
        return 'ic';
    }

    public function resetPro()
    {
        $exit = DB::table('members')->update(['status_pro' => 0]);
        if($exit){
            return 'ล้างค่า โปรสมาชิกใหม่แล้ว';
        }
        return 'ลองใหม่';
    }

    public function updatePatch(SelfUpdateManager $updater)
    {
        session()->flash('warning', $updater->getDecommissionedMessage());

        return redirect()->route('admin.bank_in.index');
    }

    public function checkPatch(SelfUpdateManager $updater)
    {
        return response()->make(
            'Current '.$updater->getInstalledVersion()
            .'<br>'
            .'Last unavailable'
            .'<br>'
            .$updater->getDecommissionedMessage()
        );
    }
}
