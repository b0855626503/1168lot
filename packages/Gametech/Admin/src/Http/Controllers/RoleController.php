<?php

namespace Gametech\Admin\Http\Controllers;




use Gametech\Admin\DataTables\RoleDataTable;
use Gametech\Admin\Repositories\RoleRepository;
use Illuminate\Http\Request;

class RoleController extends AppBaseController
{

    protected $_config;

    protected $repository;

    public function __construct
    (
        RoleRepository $repository
    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;
    }


    public function index(RoleDataTable $roleDataTable)
    {
        return $roleDataTable->render($this->_config['view']);
    }

    public function loadData(Request $request)
    {
        $id = $request->input('id');

        $data = $this->repository->findOrFail($id);
        if(!$data){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }

        $data['permissions'] = json_encode($data['permissions']);
//        dd($data);
        $acl = app('acl');
        $data['acl'] = collect($acl->items)->toJson();
        return $this->sendResponse($data,'ดำเนินการเสร็จสิ้น');

    }

    public function create(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $data = request()->all();




        $data['user_create'] = $user;
        $data['user_update'] = $user;


        $this->repository->create($data);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function update($id)
    {
        $user = $this->user()->name.' '.$this->user()->surname;



        $chk = $this->repository->find($id);
        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }


        $data['user_update'] = $user;
        $this->repository->update(request()->all(), $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

}
