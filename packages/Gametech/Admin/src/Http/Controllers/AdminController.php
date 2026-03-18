<?php

namespace Gametech\Admin\Http\Controllers;

use Gametech\Admin\DataTables\AdminDataTable;
use Gametech\Admin\Repositories\AdminRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends AppBaseController
{

    protected $_config;

    protected $repository;

    public function __construct
    (
        AdminRepository $repository
    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;
    }


    public function index(AdminDataTable $adminDataTable)
    {
        return $adminDataTable->render($this->_config['view']);
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

    public function loadRole()
    {
        $roles = [
            'value' => '',
            'text' => 'โปรดระบุ'
        ];

        $user = $this->user();
        if($user->role_id === 1){
            $responses = collect(app('Gametech\Admin\Repositories\RoleRepository')->get()->toArray());

        }else{
            $responses = collect(app('Gametech\Admin\Repositories\RoleRepository')->where('code','<>',1)->get()->toArray());

        }

        $responses = $responses->map(function ($items){
            $item = (object)$items;
            return [
                'value' => $item->code,
                'text' => $item->name
            ];

        })->prepend($roles);



        $result['roles'] = $responses;
        return $this->sendResponseNew($result,'complete');
    }

    public function create(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $data = $request->input('data');


        $validator = Validator::make($data, [
            'user_name' => 'required|string|unique:employees,user_name',
            'email' => 'required|email|unique:employees,email',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->sendError($errors->messages(),200);
        }



//        $data['user_pass'] = $data['user_pass'];
        $data['password'] = Hash::make($data['user_pass']);
        $data['user_create'] = $user;
        $data['user_update'] = $user;


        $this->repository->create($data);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function update(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');
        $data = $request->input('data');

        $validator = Validator::make($data, [
            'user_name' => 'required|string|unique:employees,user_name,'.$id.',code',
            'email' => 'required|email|unique:employees,email,'.$id.',code'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->sendError($errors->messages(),200);
        }


        $chk = $this->repository->find($id);
        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }

        if($data['user_pass']){
//            $data['user_pass'] = $data['user_pass'];
            $data['password'] = Hash::make($data['user_pass']);
        }else{
            unset($data['user_pass']);
        }
        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function edit(Request $request)
    {
        $myid = Auth::guard('admin')->user();
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');
        $status = $request->input('status');
        $method = $request->input('method');


        $data[$method] = $status;

        $chk = $this->repository->find($id);
        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }


        if($method == 'google2fa_enable' && $status == 0){
            $data['google2fa_secret'] = null;
//            Auth::loginUsingId($myid->code);
        }

        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');

        $chk = $this->repository->find($id);

        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }

        $this->repository->delete($id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }


}
