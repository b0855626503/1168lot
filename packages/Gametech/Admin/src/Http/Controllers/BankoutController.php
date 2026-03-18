<?php

namespace Gametech\Admin\Http\Controllers;


use Gametech\Admin\DataTables\BankoutDataTable;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\WithdrawRepository;
use Illuminate\Http\Request;


class BankoutController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberRepository;

    public function __construct
    (
        BankPaymentRepository $repository,
        WithdrawRepository $withdrawRepository,

        MemberRepository $memberRepository
    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->memberRepository = $memberRepository;

        $this->withdrawRepository = $withdrawRepository;
    }


    public function index(BankoutDataTable $bankoutDataTable)
    {
        return $bankoutDataTable->render($this->_config['view']);
    }

    public function clear(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');
        $remark = $request->input('remark');

        $chk = $this->repository->find($id);

        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }


        $data['ip_admin'] = $request->ip();
        $data['remark_admin'] = $remark;
        $data['status'] = 3;
        $data['emp_topup'] = $this->user()->code;
        $data['user_update'] = $user;
        $data['date_approve'] = now()->toDateTimeString();
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function destroy(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');

        $chk = $this->withdrawRepository->find($id);

        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }


        $data['enable'] = 'N';
        $data['user_update'] = $user;
        $this->withdrawRepository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }


}
