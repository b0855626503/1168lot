<?php

namespace Gametech\Admin\Http\Controllers;

use App\Libraries\KbankOut;
use App\Libraries\ScbOut;
use App\Services\Dashboard\DashboardWebCodeResolver;
use Carbon\Carbon;
use Gametech\Admin\Services\DashboardService;
use Gametech\Member\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Klevze\OnlineUsers\Facades\OnlineUsers;

class DashboardController extends AppBaseController
{
    protected $_config;

    /**
     * string object
     *
     * @var \Illuminate\Support\Carbon
     */
    protected $startDate;

    /**
     * string object
     *
     * @var \Illuminate\Support\Carbon
     */
    protected $lastStartDate;

    /**
     * string object
     *
     * @var \Illuminate\Support\Carbon
     */
    protected $endDate;

    /**
     * string object
     *
     * @var \Illuminate\Support\Carbon
     */
    protected $lastEndDate;

    public function __construct()
    {
        $this->_config = request('_config');

        $this->middleware('admin');
    }

    public function index()
    {
        $this->setStartEndDate();

        return view($this->_config['view'], [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'dashboardWebCode' => app(DashboardWebCodeResolver::class)->resolve(),
        ]);
    }

    public function setStartEndDate()
    {
        $this->startDate = request()->get('start')
            ? Carbon::createFromTimeString(request()->get('start').' 00:00:01')
            : Carbon::createFromTimeString(Carbon::now()->subDays(30)->format('Y-m-d').' 00:00:01');

        $this->endDate = request()->get('end')
            ? Carbon::createFromTimeString(request()->get('end').' 23:59:59')
            : Carbon::now();

        if ($this->endDate > Carbon::now()) {
            $this->endDate = Carbon::now();
        }

        $this->lastStartDate = clone $this->startDate;
        $this->lastEndDate = clone $this->startDate;

        $this->lastStartDate->subDays($this->startDate->diffInDays($this->endDate));
        // $this->lastEndDate->subDays($this->lastStartDate->diffInDays($this->lastEndDate));
    }

    public function loadCnt()
    {
        $startOfToday = now()->startOfDay();
        $startOfTomorrow = (clone $startOfToday)->addDay();
        $startdate = $startOfToday->toDateTimeString();
        $enddate = (clone $startOfTomorrow)->subSecond()->toDateTimeString();

        $config = $this->getCoreConfig();

        $bankCounters = DB::selectOne(
            'SELECT
                (SELECT COUNT(*)
                 FROM bank_payment bp1
                 WHERE bp1.value > 0
                   AND bp1.enable = ?
                   AND bp1.status = ?
                   AND bp1.date_create >= ?
                   AND bp1.date_create < ?) AS bank_in_today,
                (SELECT COUNT(*)
                 FROM bank_payment bp2
                 WHERE bp2.value > 0
                   AND bp2.enable = ?
                   AND bp2.status = ?
                   AND bp2.date_create < ?) AS bank_in,
                (SELECT COUNT(*)
                 FROM bank_payment bp3
                 WHERE bp3.value < 0
                   AND bp3.enable = ?
                   AND bp3.status = ?
                   AND bp3.autocheck = ?
                   AND bp3.date_create BETWEEN ? AND ?) AS bank_out',
            [
                'Y', 0, $startdate, $startOfTomorrow->toDateTimeString(),
                'Y', 0, $startdate,
                'Y', 0, 'N', $startdate, $enddate,
            ]
        );

        $bank_in_today = (int) ($bankCounters->bank_in_today ?? 0);
        $bank_in = (int) ($bankCounters->bank_in ?? 0);
        $bank_out = (int) ($bankCounters->bank_out ?? 0);

        if ($config->seamless == 'Y') {
            $withdraw = app('Gametech\Payment\Repositories\WithdrawRepository')
                ->active()->waiting()
                ->count();
            $withdraw_free = app('Gametech\Payment\Repositories\WithdrawFreeRepository')
                ->active()->waiting()
                ->count();

            //            $withdraw_free = 0;
        } else {
            $withdraw = app('Gametech\Payment\Repositories\WithdrawRepository')
                ->active()->waiting()
                ->count();
            $withdraw_free = app('Gametech\Payment\Repositories\WithdrawFreeRepository')
                ->active()->waiting()
                ->count();
        }

        $payment_waiting = app('Gametech\Payment\Repositories\PaymentWaitingRepository')
            ->where('date_create', '>=', '2021-04-06 00:00:00')
            ->active()->waiting()
            ->count();

        $member_confirm = app('Gametech\Member\Repositories\MemberRepository')
            ->active()->waiting()
            ->count();

        $announce = [
            'content' => '',
            'updated_at' => now()->toDateTimeString(),
        ];

        $announce_new = 'N';

        $response = Http::get('https://api.168csn.com/api/announce');

        if ($response->successful()) {
            $response = $response->json();
            //            dd($response);
            $announce = $response['data'];
        }
        //        $announce = '';
        //        dd($announce);

        if ($announce != '') {
            if (! Cache::has($this->id().'announce_start')) {
                Cache::add($this->id().'announce_stop', $announce['updated_at']);
            }
            if (! Cache::has($this->id().'announce_stop')) {
                Cache::add($this->id().'announce_stop', $announce['updated_at']);
            } else {
                Cache::put($this->id().'announce_stop', $announce['updated_at']);
            }

            $start = Cache::get($this->id().'announce_start');
            $stop = Cache::get($this->id().'announce_stop');
            if ($start != $stop) {
                $announce_new = 'Y';
                Cache::put($this->id().'announce_start', $stop);
            }
        }

        $result['member_confirm'] = $member_confirm;
        $result['bank_in_today'] = $bank_in_today;
        $result['bank_in'] = $bank_in;
        $result['bank_out'] = $bank_out;
        $result['withdraw'] = $withdraw;
        $result['withdraw_free'] = $withdraw_free;
        $result['payment_waiting'] = $payment_waiting;
        $result['announce'] = $announce['content'];
        $result['announce_new'] = $announce_new;

        //        Artisan::call('migrate --force');
        //        Artisan::call('queue:restart');

        return $this->sendResponseNew($result, 'Complete');

    }

