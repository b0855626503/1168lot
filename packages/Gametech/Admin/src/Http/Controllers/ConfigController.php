<?php

namespace Gametech\Admin\Http\Controllers;

use Gametech\Core\Repositories\ConfigRepository;
use Gametech\Payment\Repositories\BankRuleRepository;
use Illuminate\Http\Request;

class ConfigController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $bankRuleRepository;

    public function __construct(
        ConfigRepository $repository,
        BankRuleRepository $bankRuleRepo

    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->bankRuleRepository = $bankRuleRepo;
    }


    public function index()
    {
        $configs = $this->repository->findOrFail(1);
        $configs = collect($configs)->toArray();

        return view($this->_config['view'])->with('configs' , $configs);
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
//            session()->flash('error', 'ไม่พบข้อมูลดังกล่าว');
//            return redirect()->back();
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }

        $data = $request->all();
        unset($data['fileupload']);
        unset($data['fileuploadnew']);
//        unset($data->fileupload);

        $this->repository->updatenew($data, $id);


//        session()->flash('success', 'บันทึกข้อมูลสำเร็จ');
//        return redirect()->route('admin.announces.index');
        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function getrule(Request $request)
    {
        $id =   $id = $request->input('id');
        $responses = collect($this->bankRuleRepository->getRule());

        $responses = $responses->map(function ($items){
            $item = (object)$items;
            $bank = (!is_null($item->bank) ? (object)$item->bank : '');
            return [

                'bank' => $bank->shortcode,
                'method' => $item->method,
                'bank_number' => $item->bank_number,
                'action' => '<button type="button" class="btn btn-warning btn-xs icon-only" onclick="delSub('.$item->code.')"><i class="fa fa-times"></i></button>'

            ];

        });

        $result['list'] = $responses;

        return $this->sendResponseNew($result, 'complete');
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


}
