<?php

namespace Gametech\Admin\Http\Controllers;


use Gametech\Admin\DataTables\BankDataTable;
use Gametech\Payment\Repositories\BankRepository;
use Illuminate\Http\Request;

class BankController extends AppBaseController
{
    protected $_config;

    protected $repository;

    public function __construct
    (
        BankRepository $repository
    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;
    }


    public function index(BankDataTable $bankDataTable)
    {
        return $bankDataTable->render($this->_config['view']);
    }

    public function edit(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');
        $status = $request->input('status');
        $method = $request->input('method');


        $data[$method] = $status;

        $chk = $this->repository->find($id);
        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }

        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function loadData(Request $request)
    {
        $id = $request->input('id');

        $data = $this->repository->find($id);
        if(!$data){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }

        return $this->sendResponse($data,'ดำเนินการเสร็จสิ้น');

    }

    public function create(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;


        $data = json_decode($request['data'], true);

        $data['user_create'] = $user;
        $data['user_update'] = $user;

//        $data['user_update'] = $user;
//
//        dd($data);

        $this->repository->createnew($data);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function update($id,Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;

        $data = json_decode($request['data'],true);


        $chk = $this->repository->find($id);
        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }

        $data['user_update'] = $user;
        $this->repository->updatenew($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }


}