    public function loadSum(Request $request)
    {
        $config = $this->getCoreConfig();
        $startdate = now()->toDateString();
        //        $startdate = '2021-02-10';
        $method = $request->input('method');
        $startDate = $request->input('date_start') ?: now()->toDateString();
        $endDate = $request->input('date_end') ?: now()->toDateString();

        $rangeStart = Carbon::parse($startDate);
        $rangeEnd = Carbon::parse($endDate);
        if ($rangeEnd->lt($rangeStart)) {
            [$rangeStart, $rangeEnd] = [$rangeEnd, $rangeStart];
        }
        $startDate = $rangeStart->toDateString();
        $endDate = $rangeEnd->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $calc = function ($method) use ($config, $startDate, $endDate, $monthStart, $monthEnd, $startdate) {
            $data = 0;
            switch ($method) {
            case 'setdeposit':
                $data = app('Gametech\Member\Repositories\MemberCreditLogRepository')->active()->where('kind', 'SETWALLET')->where('credit_type', 'D')
//                    ->whereDate('date_create', $startdate)
                    ->whereBetween(DB::raw('DATE(date_create)'), [$startDate, $endDate])
                    ->sum('amount');
                $data = core()->currency($data);
                break;
            case 'setwithdraw':
                $data = app('Gametech\Member\Repositories\MemberCreditLogRepository')->active()->where('kind', 'SETWALLET')->where('credit_type', 'W')
                    ->whereBetween(DB::raw('DATE(date_create)'), [$startDate, $endDate])
//                    ->whereDate('date_create', $startdate)
                    ->sum('amount');
                $data = core()->currency($data);
                break;
            case 'deposit':
                $data = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()->whereIn('status', [0, 1])
//                    ->whereDate('date_create', $startdate)
//                    ->whereBetween(DB::raw('DATE(date_create)'), [$startDate, $endDate])
                    ->whereBetween(DB::raw('DATE(date_create)'), [$monthStart, $monthEnd])
                    ->sum('value');

                //                $data = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()->complete()->whereDate('date_create', $startdate)->sum('value');
                $data = core()->currency($data);
                break;

            case 'deposit-today':
                $data = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()->whereIn('status', [0, 1])
//                    ->whereDate('date_create', $startdate)
                    ->whereBetween(DB::raw('DATE(date_create)'), [$startDate, $endDate])
//                    ->whereBetween(DB::raw('DATE(date_create)'), [$monthStart, $monthEnd])
                    ->sum('value');

                //                $data = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()->complete()->whereDate('date_create', $startdate)->sum('value');
                $data = core()->currency($data);
                break;
            case 'deposit_wait':
                $data = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()->waiting()
                    ->where('autocheck', 'Y')
                    ->whereBetween(DB::raw('DATE(date_approve)'), [$monthStart, $monthEnd])->sum('value');

//                    ->whereDate('date_create', $startdate)
//                    ->whereBetween(DB::raw('DATE(date_create)'), [$startDate, $endDate])
//                    ->sum('value');
                $data = core()->currency($data);
                break;
            case 'withdraw':
                if ($config->seamless == 'Y') {
                    $data1 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
//                        ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate])->sum('amount');
                    ->whereBetween(DB::raw('DATE(date_approve)'), [$monthStart, $monthEnd])->sum('amount');

                } else {
                    $data1 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
                     ->whereBetween(DB::raw('DATE(date_approve)'), [$monthStart, $monthEnd])->sum('amount');
                }
                //                $data1 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()->whereRaw(DB::raw("DATE_FORMAT(date_approve,'%Y-%m-%d') = ?"), [$startdate])->sum('amount');
                //                $data2 = app('Gametech\Payment\Repositories\WithdrawFreeRepository')->active()->complete()->whereRaw(DB::raw("DATE_FORMAT(date_approve,'%Y-%m-%d') = ?"), [$startdate])->sum('amount');
                $data = core()->currency($data1);
                break;

            case 'withdraw-today':
                if ($config->seamless == 'Y') {
                    $data1 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
//                        ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate])->sum('amount');
                        ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate])->sum('amount');

                } else {
                    $data1 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
                        ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate])->sum('amount');
                }
                //                $data1 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()->whereRaw(DB::raw("DATE_FORMAT(date_approve,'%Y-%m-%d') = ?"), [$startdate])->sum('amount');
                //                $data2 = app('Gametech\Payment\Repositories\WithdrawFreeRepository')->active()->complete()->whereRaw(DB::raw("DATE_FORMAT(date_approve,'%Y-%m-%d') = ?"), [$startdate])->sum('amount');
                $data = core()->currency($data1);
                break;
            case 'bonus':
                $data1 = app('Gametech\Payment\Repositories\PaymentPromotionRepository')->active()->aff()
