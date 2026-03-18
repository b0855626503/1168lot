<?php

namespace Gametech\Admin\Http\Controllers;

use Gametech\Admin\DataTables\MemberConfirmDataTable;
use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Http\Request;


class MemberConfirmController extends AppBaseController
{
    protected $_config;


    protected $repository;


    public function __construct
    (

        MemberRepository $memberRepository

    )

    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $memberRepository;


    }


    public function index(MemberConfirmDataTable $memberConfirmDataTable)
    {
        return $memberConfirmDataTable->render($this->_config['view']);
    }


    public function edit(Request $request)
    {
        $user = $this->user()->name . ' ' . $this->user()->surname;
        $id = $request->input('id');
        $status = $request->input('status');
        $method = $request->input('method');


        $data[$method] = $status;

        $chk = $this->repository->find($id);
        if (!$chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');

        $chk = $this->repository->find($id);

        if (!$chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $this->repository->delete($id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }


}
