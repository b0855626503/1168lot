<?php

namespace Gametech\Admin\Http\Controllers;

use Gametech\Admin\DataTables\ConfirmwalletDataTable;
use Gametech\Payment\Repositories\PaymentWaitingRepository;
use Illuminate\Http\Request;


class ConfirmwalletController extends AppBaseController
{
    protected $_config;

    protected $repository;

    public function __construct(PaymentWaitingRepository $repository)
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;
    }


    public function index(ConfirmwalletDataTable $confirmwalletDataTable)
    {
        return $confirmwalletDataTable->render($this->_config['view']);
    }

    public function update($id,Request $request)
    {
        $ip = $request->ip();
        $user = $this->user()->name.' '.$this->user()->surname;

        $data = json_decode($request['data'],true);


        $chk = $this->repository->find($id);
        if(!$chk){
            return $this->sendSuccess('ไม่พบข้อมูลดังกล่าว');
        }

        $member = app('Gametech\Member\Repositories\MemberRepository')->find($chk->member_code);

        $getdata = app('Gametech\Game\Repositories\GameUserRepository')->getOneUser($chk->member_code,$chk->game_code);
        if($getdata['success'] === false){
            return $this->sendError('ไม่พบข้อมูล User ของเกม ดังกล่าว',200);
        }

        if($getdata->pro_code > 0 && $getdata->amount_balance > 0){
            return $this->sendError('ไม่สามารถทำรายการได้ เนื่องจาก User ติดเทินโปรอยู่',200);
        }

        $promotion = app('Gametech\Promotion\Repositories\PromotionRepository')->checkPromotion($chk->pro_code,$chk->amount,$chk->date_create);

        $data_new = [
            'member_code' => $chk->member_code,
            'game_code' => $chk->game_code,
            'game_name' => $getdata->game->name,
            'user_code' => $getdata->code,
            'user_name' => $getdata->user_name,
            'pro_code' => $chk->pro_code,
            'pro_name' => $promotion['pro_name'],
            'turnpro' => $promotion['turnpro'],
            'withdraw_limit' => $promotion['withdraw_limit'],
            'amount' => $chk->amount,
            'bonus' => $promotion['bonus'],
            'total' => $promotion['total'],
            'payment_code' => $id,
            'emp_code' => $this->id(),
            'emp_name' => $user,
            'member_balance' => $member->balance,
        ];

        $response =  app('Gametech\Payment\Repositories\BillRepository')->confirmWallet($data_new);
        if($response['success'] === false){
            return $this->sendError('ไม่สามารถทำรายการ อนุมัติการโอนเงินนี้ได้',200);
        }


        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function clear(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');
        $remark = $request->input('remark');

        $chk = $this->repository->find($id);

        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }

        $member = app('Gametech\Member\Repositories\MemberRepository')->find($chk->member_code);

        $getdata = app('Gametech\Game\Repositories\GameUserRepository')->getOneUser($chk->member_code,$chk->game_code);
        if($getdata['success'] === false){
            return $this->sendError('ไม่พบข้อมูล User ของเกม ดังกล่าว',200);
        }

        $promotion = app('Gametech\Promotion\Repositories\PromotionRepository')->checkPromotion($chk->pro_code,$chk->amount,$chk->date_create);



        $data_new = [
            'member_code' => $chk->member_code,
            'game_code' => $chk->game_code,
            'game_name' => $getdata->game->name,
            'user_code' => $getdata->code,
            'user_name' => $getdata->user_name,
            'pro_code' => $chk->pro_code,
            'pro_name' => $promotion['pro_name'],
            'turnpro' => $promotion['turnpro'],
            'amount' => $chk->amount,
            'bonus' => $promotion['bonus'],
            'total' => $promotion['total'],
            'payment_code' => $id,
            'emp_code' => $this->id(),
            'emp_name' => $user,
            'member_balance' => $member->balance,
            'remark' =>  $remark
        ];

        $response =  app('Gametech\Payment\Repositories\BillRepository')->rejectWallet($data_new);
        if($response['success'] === false){
            return $this->sendError('ไม่สามารถทำรายการ คืนยอดการโอนเงินนี้ได้',200);
        }


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

        $data['ip_admin'] = $request->ip();
        $data['emp_code'] = $this->id();
        $data['enable'] = 'N';
        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }


}