//                    ->whereBetween(DB::raw('DATE(date_create)'), [$startDate, $endDate])->sum('credit_bonus');
                        ->whereBetween(DB::raw('DATE(date_create)'), [$monthStart, $monthEnd])->sum('credit_bonus');


                $data2 = app('Gametech\Payment\Repositories\BillRepository')->active()->getpro()->where('transfer_type', 1)
                    ->whereBetween(DB::raw('DATE(date_create)'), [$monthStart, $monthEnd])
                    ->sum('credit_bonus');
                $data = core()->currency($data1 + $data2);
                break;

            case 'bonus-today':
                $data1 = app('Gametech\Payment\Repositories\PaymentPromotionRepository')->active()->aff()
//                    ->whereBetween(DB::raw('DATE(date_create)'), [$startDate, $endDate])->sum('credit_bonus');
                    ->whereBetween(DB::raw('DATE(date_create)'), [$startDate, $endDate])->sum('credit_bonus');


                $data2 = app('Gametech\Payment\Repositories\BillRepository')->active()->getpro()->where('transfer_type', 1)
                    ->whereBetween(DB::raw('DATE(date_create)'), [$startDate, $endDate])
                    ->sum('credit_bonus');
                $data = core()->currency($data1 + $data2);
                break;
            case 'balance':
                //                $data1 = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()->complete()->whereDate('date_create', $startdate)->sum('value');
                $data1 = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()->whereIn('status', [0, 1])
//                    ->whereDate('date_create', $startdate)
//                    ->whereBetween(DB::raw('DATE(date_create)'), [$startDate, $endDate])
                    ->whereBetween(DB::raw('DATE(date_create)'), [$monthStart, $monthEnd])

                    ->sum('value');
                if ($config->seamless == 'Y') {
                    $data2 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
                        ->whereBetween(DB::raw('DATE(date_approve)'), [$monthStart, $monthEnd])->sum('amount');
//                        ->whereRaw(DB::raw("DATE_FORMAT(date_approve,'%Y-%m-%d') = ?"), [$startdate])->sum('amount');
                    //
                } else {
                    $data2 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
                        ->whereBetween(DB::raw('DATE(date_approve)'), [$monthStart, $monthEnd])->sum('amount');

//                        ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate])->sum('amount');
                    //
                }

                //                $data2 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()->whereRaw(DB::raw("DATE_FORMAT(date_approve,'%Y-%m-%d') = ?"), [$startdate])->sum('amount');
                //                $data3 = app('Gametech\Payment\Repositories\PaymentPromotionRepository')->active()->aff()->whereRaw(DB::raw("DATE_FORMAT(date_create,'%Y-%m-%d') = ?"), [$startdate])->sum('credit_bonus');
                //                $data4 = app('Gametech\Payment\Repositories\BillRepository')->active()->getpro()->where('transfer_type', 1)->whereRaw(DB::raw("DATE_FORMAT(date_create,'%Y-%m-%d') = ?"), [$startdate])->sum('credit_bonus');

                $data = core()->currency(($data1 - $data2));
                break;

            case 'balance-today':
                //                $data1 = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()->complete()->whereDate('date_create', $startdate)->sum('value');
                $data1 = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()->whereIn('status', [0, 1])
//                    ->whereDate('date_create', $startdate)
//                    ->whereBetween(DB::raw('DATE(date_create)'), [$startDate, $endDate])
                    ->whereBetween(DB::raw('DATE(date_create)'), [$startDate, $endDate])

                    ->sum('value');
                if ($config->seamless == 'Y') {
                    $data2 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
                        ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate])->sum('amount');
//                        ->whereRaw(DB::raw("DATE_FORMAT(date_approve,'%Y-%m-%d') = ?"), [$startdate])->sum('amount');
                    //
                } else {
                    $data2 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
                        ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate])->sum('amount');

