<?php

namespace Gametech\Auto\Http\Controllers;

use Gametech\Auto\Jobs\AutoCheckTopup;
use Gametech\Auto\Jobs\AutoTopup;
use Gametech\Auto\Jobs\MemberCashback;
use Gametech\Auto\Jobs\PaymentBay;
use Gametech\Auto\Jobs\PaymentKbank;
use Gametech\Auto\Jobs\PaymentKtb;
use Gametech\Auto\Jobs\PaymentTrue;
use Illuminate\Http\Request;


class JobController extends Controller
{

    public function __construct()
    {

    }

    public function checkPayment($id)
    {

        $topup = new AutoCheckTopup($id);
        AutoCheckTopup::dispatch($topup)->onQueue('processing');
        echo 'Auto Check Payment Start';

    }

    public function topup()
    {

        $topup = new AutoTopup();
        AutoTopup::dispatch($topup)->onQueue('processing');
        echo 'Auto Topup Start';

    }

    public function getBank($id)
    {


    }

    public function getAccount($id,$account)
    {
        switch ($id){
            case 'tw':
//                $topup = new PaymentTrue($account);
//                dd($topup);
                PaymentTrue::dispatch($account);
                break;
            case 'kbank':
                $topup = new PaymentKbank($account);
                PaymentKbank::dispatch($topup)->onQueue('processing');
                break;
            case 'bay':
                $topup = new PaymentBay($account);
                PaymentBay::dispatch($topup)->onQueue('processing');
                break;
            case 'ktb':
                $topup = new PaymentKtb($account);
                PaymentKtb::dispatch($topup)->onQueue('processing');
                break;
        }

    }

    public function memberCashback(Request $request)
    {
        MemberCashback::dispatch($request);
    }


}
