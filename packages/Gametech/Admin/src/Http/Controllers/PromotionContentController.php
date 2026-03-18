<?php

namespace Gametech\Admin\Http\Controllers;

use Gametech\Admin\DataTables\PromotionContentDataTable;
use Gametech\Promotion\Repositories\PromotionAmountRepository;
use Gametech\Promotion\Repositories\PromotionContentRepository;
use Gametech\Promotion\Repositories\PromotionTimeRepository;
use Illuminate\Http\Request;


class PromotionContentController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $promotionTimeRepository;

    protected $promotionAmountRepository;

    public function __construct
    (
        PromotionContentRepository $repository,
        PromotionTimeRepository $promotionTimeRepo,
        PromotionAmountRepository $promotionAmountRepo

    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->promotionTimeRepository = $promotionTimeRepo;

        $this->promotionAmountRepository = $promotionAmountRepo;
    }


    public function index(PromotionContentDataTable $promotionContentDataTable)
    {
        return $promotionContentDataTable->render($this->_config['view']);
    }

    public function loadData(Request $request)
    {
        $id = $request->input('id');


        $data = $this->repository->find($id);
        if (!$data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }


        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');

    }

    public function create(Request $request)
    {
        $user = $this->user()->name . ' ' . $this->user()->surname;

        $data = json_decode($request['data'], true);



        $data['user_create'] = $user;
        $data['user_update'] = $user;

        $this->repository->createnew($data);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function update($id, Request $request)
    {
        $user = $this->user()->name . ' ' . $this->user()->surname;

        $data = json_decode($request['data'], true);


        $chk = $this->repository->find($id);
        if (!$chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $data['user_update'] = $user;
        $this->repository->updatenew($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

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
        $user = $this->user()->name.' '.$this->user()->surname;

        $id = $request->input('id');

        $chk = $this->repository->find($id);

        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }


        $this->repository->delete($id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function loadPro(Request $request)
    {
        $id = $request->input('id');
        $method = $request->input('method');
        $table = '';

        $responses = [];


        switch ($method) {
            case 'TIME':
            case 'TIMEPC':
                $responses = $this->promotionTimeRepository->findByField('pro_code', $id);
                $table = 'promotions_time';
                break;

            case 'AMOUNT':
            case 'AMOUNTPC':
            case 'BETWEEN':
            case 'BETWEENPC':
                $responses = $this->promotionAmountRepository->findByField('pro_code', $id);
            $table = 'promotions_amount';
                break;

        }

        $no = 0;
        $responses = collect($responses)->map(function ($items) use ($no,$table){
            ++$no;
            $item = (object)$items;
            return [
                'no' => $item->code,
                'deposit_amount' => $item->deposit_amount,
                'deposit_stop' => $item->deposit_stop,
                'amount' => $item->amount,
                'action' => '<button type="button" class="btn btn-warning btn-xs icon-only" onclick="delSub('.$item->code.','. "'" .$table."'" .')"><i class="fa fa-times"></i></button>'

            ];

        });

        $result['list'] = $responses;

        return $this->sendResponseNew($result, 'complete');
    }


}