//                        ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate])->sum('amount');
                    //
                }

                //                $data2 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()->whereRaw(DB::raw("DATE_FORMAT(date_approve,'%Y-%m-%d') = ?"), [$startdate])->sum('amount');
                //                $data3 = app('Gametech\Payment\Repositories\PaymentPromotionRepository')->active()->aff()->whereRaw(DB::raw("DATE_FORMAT(date_create,'%Y-%m-%d') = ?"), [$startdate])->sum('credit_bonus');
                //                $data4 = app('Gametech\Payment\Repositories\BillRepository')->active()->getpro()->where('transfer_type', 1)->whereRaw(DB::raw("DATE_FORMAT(date_create,'%Y-%m-%d') = ?"), [$startdate])->sum('credit_bonus');

                $data = core()->currency(($data1 - $data2));
                break;

            case 'new-member-deposit-month':
                $data = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()->whereIn('status', [0, 1])
                    ->whereBetween(DB::raw('DATE(date_create)'), [$monthStart, $monthEnd])
                    ->whereHas('member', function ($query) use ($monthStart, $monthEnd) {
                        $query->whereBetween(DB::raw('DATE(date_regis)'), [$monthStart, $monthEnd]);
                    })
                    ->sum('value');

                $data = core()->currency($data);
                break;

            case 'new-member-withdraw-month':
                if ($config->seamless == 'Y') {
                    $data1 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
                        ->whereBetween(DB::raw('DATE(date_approve)'), [$monthStart, $monthEnd])
                        ->whereHas('member', function ($query) use ($monthStart, $monthEnd) {
                            $query->whereBetween(DB::raw('DATE(date_regis)'), [$monthStart, $monthEnd]);
                        })
                        ->sum('amount');
                } else {
                    $data1 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
                        ->whereBetween(DB::raw('DATE(date_approve)'), [$monthStart, $monthEnd])
                        ->whereHas('member', function ($query) use ($monthStart, $monthEnd) {
                            $query->whereBetween(DB::raw('DATE(date_regis)'), [$monthStart, $monthEnd]);
                        })
                        ->sum('amount');
                }

                $data = core()->currency($data1);
                break;

            case 'new-member-bonus-month':
                $data1 = app('Gametech\Payment\Repositories\PaymentPromotionRepository')->active()->aff()
                    ->whereBetween(DB::raw('DATE(date_create)'), [$monthStart, $monthEnd])
                    ->whereHas('member', function ($query) use ($monthStart,$monthEnd) {
                        $query->whereBetween(DB::raw('DATE(date_regis)'), [$monthStart, $monthEnd]);
                    })
                    ->sum('credit_bonus');
                $data2 = app('Gametech\Payment\Repositories\BillRepository')->active()->getpro()->where('transfer_type', 1)
                    ->whereBetween(DB::raw('DATE(date_create)'), [$monthStart, $monthEnd])
                    ->whereHas('member', function ($query) use ($monthStart,$monthEnd) {
                        $query->whereBetween(DB::raw('DATE(date_regis)'), [$monthStart, $monthEnd]);
                    })
                    ->sum('credit_bonus');
                $data = core()->currency($data1 + $data2);
                break;

            case 'new-member-balance-month':
                $data1 = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()->whereIn('status', [0, 1])
                    ->whereBetween(DB::raw('DATE(date_create)'), [$monthStart, $monthEnd])
                    ->whereHas('member', function ($query) use ($monthStart,$monthEnd) {
                        $query->whereBetween(DB::raw('DATE(date_regis)'), [$monthStart, $monthEnd]);
                    })
                    ->sum('value');
                if ($config->seamless == 'Y') {
                    $data2 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
                        ->whereBetween(DB::raw('DATE(date_approve)'), [$monthStart, $monthEnd])
                        ->whereHas('member', function ($query) use ($monthStart,$monthEnd) {
                            $query->whereBetween(DB::raw('DATE(date_regis)'), [$monthStart, $monthEnd]);
                        })
                        ->sum('amount');
                } else {
                    $data2 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
                        ->whereBetween(DB::raw('DATE(date_approve)'), [$monthStart, $monthEnd])
                        ->whereHas('member', function ($query) use ($monthStart,$monthEnd) {
                            $query->whereBetween(DB::raw('DATE(date_regis)'), [$monthStart, $monthEnd]);
                        })
                        ->sum('amount');
                }

                $data = core()->currency(($data1 - $data2));
                break;

            case 'new-member-deposit-today':
                $data = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()->whereIn('status', [0, 1])
                    ->whereBetween(DB::raw('DATE(date_create)'), [$startDate, $endDate])
                    ->whereHas('member', function ($query) use ($startDate, $endDate) {
                        $query->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate]);
                    })
                    ->sum('value');

                $data = core()->currency($data);
                break;

            case 'new-member-withdraw-today':
                if ($config->seamless == 'Y') {
                    $data1 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
                        ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate])
                        ->whereHas('member', function ($query) use ($startDate, $endDate) {
                            $query->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate]);
                        })
                        ->sum('amount');
                } else {
                    $data1 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
                        ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate])
                        ->whereHas('member', function ($query) use ($startDate, $endDate) {
                            $query->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate]);
                        })
                        ->sum('amount');
                }

                $data = core()->currency($data1);
                break;

            case 'new-member-bonus-today':
                $data1 = app('Gametech\Payment\Repositories\PaymentPromotionRepository')->active()->aff()
                    ->whereBetween(DB::raw('DATE(date_create)'), [$startDate, $endDate])
                    ->whereHas('member', function ($query) use ($startDate, $endDate) {
                        $query->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate]);
                    })
                    ->sum('credit_bonus');
                $data2 = app('Gametech\Payment\Repositories\BillRepository')->active()->getpro()->where('transfer_type', 1)
                    ->whereBetween(DB::raw('DATE(date_create)'), [$startDate, $endDate])
                    ->whereHas('member', function ($query) use ($startDate, $endDate) {
                        $query->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate]);
                    })
                    ->sum('credit_bonus');
                $data = core()->currency($data1 + $data2);
                break;

            case 'new-member-balance-today':
                $data1 = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()->whereIn('status', [0, 1])
                    ->whereBetween(DB::raw('DATE(date_create)'), [$startDate, $endDate])
                    ->whereHas('member', function ($query) use ($startDate, $endDate) {
                        $query->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate]);
                    })
                    ->sum('value');
                if ($config->seamless == 'Y') {
                    $data2 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
                        ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate])
                        ->whereHas('member', function ($query) use ($startDate, $endDate) {
                            $query->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate]);
                        })
                        ->sum('amount');
                } else {
                    $data2 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
                        ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate])
                        ->whereHas('member', function ($query) use ($startDate, $endDate) {
                            $query->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate]);
                        })
                        ->sum('amount');
                }

                $data = core()->currency(($data1 - $data2));
                break;

            case 'register-today':

                $data = app('Gametech\Member\Repositories\MemberRepository')->active()
                    ->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate])
