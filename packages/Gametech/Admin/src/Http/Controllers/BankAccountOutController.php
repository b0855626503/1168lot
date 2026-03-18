<?php

namespace Gametech\Admin\Http\Controllers;


use Gametech\Admin\DataTables\BankAccountOutDataTable;
use Gametech\Payment\Models\BankAccount;
use Gametech\Payment\Repositories\BankAccountRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class BankAccountOutController extends AppBaseController
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


    public function index(BankAccountOutDataTable $bankAccountOutDataTable)
    {
        return $bankAccountOutDataTable->render($this->_config['view']);
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
        $user = $this->user()->name.' '.$this->user()->surname;
        $data = json_decode($request['data'],true);

        $banks = $data['banks'];
        $acc_no = $data['acc_no'];

        $validator = Validator::make($data, [
            'acc_no' => [
                'required',
                'digits_between:1,20',
                Rule::unique('banks_account')->where(function ($query) use ($banks,$acc_no) {
                    return $query->where('banks', $banks)->where('acc_no', $acc_no)->where('bank_type', 2);
                })
            ]
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->sendError($errors->messages(),200);
        }



        $data['user_create'] = $user;
        $data['user_update'] = $user;
        $data['bank_type'] = 2;
        $data['device_id'] = '';
        $data['api_refresh'] = '';

//        dd($data);

        $this->repository->create($data);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function update($id,Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;

        $data = json_decode($request['data'],true);

        $banks = $data['banks'];
        $acc_no = $data['acc_no'];

        $validator = Validator::make($data, [
            'acc_no' => [
                'required',
                'digits_between:1,20',
                Rule::unique('banks_account')->where(function ($query) use ($banks,$acc_no) {
                    return $query->where('banks', $banks)->where('acc_no', $acc_no)->where('bank_type', 2);
                })->ignore($id,'code')

            ]
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->sendError($errors->messages(),200);
        }


        $chk = $this->repository->find($id);
        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }

        if($data['auto_transfer'] == 'Y') {
            BankAccount::where('bank_type',2)->whereNotIn('code', [$id])->update(['auto_transfer' => 'N']);
        }

        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

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
