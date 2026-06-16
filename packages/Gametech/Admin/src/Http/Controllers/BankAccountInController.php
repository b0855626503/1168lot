<?php

namespace Gametech\Admin\Http\Controllers;


use Gametech\Admin\DataTables\BankAccountInDataTable;
use Gametech\Payment\Repositories\BankAccountRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PragmaRX\Google2FA\Google2FA;


class BankAccountInController extends AppBaseController
{
    protected $_config;

    protected $repository;

    public function __construct
    (
        BankAccountRepository $repository
    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;
    }


    public function index(BankAccountInDataTable $bankAccountInDataTable)
    {
        return $bankAccountInDataTable->render($this->_config['view']);
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

    public function loadBank()
    {
        $banks = [
            'value' => '',
            'text' => '== เลือกธนาคาร =='
        ];

        $responses = collect(app('Gametech\Payment\Repositories\BankRepository')->findWhere(['enable' => 'Y'])->toArray());

        $responses = $responses->map(function ($items){
            $item = (object)$items;
            return [
                'value' => $item->code,
                'text' => $item->name_th
            ];

        })->prepend($banks);



        $result['banks'] = $responses;
        return $this->sendResponseNew($result,'ดำเนินการเสร็จสิ้น');
    }

    public function create(Request $request)
    {
        $google2fa = new Google2FA();
        $user = $this->user()->name.' '.$this->user()->surname;
        $data = json_decode($request['data'],true);

        $banks = $data['banks'];
        $acc_no = $data['acc_no'];
        $scope = $data['visibility_scope'];
        $data['visibility_scope'] = ($scope === 'null' || $scope === '')
            ? null
            : $scope;

       $validator = Validator::make($data, [
           'acc_no' => [
               'required',
               'digits_between:1,20',
               Rule::unique('banks_account')->where(function ($query) use ($banks,$acc_no) {
                   return $query->where('banks', $banks)->where('acc_no', $acc_no)->where('bank_type', 1);
               })
           ]
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->sendError($errors->messages(),200);
        }

        $secret = $data['one_time_password'];

        if ($this->user()->superadmin == 'N') {

            $valid = $google2fa->verifyKey($this->user()->google2fa_secret, $secret);
            if (!$valid) {
                return $this->sendError('รหัสยืนยันไม่ถูกต้อง', 422);
            }
        }

        unset($data['one_time_password']);



        $data['balance'] = 0;
        $data['user_create'] = $user;
        $data['user_update'] = $user;
        $data['bank_type'] = 1;
        $data['device_id'] = '';
        $data['api_refresh'] = '';

//        dd($data);

        $this->repository->createnew($data);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function update($id,Request $request)
    {
        $google2fa = new Google2FA();
        $user = $this->user()->name.' '.$this->user()->surname;

        $data = json_decode($request['data'],true);

        $banks = $data['banks'];
        $acc_no = $data['acc_no'];

        $scope = $data['visibility_scope'];
        $data['visibility_scope'] = ($scope === 'null' || $scope === '')
            ? null
            : $scope;

        $validator = Validator::make($data, [
            'acc_no' => [
                'required',
                'digits_between:1,20',
                Rule::unique('banks_account')->where(function ($query) use ($banks,$acc_no) {
                    return $query->where('banks', $banks)->where('acc_no', $acc_no)->where('bank_type', 1);
                })->ignore($id,'code')

            ]
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->sendError($errors->messages(),200);
        }

        $secret = $data['one_time_password'];

        if ($this->user()->superadmin == 'N') {

            $valid = $google2fa->verifyKey($this->user()->google2fa_secret, $secret);
            if (!$valid) {
                return $this->sendError('รหัสยืนยันไม่ถูกต้อง', 200);
            }
        }

        unset($data['one_time_password']);

        $chk = $this->repository->find($id);
        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }

        $data['user_update'] = $user;
        $this->repository->updatenew($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function edit(Request $request)
    {
        $success = true;
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');
        $status = $request->input('status');
        $method = $request->input('method');


        $data[$method] = $status;

        $chk = $this->repository->find($id);
        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }

//        if($method === 'status_auto' && $chk['banks'] === 11 && $chk['local'] === 'N'){
//            if($status == 'Y'){
//
//                $url = 'https://bays.z7z.work/'.$chk['acc_no'].'/transection.php?bot-status=start';
//               $response = Http::timeout(15)->withHeaders([
//                    'access-key' => '0dbbe3a5-8a3d-4505-9a8e-5790b4a6c90d'
//                ])->post($url);
//               if(!$response->successful()){
//                   return $this->sendError('ไม่สามารถเปิดการทำงานบอทดึงยอดได้');
//               }
//
//            }else{
//
//                $url = 'https://bays.z7z.work/'.$chk['acc_no'].'/transection.php?bot-status=stop';
//                $response = Http::timeout(15)->withHeaders([
//                    'access-key' => '0dbbe3a5-8a3d-4505-9a8e-5790b4a6c90d'
//                ])->post($url);
//                if(!$response->successful()){
//                    return $this->sendError('ไม่สามารถปิดการทำงานบอทดึงยอดได้');
//                }
//
//            }
//        }

        if($method === 'status_auto' && $chk['banks'] === 2 && $chk['local'] === 'N'){
            if($status == 'Y'){

//                $url = 'https://api-kbank.me2me.biz/kbiz/'.$chk['acc_no'].'/status?action=start';
//                $response = Http::timeout(15)->withHeaders([
//                    'access-key' => 'b499fe72-a9fb-4a6a-817d-c096c39a6896'
//                ])->post($url);
//                if(!$response->successful()){
//                    return $this->sendError('ไม่สามารถเปิดการทำงานบอทดึงยอดได้');
//                }

            }else{

//                $url = 'https://api-kbank.me2me.biz/kbiz/'.$chk['acc_no'].'/status?action=stop';
//                $response = Http::timeout(15)->withHeaders([
//                    'access-key' => 'b499fe72-a9fb-4a6a-817d-c096c39a6896'
//                ])->post($url);
//                if(!$response->successful()){
//                    return $this->sendError('ไม่สามารถปิดการทำงานบอทดึงยอดได้');
//                }

            }
        }

        if($method === 'status_auto' && $chk['banks'] === 4 && $chk['local'] === 'N'){
            if($status == 'Y'){

//                $url = 'https://api-scb.me2me.biz/'.$chk['acc_no'].'/status?action=start';
//                $response = Http::timeout(15)->withHeaders([
//                    'access-key' => '8e3b25e3c19b'
//                ])->post($url);
//                if(!$response->successful()){
//                    return $this->sendError('ไม่สามารถเปิดการทำงานบอทดึงยอดได้');
//                }

            }else{

//                $url = 'https://api-scb.me2me.biz/'.$chk['acc_no'].'/status?action=stop';
//                $response = Http::timeout(15)->withHeaders([
//                    'access-key' => '8e3b25e3c19b'
//                ])->post($url);
//                if(!$response->successful()){
//                    return $this->sendError('ไม่สามารถปิดการทำงานบอทดึงยอดได้');
//                }

            }
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