//                    ->whereDate('date_regis', now()->toDateString())
                    ->count();
                break;

            case 'register-deposit':

                $data = app('Gametech\Member\Repositories\MemberRepository')
                    ->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate])
//                    ->whereDate('date_regis', now()->toDateString())
                    ->whereHas('payment', function ($query) use ($startDate, $endDate) {
                        // จะกรองให้เฉพาะ member ที่มีรายการฝาก
                        $query->where('status', 1)->where('enable', 'Y')
                            ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate]);
                        //                            ->whereDate('date_approve', now()->toDateString());
                    })
                    ->count();

                break;

            case 'register-all-deposit':

                $data = app('Gametech\Member\Repositories\MemberRepository')
                    ->whereNotBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate])

//                    ->whereDate('date_regis', '!=', now()->toDateString())
                    ->whereHas('payment', function ($query) use ($startDate, $endDate) {
                        // จะกรองให้เฉพาะ member ที่มีรายการฝาก
                        $query->where('status', 1)->where('enable', 'Y')
                            ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate]);
//                            ->whereDate('date_approve', now()->toDateString());
                    })
                    ->count();

                break;

            case 'register-not-deposit':

                $data = app('Gametech\Member\Repositories\MemberRepository')
                    ->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate])
//                    ->whereDate('date_regis', now()->toDateString())
                    ->whereDoesntHave('payment', function ($query) use ($startDate, $endDate) {
                        // จะกรองให้เฉพาะ member ที่มีรายการฝาก
                        $query->where('status', 1)->where('enable', 'Y')
                            ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate]);
//                            ->whereDate('date_approve', now()->toDateString());
                    })
                    ->count();

                break;

            case 'register-downline':

                $data = app('Gametech\Member\Repositories\MemberRepository')->active()
//                    ->whereDate('date_regis', now()->toDateString())
                    ->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate])
                    ->where('upline_code', '>', 0)
                    ->count();
                break;

            case 'register-downline-deposit':

                $data = app('Gametech\Member\Repositories\MemberRepository')
                    ->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate])
//                    ->whereDate('date_regis', now()->toDateString())
                    ->where('upline_code', '>', 0)
                    ->whereHas('payment', function ($query) use ($startDate, $endDate) {
                        // จะกรองให้เฉพาะ member ที่มีรายการฝาก
                        $query->where('status', 1)->where('enable', 'Y')
                            ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate]);
