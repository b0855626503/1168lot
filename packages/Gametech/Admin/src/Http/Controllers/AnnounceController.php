<?php

namespace Gametech\Admin\Http\Controllers;

use Gametech\Core\Repositories\AnnounceRepository;
use Illuminate\Http\Request;

class AnnounceController extends AppBaseController
{
    protected $_config;

    protected $repository;

    public function __construct(AnnounceRepository $repository)
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;
    }


    public function index()
    {
        $announce = $this->repository->findOrFail(1);

        return view($this->_config['view'], compact('announce'));
    }

    public function loadData(Request $request)
    {
        $id = $request->input('id');

        $data = $this->repository->findOrFail($id);
        if(!$data){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }

        return $this->sendResponse($data,'ดำเนินการเสร็จสิ้น');

    }

    public function update($id,Request $request)
    {

        $chk = $this->repository->findOrFail($id);

        if (empty($chk)) {
            session()->flash('error', 'ไม่พบข้อมูลดังกล่าว');
            return redirect()->back();
        }

        $this->repository->update($request->all(), $id);

        session()->flash('success', 'บันทึกข้อมูลสำเร็จ');
        return redirect()->route('admin.announces.index');

    }


}
