<?php

namespace Gametech\Admin\Http\Controllers;

use Gametech\Auto\Jobs\MemberCashback as MemberCashbackJob;
use Gametech\Auto\Jobs\MemberIc as MemberIcJob;
use Gametech\Core\Repositories\AnnounceRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashbackICController extends AppBaseController
{
    protected $_config;

    protected $repository;

    public function __construct(AnnounceRepository $repository)
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;
    }



    public function Cashback(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $data = request()->all();
        $ip = $request->ip();
        $startdate = $data['date_cashback'];
        unset($data['id']);


        $data['emp_code'] = $this->id();
        $data['emp_name'] = $user;
        $data['ip'] = $ip;

        $items = (object) $data;


        $chk = DB::table('members_cashback')->whereDate('date_cashback',$startdate)->where('downline_code',$items->member_code)->where('topupic','Y');
        if($chk->doesntExist()) {
            MemberCashbackJob::dispatch($startdate, $items)->onQueue('cashback');
        }else{
            return $this->sendError('มีรายการมอบแล้ว',200);
        }


        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function MemberIC(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $data = request()->all();
        $ip = $request->ip();
        $startdate = $data['date_cashback'];
        unset($data['id']);


        $data['emp_code'] = $this->id();
        $data['emp_name'] = $user;
        $data['ip'] = $ip;

        $items = (object) $data;

        $chk = DB::table('members_ic')->whereDate('date_cashback',$startdate)->where('member_code',$items->upline_code)->where('downline_code',$items->member_code)->where('topupic','Y');
        if($chk->doesntExist()){
            MemberIcJob::dispatch($startdate,$items)->onQueue('ic');
        }else{
            return $this->sendError('มีรายการมอบแล้ว',200);
        }


        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }


}