//                            ->whereDate('date_approve', now()->toDateString());
                    })
                    ->count();

                break;

            case 'register-downline-not-deposit':

                $data = app('Gametech\Member\Repositories\MemberRepository')
                    ->whereBetween(DB::raw('DATE(date_regis)'), [$startDate, $endDate])
                    ->where('upline_code', '>', 0)
                    ->whereDoesntHave('payment', function ($query) use ($startDate, $endDate) {
                        // จะกรองให้เฉพาะ member ที่มีรายการฝาก
                        $query->where('status', 1)->where('enable', 'Y')
                            ->whereBetween(DB::raw('DATE(date_approve)'), [$startDate, $endDate]);
                    })
                    ->count();

                break;

            case 'user_online':
                $data1 = new Member;
                $data = $data1->allOnline();
                dd($data);
                break;

            case 'online':
                $data = OnlineUsers::getActiveUsers() ?? 0;
                //                $data  = DB::table('client_presence')
                //                    ->select(DB::raw('COUNT(DISTINCT client_id) AS online_clients'))
                //                    ->where('last_seen_at', '>=', DB::raw('NOW() - INTERVAL 5 MINUTE'))
                //                    ->value('online_clients');
                break;
            }

            return $data;
        };

        $methods = $request->input('methods');
        if (is_array($methods) && count($methods)) {
            $sums = [];
            foreach ($methods as $m) {
                $sums[$m] = $calc($m);
            }

            return $this->sendResponseNew(['sum' => $sums], 'Complete');
        }

        $data = $calc($method);

        return $this->sendResponseNew(['sum' => $data], 'Complete');
    }

    public function loadSumAll(Request $request)
    {
        $config = $this->getCoreConfig();

        //        $startdate = '2021-02-04';
        //        $enddate = '2021-02-10';
        $startdate = now()->subDays(6)->toDateString();
        $enddate = now()->toDateString();

        $date_arr = core()->generateDateRange($startdate, $enddate);
        //        dd($date_arr);

        $method = $request->input('method');
        switch ($method) {
            case 'income':
                $data = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()
                    ->complete()
                    ->whereRaw(DB::raw("DATE_FORMAT(date_create,'%Y-%m-%d') between ? and ? "), [$startdate, $enddate])
                    ->groupBy(DB::raw('Date(bank_payment.date_create)'))
                    ->select(DB::raw('SUM(value) as value'), DB::raw("DATE_FORMAT(date_create,'%Y-%m-%d') as date"))->get();

                $datas = collect($data->toArray())->mapToGroups(function ($item, $key) {
                    return [$item['date'] => $item['value']];
                })->toArray();

                if ($config->seamless == 'Y') {
                    $data2 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
                        ->whereRaw(DB::raw("DATE_FORMAT(date_approve,'%Y-%m-%d') between ? and ? "), [$startdate, $enddate])
                        ->groupBy(DB::raw('Date(withdraws.date_approve)'))
                        ->select(DB::raw('SUM(amount) as value'), DB::raw("DATE_FORMAT(date_approve,'%Y-%m-%d') as date"))->get();

                } else {
                    $data2 = app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
                        ->whereRaw(DB::raw("DATE_FORMAT(date_approve,'%Y-%m-%d') between ? and ? "), [$startdate, $enddate])
                        ->groupBy(DB::raw('Date(withdraws.date_approve)'))
                        ->select(DB::raw('SUM(amount) as value'), DB::raw("DATE_FORMAT(date_approve,'%Y-%m-%d') as date"))->get();

                }

                $datas2 = collect($data2->toArray())->mapToGroups(function ($item, $key) {
                    return [$item['date'] => $item['value']];
                })->toArray();

                $data3 = app('Gametech\Payment\Repositories\PaymentPromotionRepository')->active()->aff()
                    ->whereRaw(DB::raw("DATE_FORMAT(date_create,'%Y-%m-%d') between ? and ? "), [$startdate, $enddate])
                    ->groupBy(DB::raw('Date(payments_promotion.date_create)'))
                    ->select(DB::raw('SUM(credit_bonus) as value'), DB::raw("DATE_FORMAT(date_create,'%Y-%m-%d') as date"))->get();

                $datas3 = collect($data3->toArray())->mapToGroups(function ($item, $key) {
                    return [$item['date'] => $item['value']];
                })->toArray();

                $data4 = app('Gametech\Payment\Repositories\BillRepository')->active()->getpro()
                    ->where('transfer_type', 1)
                    ->whereRaw(DB::raw("DATE_FORMAT(date_create,'%Y-%m-%d') between ? and ? "), [$startdate, $enddate])
                    ->groupBy(DB::raw('Date(bills.date_create)'))
                    ->select(DB::raw('SUM(credit_bonus) as value'), DB::raw("DATE_FORMAT(date_create,'%Y-%m-%d') as date"))->get();

                $datas4 = collect($data4->toArray())->mapToGroups(function ($item, $key) {
                    return [$item['date'] => $item['value']];
                })->toArray();

                foreach ($date_arr as $i => $dt) {
                    $x1 = (empty($datas[$dt]) ? 0 : $datas[$dt][0]);
                    $x2 = (empty($datas2[$dt]) ? 0 : $datas2[$dt][0]);
                    $x3 = (empty($datas3[$dt]) ? 0 : $datas3[$dt][0]);
                    $x4 = (empty($datas4[$dt]) ? 0 : $datas4[$dt][0]);
                    $a = intval($x1);
                    $b = intval($x2);
                    $c = intval($x3);
                    $d = intval($x4);
                    $balance = ($a - $b);

                    $result['label'][] = core()->Date($dt, 'd M');
                    $result['line_deposit'][] = $a;
                    $result['line_withdraw'][] = $b;
                    $result['line_bonus'][] = ($c + $d);
                    $result['line_balance'][] = $balance;
                }

                break;

            case 'topup':
                $data = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()
                    ->whereIn('status', [0, 1])
                    ->whereRaw(DB::raw("DATE_FORMAT(date_create,'%Y-%m-%d') between ? and ? "), [$startdate, $enddate])
                    ->groupBy(DB::raw('Date(bank_payment.date_create)'))
                    ->select(DB::raw('SUM(value) as value'), DB::raw("DATE_FORMAT(date_create,'%Y-%m-%d') as date"))->get();

                $datas = collect($data->toArray())->mapToGroups(function ($item, $key) {
                    return [$item['date'] => $item['value']];
                })->toArray();

                foreach ($date_arr as $i => $dt) {
                    $x1 = (empty($datas[$dt]) ? 0 : $datas[$dt][0]);

                    $a = intval($x1);

                    $result['label'][] = core()->Date($dt, 'd M');
                    $result['bar'][] = $a;

                }

                break;

            case 'register':
                $data = app('Gametech\Member\Repositories\MemberRepository')->active()
                    ->whereRaw(DB::raw('date_regis between ? and ? '), [$startdate, $enddate])
                    ->groupBy('members.date_regis')
                    ->select(DB::raw('COUNT(*) as value'), DB::raw('date_regis as date'))->get();

                $datas = collect($data->toArray())->mapToGroups(function ($item, $key) {
                    return [$item['date'] => $item['value']];
                })->toArray();

                foreach ($date_arr as $i => $dt) {
                    $x1 = (empty($datas[$dt]) ? 0 : $datas[$dt][0]);

                    $a = intval($x1);

                    $result['label'][] = core()->Date($dt, 'd M');
                    $result['bar'][] = $a;

                }

                break;

        }

        return $this->sendResponseNew($result, 'Complete');
    }

    public function loadBank(Request $request)
    {
        $result['list'] = [];
        $method = $request->input('method');
        switch ($method) {
            case 'bankin':
                $responses = collect(app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountInAll()->toArray());
                //                dd($responses);
                $response = $responses->map(function ($items) {
                    $login = 'Y';
                    $btn = '';
                    if ($items['bank']['shortcode'] == 'KBANK' && $items['local'] == 'Y') {
                        $btn = core()->displayBtn($items['code'], $login, 'login');
                    }
                    if ($items['bank']['shortcode'] == 'SCB' && $items['status_auto'] == 'Y') {
                        $btn = core()->displayBtn($items['code'], $login, 'refresh');
                    }
                    $frontDisplay = ($items['display_wallet'] ?? 'N') === 'Y' && ($items['status_topup'] ?? 'N') === 'Y';

                    return [
                        'date_update' => core()->formatDate($items['checktime'], 'd/m/y H:i:s'),
                        'bank' => core()->displayBank($items['bank']['shortcode'], $items['bank']['filepic']),
                        'acc_name' => $items['acc_name'],
                        'acc_no' => $items['acc_no'],
                        'balance' => core()->currency($items['balance']),
                        'status' => $items['api_refresh'],
                        'front_display' => $frontDisplay ? 'Y' : 'N',
                        'login' => $btn,
                    ];

                });

                $result['list'] = $response;

                break;
            case 'bankout':

                $responses = collect(app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutAll()->toArray());

                $response = $responses->map(function ($items) {
                    $login = 'Y';
                    $btn = '';
                    if ($items['bank']['shortcode'] == 'SCB' && $items['status_auto'] == 'Y') {
                        $btn = core()->displayBtn($items['code'], $login, 'refresh');
                    }

                    return [
                        'date_update' => core()->formatDate($items['checktime'], 'd/m/y H:i:s'),
                        'bank' => core()->displayBank($items['bank']['shortcode'], $items['bank']['filepic']),
                        'acc_name' => $items['acc_name'],
                        'acc_no' => $items['acc_no'],
                        'balance' => core()->currency($items['balance']),
                        'login' => $btn,
                    ];

                });

                $result['list'] = $response;

                break;
        }

        return $this->sendResponseNew($result, 'complete');
    }

    public function loadLogin(Request $request)
    {
        $result['list'] = [];
        $method = $request->input('method');
        switch ($method) {
            case 'login':
                $responses = app('Gametech\Member\Repositories\MemberLogRepository')->where('mode', 'LOGIN')->where('member_code', '>', 0)->orderBy('code', 'desc')->take(10)->get();
                //                dd($responses);
                $response = collect($responses)->map(function ($items) {
                    return [
                        'user_name' => ($items->admin ? $items->admin->user_name : ''),
                        'date_update' => $items->date_update->format('Y-m-d H:i:s'),
                        'ip' => $items->ip,
                    ];

                });

                $result['list'] = $response;

                break;
            case 'logout':

                $responses = app('Gametech\Member\Repositories\MemberLogRepository')->where('mode', 'LOGOUT')->orderBy('code', 'desc')->take(10)->get();

                $response = collect($responses)->map(function ($items) {
                    return [
                        'user_name' => ($items->admin ? $items->admin->user_name : ''),
                        'date_update' => $items->date_update->format('Y-m-d H:i:s'),
                        'ip' => $items->ip,
                    ];

                });

                $result['list'] = $response;

                break;
        }

        return $this->sendResponseNew($result, 'complete');
    }

    public function options(DashboardService $service)
    {
        return $this->sendResponseNew($service->getOptions(), 'Complete');
    }

    public function summary(Request $request, DashboardService $service)
    {
        return $this->sendResponseNew($service->getSummary($request->all()), 'Complete');
    }

    public function conversion(Request $request, DashboardService $service)
    {
        return $this->sendResponseNew($service->getConversion($request->all()), 'Complete');
    }

    public function trends(Request $request, DashboardService $service)
    {
        return $this->sendResponseNew($service->getTrends($request->all()), 'Complete');
    }

    public function activity(Request $request, DashboardService $service)
    {
        return $this->sendResponseNew($service->getActivity($request->all()), 'Complete');
    }

    public function funnel(Request $request, DashboardService $service)
    {
        return $this->sendResponseNew($service->getFunnel($request->all()), 'Complete');
    }

    public function memberList(Request $request, DashboardService $service)
    {
        $type = (string) $request->input('type', '');

        return $this->sendResponseNew(
            $service->getMemberList($request->all(), $type),
            'Complete'
        );
    }

    public function syncSummary(Request $request, DashboardService $service)
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin || (int) ($admin->role_id ?? 0) !== 1) {
            return $this->sendResponseFail([], 'ไม่มีสิทธิ์ซิงค์ข้อมูล', 403);
        }

        $result = $service->syncSummaryRange($request->all());

        return $this->sendResponseNew($result, 'Complete');
    }

    public function lottoSummary(Request $request, DashboardService $service)
    {
        return $this->sendResponseNew($service->getLottoSummary($request->all()), 'Complete');
    }

    public function lottoMarketSummary(Request $request, DashboardService $service)
    {
        return $this->sendResponseNew($service->getLottoMarketSummary($request->all()), 'Complete');
    }

    public function lottoRiskSnapshot(Request $request, DashboardService $service)
    {
        return $this->sendResponseNew($service->getLottoRiskSnapshot($request->all()), 'Complete');
    }

    public function alerts(Request $request, DashboardService $service)
    {
        return $this->sendResponseNew($service->getAlerts($request->all()), 'Complete');
    }

    public function getAnnounce()
    {
        $announce = [
            'content' => '',
            'updated_at' => now()->toDateTimeString(),
        ];

        $announce_new = 'N';
        $result['content'] = '';
        $result['new'] = $announce_new;

        $response = Http::get('https://announce.168csn.com/api/announce');

        if ($response->successful()) {
            $response = $response->json();
            $announce = $response['data'];
        }

        if (! Cache::has($this->id().'announce_start')) {
            Cache::add($this->id().'announce_stop', $announce['updated_at']);
        }
        if (! Cache::has($this->id().'announce_stop')) {
            Cache::add($this->id().'announce_stop', $announce['updated_at']);
        } else {
            Cache::put($this->id().'announce_stop', $announce['updated_at']);
        }

        $start = Cache::get($this->id().'announce_start');
        $stop = Cache::get($this->id().'announce_stop');
        if ($start != $stop) {
            $announce_new = 'Y';
            Cache::put($this->id().'announce_start', $stop);
        }

        $result['content'] = $announce['content'];
        $result['new'] = $announce_new;

        return $result;
    }

    public function edit(Request $request)
    {
        $id = $request->input('id');
        $method = $request->input('method');
        if ($method == 'login') {

            $account = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountInOne($id);
            if ($account->bank->shortcode == 'SCB') {
                $dir = storage_path('cookies');
                $cookiesPath = $dir.'/cookies-'.$account->user_name.'.txt';
                $dataPath = $dir.'/data-'.$account->user_name.'.json';

                if (file_exists($cookiesPath)) {
                    unlink($cookiesPath);
                }
                if (file_exists($dataPath)) {
                    unlink($dataPath);
                }
            } elseif ($account->bank->shortcode == 'KBANK') {

                $accname = str_replace('-', '', $account->acc_no);
                $dir = storage_path('cookies');
                $cookiesPath = $dir.'/.kbizcookie'.$accname;
                if (file_exists($cookiesPath)) {
                    unlink($cookiesPath);
                }

                $cookiesPath = $dir.'/.kbizpara'.$accname;
                if (file_exists($cookiesPath)) {
                    unlink($cookiesPath);
                }

                $cookiesPath = $dir.'/.kbizownid'.$accname;
                if (file_exists($cookiesPath)) {
                    unlink($cookiesPath);
                }

                $cookiesPath = $dir.'/.kbizdatarsso'.$accname;
                if (file_exists($cookiesPath)) {
                    unlink($cookiesPath);
                }
            }

            return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
        } elseif ($method == 'refresh') {
            $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountInOutOne($id);
            $bank_code = $bank->bank->code;

            if ($bank_code == 2) {

                $bbl = new KbankOut;

                $chk = $bbl->BankCurl($bank['acc_no'], 'getbalance', 'POST');
                if (isset($chk['status']) && $chk['status'] === true) {
                    $balance_start = str_replace(',', '', $chk['data']['availableBalance']);
                    if ($balance_start >= 0) {
                        $bank->balance = $balance_start;
                    }

                    $bank->checktime = now()->toDayDateTimeString();
                    $bank->save();
                }

            } elseif ($bank_code == 4) {

                $bbl = new ScbOut;

                $chk = $bbl->BankCurl($bank['acc_no'], 'getbalance', 'POST');
                //                dd($bank['acc_no']);
                if (isset($chk['status']) && $chk['status'] === true) {
                    $balance_start = str_replace(',', '', $chk['data']['availableBalance']);
                    if ($balance_start >= 0) {
                        $bank->balance = $balance_start;
                    }

                    $bank->checktime = now()->toDayDateTimeString();
                    $bank->save();

                    return $this->sendSuccess('ยอดปัจจุบันคือ '.$balance_start.' บาท');
                } else {
                    return $this->sendSuccess($chk['msg']);
                }
            }

        }

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }
}
