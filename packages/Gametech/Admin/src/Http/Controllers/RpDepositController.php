<?php

namespace Gametech\Admin\Http\Controllers;

use Gametech\Admin\DataTables\RpDepositDataTable;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;

class RpDepositController extends AppBaseController
{
    protected $_config;

    protected $repository;

    public function __construct(
        BankPaymentRepository $repository
    ) {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;
    }

    public function export(RpDepositDataTable $dataTable)
    {
        return $dataTable->myexport();
    }

    public function edit(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');
        $status = $request->input('status');
        $method = $request->input('method');

        $data[$method] = $status;

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }
}
