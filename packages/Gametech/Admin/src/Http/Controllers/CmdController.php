<?php

namespace Gametech\Admin\Http\Controllers;


use Codedge\Updater\Traits\UseVersionFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;


class CmdController extends AppBaseController
{
    use UseVersionFile;

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
        Artisan::call('websockets:serve --host=127.0.0.1');
        return 'Websockets start';
    }

    public function webServiceStop()
    {
        Artisan::call('websockets:restart');
        return 'Websockets Restart';
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

    public function updatePatch(\Codedge\Updater\UpdaterManager $updater)
    {
        $current = $updater->source()->getVersionInstalled();

        if($updater->source()->isNewVersionAvailable($current)) {

            $versionAvailable = $updater->source()->getVersionAvailable();

            $release = $updater->source()->fetch($versionAvailable);

            $updater->source()->update($release);

            Artisan::call('postupdate:work');

        }

        return redirect()->route('admin.bank_in.index');
    }

    public function checkPatch(\Codedge\Updater\UpdaterManager $updater)
    {
        $this->deleteVersionFile();

        $current = $updater->source()->getVersionInstalled();
        $versionAvailable = $updater->source()->getVersionAvailable();
        echo 'Current '.$current;
        echo '<br>';
        echo 'Last '.$versionAvailable;


    }


}
