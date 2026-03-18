<?php

namespace Gametech\Wallet\Http\Controllers;

use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankRepository;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HistoryController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected $bankRepository;

    protected $memberRepository;

    public $method;

    /**
     * Create a new Repository instance.
     */
    public function __construct(
        BankRepository $bankRepo,
        MemberRepository $memberRepo
    ) {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->bankRepository = $bankRepo;

        $this->memberRepository = $memberRepo;

        $this->method = ['TOPUP' => Lang::get('app.status.refill') , 'WITHDRAW' => Lang::get('app.status.withdraw') , 'ROLLBACK' => Lang::get('app.status.rollback') , 'SETWALLET' => Lang::get('app.status.setwallet') , 'BONUS' => Lang::get('app.status.bonus')];
    }

    public function index()
    {
        //       $banks = $this->bankRepository->getBankAccount();
        //
        //       $profile = auth()->guard('customer')->user()->with('bank')->first();

        $config = core()->getConfigData();

        $banks[] = ['method' => 'deposit', 'name' => Lang::get('app.home.refill'), 'color' => 'green', 'id' => 'deposit-tab', 'href' => '#deposit', 'select' => 'true'];
        $banks[] = ['method' => 'withdraw', 'name' => Lang::get('app.home.withdraw'), 'color' => 'red', 'id' => 'withdraw-tab', 'href' => '#withdraw', 'select' => 'false'];
        if ($config->multigame_open == 'Y') {
            $banks[] = ['method' => 'transfer', 'name' => Lang::get('app.home.transfer'), 'color' => 'red', 'id' => 'transfer-tab', 'href' => '#transfer', 'select' => 'false'];
        }
        if ($config->freecredit_open == 'N') {
            if ($config->wheel_open == 'Y') {
                $banks[] = ['method' => 'spin', 'name' => Lang::get('app.home.wheel'), 'color' => 'green', 'id' => 'spin-tab', 'href' => '#spin', 'select' => 'false'];
            }
        }
        if ($config->money_tran_open == 'Y') {
            $banks[] = ['method' => 'money', 'name' => 'โอนเงิน', 'color' => 'red', 'id' => 'money-tab', 'href' => '#money', 'select' => 'false'];
        }

        $banks = collect($banks);

        return view($this->_config['view'], compact('banks'));
    }

    public function store()
    {
        $result['success'] = true;
        $date_start = request()->input('date_start');
        $date_stop = request()->input('date_stop');
        $id = request()->input('id');

        switch ($id) {
            case 'deposit':
                $result['data'] = $this->loadBillType($this->id(), 'TOPUP', $date_start, $date_stop);
                break;
            case 'withdraw':
                $result['data'] = $this->loadBillType($this->id(), 'WITHDRAW', $date_start, $date_stop);
                break;
            case 'transfer':
                $result['data'] = $this->loadBill($this->id(), $date_start, $date_stop);
                break;
            case 'spin':
                $result['data'] = $this->loadSpin($this->id(), $date_start, $date_stop);
                break;
            case 'money':
                $result['data'] = $this->loadMoneyTran($this->id(), $date_start, $date_stop);
                break;
            case 'cashback':
                $result['data'] = $this->loadCashback($date_start, $date_stop);
                break;
            case 'memberic':
                $result['data'] = $this->loadIC($date_start, $date_stop);
                break;
            case 'bonus':
                $result['data'] = $this->loadBonus($this->id(), $date_start, $date_stop);
                break;
            case 'other':
                $result['data'] = $this->loadBillTypeArr($this->id(), ['ROLLBACK','SETWALLET'], $date_start, $date_stop);
                break;
            default:
                $result['data'] = '';
        }

        return json_encode($result);

    }

    public function loadBillType($id, $method, $date_start = null, $date_stop = null)
    {

        $responses = collect(app('Gametech\Member\Repositories\MemberRepository')->loadBillType($id, $method, $date_start, $date_stop)->toArray());
        $responses = $responses->map(function ($items) {
            $item = (object) $items;
            $image = ['N' => 'ic_fail', 'Y' => 'ic_success', 'R' => 'ic_fail'];
            $status = ['N' => Lang::get('app.status.wait'), 'Y' => Lang::get('app.status.success'), 'R' => Lang::get('app.status.cancel')];
            $color = ['N' => 'bg-info', 'Y' => 'bg-success', 'R' => 'bg-danger'];

            return [
                'id' => '#DP'.Str::of($item->code)->padLeft(8, 0),
                'date_create' => core()->formatDate($item->date_create, 'd/m/Y H:i'),
                'amount' => $item->amount,
                'amount_request' => $item->amount_request,
                'pro_name' => $item->pro_name,
                'credit_bonus' => $item->credit_bonus,
                'credit_before' => $item->credit_before,
                'credit_after' => $item->credit_after,
                'status' => $item->complete,
                'image' => $image[$item->complete],
                'transfer_type' => ($item->transfer_type == 1 ? '+' : '-'),
                'method' => $this->method[$item->method],
                'status_color' => $color[$item->complete],
                'status_display' => $status[$item->complete],
            ];

        });

        return $responses;

    }

    public function loadBonus($id, $date_start = null, $date_stop = null)
    {

        $responses = collect(app('Gametech\Member\Repositories\MemberRepository')->loadBonus($id,$date_start, $date_stop)->toArray());
        $responses = $responses->map(function ($items) {
            $item = (object) $items;
            $image = ['N' => 'ic_fail', 'Y' => 'ic_success', 'R' => 'ic_fail'];
            $status = ['N' => Lang::get('app.status.wait'), 'Y' => Lang::get('app.status.success'), 'R' => Lang::get('app.status.cancel')];
            $color = ['N' => 'bg-info', 'Y' => 'bg-success', 'R' => 'bg-danger'];

            return [
                'id' => '#DP'.Str::of($item->code)->padLeft(8, 0),
                'date_create' => core()->formatDate($item->date_create, 'd/m/Y H:i'),
                'amount' => ($item->credit_bonus > 0 ?$item->credit_bonus : $item->amount),
                'pro_name' => $item->pro_name,
                'credit_bonus' => $item->credit_bonus,
                'credit_before' => $item->credit_before,
                'credit_after' => $item->credit_after,
                'status' => $item->complete,
                'image' => $image[$item->complete],
                'transfer_type' => ($item->transfer_type == 1 ? '+' : '-'),
                'method' => $item->pro_name,
                'status_color' => $color[$item->complete],
                'status_display' => $status[$item->complete],
            ];

        });

        return $responses;

    }

    public function loadBillTypeArr($id, $method, $date_start = null, $date_stop = null)
    {

        $responses = collect(app('Gametech\Member\Repositories\MemberRepository')->loadBillTypeArr($id, $method, $date_start, $date_stop)->toArray());
        $responses = $responses->map(function ($items) {
            $item = (object) $items;
            $image = ['N' => 'ic_fail', 'Y' => 'ic_success', 'R' => 'ic_fail'];
            $status = ['N' => Lang::get('app.status.wait'), 'Y' => Lang::get('app.status.success'), 'R' => Lang::get('app.status.cancel')];
            $color = ['N' => 'bg-info', 'Y' => 'bg-success', 'R' => 'bg-danger'];

            return [
                'id' => '#DP'.Str::of($item->code)->padLeft(8, 0),
                'date_create' => core()->formatDate($item->date_create, 'd/m/Y H:i'),
                'amount' => $item->amount,
                'pro_name' => $item->pro_name,
                'credit_bonus' => $item->credit_bonus,
                'credit_before' => $item->credit_before,
                'credit_after' => $item->credit_after,
                'status' => $item->complete,
                'image' => $image[$item->complete],
                'transfer_type' => ($item->transfer_type == 1 ? '+' : '-'),
                'method' => $this->method[$item->method],
                'status_color' => $color[$item->complete],
                'status_display' => $status[$item->complete],
            ];

        });

        return $responses;

    }

    public function loadBill($id, $date_start = null, $date_stop = null)
    {

        $responses = collect(app('Gametech\Member\Repositories\MemberRepository')->loadBill($id, $date_start, $date_stop)->toArray());

        $responses = $responses->map(function ($items) {
            $item = (object) $items;
            $status = ['0' => Lang::get('app.status.wait'), '1' => Lang::get('app.status.confirm'), '2' => Lang::get('app.status.cancel')];
            $color = ['0' => 'bg-info', '1' => 'bg-success', '2' => 'bg-danger'];

            return [
                'code' => $item->code,
                'id' => '#BL'.Str::of($item->code)->padLeft(8, 0),
                'promotion_name' => (! is_null($item->promotion) ? $item->promotion['name_th'] : 'ไม่มี'),
                'date_create' => core()->formatDate($item->date_create, 'd/m/y H:i'),
                'amount' => $item->amount,
                'balance_before' => $item->balance_before,
                'balance_after' => $item->balance_after,
                'credit' => $item->credit,
                'credit_bonus' => $item->credit_bonus,
                'credit_balance' => $item->credit_balance,
                'credit_before' => $item->credit_before,
                'credit_after' => $item->credit_after,
                'game_name' => $item->game['name'],
                'filepic' => $item->transfer_type == 1 ? Storage::url('game_img/'.$item->game['filepic']) : Storage::url('game_img/wallet.png'),
                'transfer' => $item->transfer_type == 1 ? 'Wallet -> Game (โยกเข้าเกม)' : 'Wallet <- Game (โยกออกเกม)',
                'status' => $item->transfer_type == 1 ? 'text-success' : 'text-danger',
            ];

        });

        return $responses;

    }

    public function loadSpin($id, $date_start = null, $date_stop = null)
    {

        $responses = collect(app('Gametech\Member\Repositories\MemberRepository')->loadSpin($id, $date_start, $date_stop)->toArray());

        $responses = $responses->map(function ($items) {
            $item = (object) $items;
            $image = ['0' => 'ic_fail', '1' => 'ic_success', '2' => 'ic_fail'];
            $status = ['0' => Lang::get('app.status.wait'), '1' => Lang::get('app.status.success'), '2' => Lang::get('app.status.cancel')];
            $color = ['0' => 'bg-info', '1' => 'bg-success', '2' => 'bg-danger'];

            return [
                'code' => $item->code,
                'id' => '#SP'.Str::of($item->code)->padLeft(8, 0),
                'date_create' => core()->formatDate($item->date_create, 'd/m/y H:i'),
                'amount' => $item->amount,
                'image' => $image[1],
                'transfer_type' => '',
                'method' => Lang::get('app.home.wheels'),
                'status' => 1,
                'status_color' => $color[1],
                'status_display' => $item->bonus_name,
            ];

        });

        return $responses;

    }

    public function loadMoneyTran($id, $date_start = null, $date_stop = null)
    {

        $responses = collect(app('Gametech\Member\Repositories\MemberRepository')->loadMoneyTran($id, $date_start, $date_stop)->toArray());

        $responses = $responses->map(function ($items) {
            $item = (object) $items;
            $status = ['D' => 'รับโอน', 'W' => 'โอน'];
            $color = ['D' => 'bg-info', 'W' => 'bg-success'];

            return [
                'code' => $item->code,
                'id' => $item->remark,
                'date_create' => core()->formatDate($item->date_create, 'd/m/y H:i'),
                'amount' => $item->amount,
                'status' => $item->credit_type,
                'status_color' => $color[$item->credit_type],
                'status_display' => $status[$item->credit_type],
            ];

        });

        return $responses;

    }

    public function loadCashback($date_start = null, $date_stop = null)
    {

        $responses = collect(app('Gametech\Member\Repositories\MemberRepository')->loadCashbackNew($this->id(), $date_start, $date_stop)->toArray());

        $responses = $responses->map(function ($items) {

            $item = (object) $items;
            $image = ['0' => 'ic_fail', '1' => 'ic_success', '2' => 'ic_fail'];
            $status = ['0' => Lang::get('app.status.wait'), '1' => Lang::get('app.status.success'), '2' => Lang::get('app.status.cancel')];
            $color = ['0' => 'bg-info', '1' => 'bg-success', '2' => 'bg-danger'];

            if ($item->credit_type == 'D') {

                $item->status_display = 'โอนเข้า';
                $item->status = 1;
            } elseif ($item->credit_type == 'W') {

                $item->status_display = 'โอนออก';
                $item->status = 1;
            }

            return [
                'code' => $item->code,
                'id' => 'ยอดเงินคืน จากการคำนวน',
                'date_create' => core()->formatDate($item->date_create, 'd/m/y H:i'),
                'amount' => $item->amount,
                'credit_before' => $item->credit_before,
                'credit_after' => $item->credit_balance,
                'image' => $image[1],
                'transfer_type' => '',
                'method' => Lang::get('app.home.cashback'),
                'status' => 1,
                'status_color' => $color[1],
                'status_display' => $status[1],
            ];

        });

        return $responses;

    }

    public function loadIC($date_start = null, $date_stop = null)
    {

        $responses = collect(app('Gametech\Member\Repositories\MemberRepository')->loadIC($this->id(), $date_start, $date_stop)->toArray());

        $responses = $responses->map(function ($items) {

            $item = (object) $items;
            $image = ['0' => 'ic_fail', '1' => 'ic_success', '2' => 'ic_fail'];
            $status = ['0' => Lang::get('app.status.wait'), '1' => Lang::get('app.status.success'), '2' => Lang::get('app.status.cancel')];
            $color = ['0' => 'bg-info', '1' => 'bg-success', '2' => 'bg-danger'];
            //            dd($item);
            //            $this->sum_ic += $item->ic;
            //            $this->sum_cashback += $item->cashback;

            if ($item->credit_type == 'D') {

                $item->status_display = 'โอนเข้า';
                $item->status = 1;
            } elseif ($item->credit_type == 'W') {

                $item->status_display = 'โอนออก';
                $item->status = 1;
            }

            return [
                'code' => $item->code,
                'id' => 'ยอดเสียเพื่อน จากการคำนวน',
                'date_create' => core()->formatDate($item->date_create, 'd/m/y H:i'),
                'amount' => $item->amount,
                'credit_before' => $item->credit_before,
                'credit_after' => $item->credit_balance,
                'image' => $image[1],
                'transfer_type' => '',
                'method' => Lang::get('app.home.ic'),
                'status' => 1,
                'status_color' => $color[1],
                'status_display' => $status[1],
            ];

        });

        return $responses;

    }

    public function loadDeposit($id, $date_start = null, $date_stop = null)
    {

        $responses = collect(app('Gametech\Member\Repositories\MemberRepository')->loadDeposit($id, $date_start, $date_stop)->toArray());
        $responses = $responses->map(function ($items) {
            $item = (object) $items;
            $image = ['N' => 'ic_fail', 'Y' => 'ic_success', 'R' => 'ic_fail'];
            $status = ['N' => Lang::get('app.status.wait'), 'Y' => Lang::get('app.status.success'), 'R' => Lang::get('app.status.cancel')];
            $color = ['N' => 'bg-info', 'Y' => 'bg-success', 'R' => 'bg-danger'];

            return [
                'id' => '#DP'.Str::of($item->code)->padLeft(8, 0),
                'date_create' => core()->formatDate($item->date_create, 'd/m/Y H:i'),
                'amount' => $item->amount,
                'pro_name' => $item->pro_name,
                'credit_bonus' => $item->credit_bonus,
                'credit_before' => $item->credit_before,
                'credit_after' => $item->credit_after,
                'status' => $item->complete,
                'image' => $image[$item->complete],
                'method' => Lang::get('app.home.refill'),
                'status_color' => $color[$item->complete],
                'status_display' => $status[$item->complete],
            ];

        });

        return $responses;

    }

    public function loadWithdraw_($id, $date_start = null, $date_stop = null)
    {

        $config = core()->getConfigData();

        if ($config->seamless == 'Y') {
            $responses = collect(app('Gametech\Member\Repositories\MemberRepository')->loadWithdrawSeamless($id, $date_start, $date_stop)->toArray());

        } else {
            $responses = collect(app('Gametech\Member\Repositories\MemberRepository')->loadWithdraw($id, $date_start, $date_stop)->toArray());

        }

        $responses = $responses->map(function ($items) {
            $item = (object) $items;
            $image = ['0' => 'ic_fail', '1' => 'ic_success', '2' => 'ic_fail'];
            $status = ['0' => Lang::get('app.status.wait'), '1' => Lang::get('app.status.confirm'), '2' => Lang::get('app.status.cancel')];
            $color = ['0' => 'bg-info', '1' => 'bg-success', '2' => 'bg-danger'];

            return [
                'code' => $item->code,
                'id' => '#WD'.Str::of($item->code)->padLeft(8, 0),
                'date_create' => core()->formatDate($item->date_create, 'd/m/Y H:i'),
                'amount' => $item->amount,
                'credit_before' => $item->oldcredit,
                'credit_after' => $item->aftercredit,
                'status' => $item->status,
                'image' => $image[$item->status],
                'method' => Lang::get('app.home.withdraw'),
                'status_color' => $color[$item->status],
                'status_display' => $status[$item->status],
            ];

        });

        return $responses;

    }

    public function loadWithdraw($id, $date_start = null, $date_stop = null)
    {

        $responses = collect(app('Gametech\Member\Repositories\MemberRepository')->loadWithdraw($id, $date_start, $date_stop)->toArray());
        $responses = $responses->map(function ($items) {
            $item = (object) $items;
            $image = ['N' => 'ic_fail', 'Y' => 'ic_success', 'R' => 'ic_fail'];
            $status = ['N' => Lang::get('app.status.wait'), 'Y' => Lang::get('app.status.success'), 'R' => Lang::get('app.status.cancel'), 'F' => Lang::get('app.status.cancel')];
            $color = ['N' => 'bg-info', 'Y' => 'bg-success', 'R' => 'bg-danger'];

            return [
                'id' => '#DP'.Str::of($item->code)->padLeft(8, 0),
                'date_create' => core()->formatDate($item->date_create, 'd/m/Y H:i'),
                'amount' => $item->amount,
                'pro_name' => $item->pro_name,
                'credit_bonus' => $item->credit_bonus,
                'credit_before' => $item->credit_before,
                'credit_after' => $item->credit_after,
                'status' => $item->complete,
                'image' => $image[$item->complete],
                'method' => Lang::get('app.home.withdraw'),
                'status_color' => $color[$item->complete],
                'status_display' => $status[$item->complete],
            ];

        });

        return $responses;

    }
}
