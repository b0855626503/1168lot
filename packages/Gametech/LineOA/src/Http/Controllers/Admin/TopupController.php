<?php

namespace Gametech\LineOA\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\LineOA\DataTables\TopupDataTable;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;

class TopupController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberRepository;

    public function __construct(
        BankPaymentRepository $repository,

        MemberRepository $memberRepository
    ) {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->memberRepository = $memberRepository;
    }

    public function index(TopupDataTable $topupDataTable)
    {
        return $topupDataTable->render($this->_config['view']);
    }

    public function clear(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');
        $remark = $request->input('remark');

        $chk = $this->repository->find($id);

        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $data['ip_admin'] = $request->ip();
        $data['remark_admin'] = $remark;
        $data['status'] = 2;
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

        $chk = $this->repository->find($id);

        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $data['enable'] = 'N';
        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function loadData(Request $request)
    {
        $id = $request->input('id');

        $data = $this->memberRepository->findOneWhere(['user_name' => $id, 'enable' => 'Y']);
        if (empty($data)) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');

    }

    public function update($id, Request $request)
    {
        $ip = $request->ip();
        $user = $this->user()->name.' '.$this->user()->surname;

        $data = json_decode($request['data'], true);

        if (! $data['member_topup']) {
            return $this->sendSuccess('ไม่พบข้อมูลสมาชิก');
        }

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendSuccess('ไม่พบข้อมูลดังกล่าว');
        }

        //        if($chk->autocheck == 'W'){
        //            return $this->sendSuccess('รายการนี้ กำลัง รอเติมเงินผ่านระบบ Auto อยู่');
        //        }

        if ($chk->autocheck == 'Y' && $chk->status == 1) {
            return $this->sendSuccess('รายการนี้ เติมสำเร็จไปแล้ว');
        }

        $data['emp_topup'] = $this->id();
        $data['autocheck'] = 'W';
        $data['ip_admin'] = $ip;
        $data['user_create'] = $chk->user_create.' รอระบบเติมอัตโนมัติ ทำรายการเติมเงินโดย Staff : '.$user;
        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function approve(Request $request)
    {
        $ip = $request->ip();
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendSuccess('ไม่พบข้อมูลดังกล่าว');
        }

        if ($chk->autocheck == 'W') {
            return $this->sendSuccess('รายการนี้ กำลัง รอเติมเงินผ่านระบบ Auto อยู่');
        }

        if ($chk->autocheck == 'Y' && $chk->status == 1) {
            return $this->sendSuccess('รายการนี้ เติมสำเร็จไปแล้ว');
        }

        $data['emp_topup'] = $this->id();
        $data['autocheck'] = 'W';
        $data['ip_admin'] = $ip;
        $data['remark_admin'] = 'รอระบบเติมอัตโนมัติ อนุมัติรายการเติมเงินโดย Staff : '.$user;
        $data['user_create'] = 'รอระบบเติมอัตโนมัติ อนุมัติรายการเติมเงินโดย Staff : '.$user;
        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }
}
