<?php

namespace Gametech\API\Http\ControllersFree;

use Gametech\Core\Repositories\AnnounceRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AnnounceController extends AppBaseController
{
    protected $_config;

    protected $repository;

    public function __construct(AnnounceRepository $repository)
    {
        $this->_config = request('_config');

        $this->middleware('api');

        $this->repository = $repository;
    }


    public function Announce(Request $request)
    {
        $id = 1;
        $chk = $this->repository->findOrFail($id);

        if (empty($chk)) {
            return $this->sendError('ไม่สามารถบันทึกข้อมูลได้', 200);
        }

        $data['content'] = $request->input('message');
        $data['new'] = 'Y';

        $this->repository->update($data, $id);

        return $this->sendSuccess('บันทึกข้อมูลแล้ว');

    }

    public function getDashBoard()
    {
        $config = core()->getConfigData();
        $startdate = now()->toDateString();

        $deposit = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()->whereIn('status', [0, 1])->whereDate('date_create', $startdate)->sum('value');
        $deposit_cnt = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()->whereIn('status', [0, 1])->whereDate('date_create', $startdate)->count();
        $withdraw = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()->whereDate('date_approve', $startdate)->sum('amount');
        $withdraw_cnt = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()->whereDate('date_approve', $startdate)->count();
        $member_cnt = app('Gametech\Member\Repositories\MemberRepository')->active()->whereDate('date_regis', $startdate)->count();
        $banks = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountInAll();
        $banks = $banks->map(function ($items) {
            return [
                'text' => $items->bank->shortcode . ' ' . $items->acc_no,
                'value' => core()->currency($items->balance),
                'update' => core()->formatDate($items->checktime, 'd/m/y H:i:s')
            ];
        });

        $response = [
            'title' => $config['sitename'] . ' (' . config('game.starvegas.merchant_admin_name') . ')',
            'data' => [
                ['method' => 'deposit', 'icon' => 'fas fa-plus-circle', 'color' => 'bg-info', 'text' => 'ยอดฝาก', 'value' => core()->currency($deposit)],
                ['method' => 'deposit_1', 'icon' => 'fas fa-plus', 'color' => 'bg-info', 'text' => 'จำนวนบิลฝาก', 'value' => $deposit_cnt],
                ['method' => 'withdraw', 'icon' => 'fas fa-minus-circle', 'color' => 'bg-danger', 'text' => 'ยอดถอน', 'value' => core()->currency($withdraw)],
                ['method' => 'withdraw_1', 'icon' => 'fas fa-minus', 'color' => 'bg-danger', 'text' => 'จำนวนบิลถอน', 'value' => $withdraw_cnt],
                ['method' => 'member', 'icon' => 'fas fa-user', 'color' => 'bg-success', 'text' => 'ลูกค้าสมัครใหม่', 'value' => $member_cnt],
                ['method' => 'agent', 'icon' => 'fas fa-user', 'color' => 'bg-success', 'text' => 'Agent', 'value' => config('game.starvegas.merchant_admin_name')]
            ],
            'bank' => $banks,
            'money' => [
                ['method' => 'deposit', 'icon' => 'fas fa-plus-circle', 'color' => 'bg-info', 'text' => 'ยอดฝาก', 'value' => core()->currency($deposit)],
                ['method' => 'withdraw', 'icon' => 'fas fa-minus-circle', 'color' => 'bg-danger', 'text' => 'ยอดถอน', 'value' => core()->currency($withdraw)],
                ['method' => 'withdraw', 'icon' => 'fas fa-minus-circle', 'color' => 'bg-danger', 'text' => 'คงเหลือ', 'value' => core()->currency($deposit - $withdraw)],

            ]
        ];

        return Response::json($response);
    }


}
