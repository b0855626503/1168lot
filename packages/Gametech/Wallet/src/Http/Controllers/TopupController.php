<?php

namespace Gametech\Wallet\Http\Controllers;


//use App\Libraries\EzPay;
use App\Libraries\HengPay;
use App\Libraries\LuckyPay;
use App\Libraries\PapayaPay;
use App\Libraries\PomPayOut;
use App\Libraries\SuperrichPay;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Models\BankAccount;
use Gametech\Payment\Repositories\BankRepository;
use Gametech\Payment\Repositories\BankRuleRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class TopupController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected $bankRepository;

    protected $memberRepository;

    protected $bankRuleRepository;


    /**
     * Create a new Repository instance.
     *
     * @param BankRepository $bankRepo
     * @param MemberRepository $memberRepo
     */
    public function __construct
    (
        BankRepository     $bankRepo,
        MemberRepository   $memberRepo,
        BankRuleRepository $bankRuleRepo
    )
    {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->bankRepository = $bankRepo;

        $this->memberRepository = $memberRepo;

        $this->bankRuleRepository = $bankRuleRepo;
    }

    public function indextest_1()
    {
        $profile = $this->user()->load('bank');

        $bankss = collect($this->bankRepository->getBankInAccountAll()->toArray());


        $bankss = $bankss->transform(function ($item, $key) {
            $item['filepic'] = Storage::url('bank_img/' . $item['filepic']);
            return $item;
        });

//        dd($bankss);


        $rules = collect($this->bankRuleRepository->all())->toArray();

        $pass = false;
        if (count($rules) > 0) {
            foreach ($rules as $i => $item) {


                if ($pass) break;
                if ($item['types'] == 'IF') {
                    if ($profile->bank->code == $item['bank_code']) {
                        if ($item['method'] == 'CAN') {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 0) {
                                $banks = $bankss->whereIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereIn('code', [$item['bank_number']])->all();
                            }


                        } else {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 0) {
                                $banks = $bankss->whereNotIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereNotIn('code', [$item['bank_number']])->all();
                            }

                        }
                        $pass = true;
                    }
                } else {

                    if ($profile->bank->code != $item['bank_code']) {
                        if ($item['method'] == 'CAN') {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 0) {
                                $banks = $bankss->whereIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereIn('code', [$item['bank_number']])->all();
                            }

                        } else {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 0) {
                                $banks = $bankss->whereNotIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereNotIn('code', [$item['bank_number']])->all();
                            }

                        }
                        $pass = true;
                    }


                }
            }
        } else {
            $banks = $bankss;
        }


//        dd($banks);
        return view($this->_config['view'], compact('banks', 'profile'));
    }

    public function indextest_()
    {
        $newbank = [];
        $profile = $this->user()->load('bank');

        $bankss = ($this->bankRepository->getBankInAccountAll()->toArray());

        foreach ($bankss as $i => $banks) {
            $newbank[$i] = $banks;
            foreach ($bankss[$i]['banks_account'] as $item) {
                $newbank[$i]['sort'] = $item['sort'];
            }

        }


        $keys = array_column($newbank, 'sort');
        array_multisort($keys, SORT_ASC, $newbank);

//        dd($newbank);
        $newbank = collect($newbank);

        $bankss = $newbank->transform(function ($item, $key) {
            $item['filepic'] = Storage::url('bank_img/' . $item['filepic']);
            return $item;
        })->all();

//        dd($bankss);


        $rules = collect($this->bankRuleRepository->all())->toArray();
        $pass = false;
        if (count($rules) > 0) {
            foreach ($rules as $i => $item) {


                if ($pass) break;
                if ($item['types'] == 'IF') {
                    if ($profile->bank->code == $item['bank_code']) {
                        if ($item['method'] == 'CAN') {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 1) {
                                $banks = $bankss->whereIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereIn('code', [$item['bank_number']])->all();
                            }


                        } else {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 1) {
                                $banks = $bankss->whereNotIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereNotIn('code', [$item['bank_number']])->all();
                            }

                        }
                        $pass = true;
                    }
                } else {

                    if ($profile->bank->code != $item['bank_code']) {
                        if ($item['method'] == 'CAN') {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 1) {
                                $banks = $bankss->whereIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereIn('code', [$item['bank_number']])->all();
                            }

                        } else {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 1) {
                                $banks = $bankss->whereNotIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereNotIn('code', [$item['bank_number']])->all();
                            }

                        }
                        $pass = true;
                    }


                }
            }
        } else {
            $banks = $bankss;
        }

        dd($banks);

        return view($this->_config['view'], compact('banks', 'profile'));
    }

    public function indextest()
    {
        $newbank = [];

        $profile = $this->user()->load('bank');

        $bankss = collect($this->bankRepository->getBankInAccountAll()->toArray());

        foreach ($bankss as $i => $banks) {
            $newbank[$i] = $banks;
            foreach ($bankss[$i]['banks_account'] as $item) {
                $newbank[$i]['sort'] = $item['sort'];
            }

        }

        $keys = array_column($newbank, 'sort');
        array_multisort($keys, SORT_ASC, $newbank);

//        dd($newbank);
        $bankss = collect($newbank);

        $bankss = $bankss->transform(function ($item, $key) {
            $item['filepic'] = Storage::url('bank_img/' . $item['filepic']);
            return $item;
        });

//        dd($bankss);


        $rules = collect($this->bankRuleRepository->all())->toArray();
        $pass = false;
        if (count($rules) > 0) {
            foreach ($rules as $i => $item) {


                if ($pass) break;
                if ($item['types'] == 'IF') {
                    if ($profile->bank->code == $item['bank_code']) {
                        if ($item['method'] == 'CAN') {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 1) {
                                $banks = $bankss->whereIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereIn('code', [$item['bank_number']])->all();
                            }


                        } else {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 1) {
                                $banks = $bankss->whereNotIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereNotIn('code', [$item['bank_number']])->all();
                            }

                        }
                        $pass = true;
                    }
                } else {

                    if ($profile->bank->code != $item['bank_code']) {
                        if ($item['method'] == 'CAN') {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 1) {
                                $banks = $bankss->whereIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereIn('code', [$item['bank_number']])->all();
                            }

                        } else {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 1) {
                                $banks = $bankss->whereNotIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereNotIn('code', [$item['bank_number']])->all();
                            }

                        }
                        $pass = true;
                    }


                }
            }
        } else {
            $banks = $bankss;
        }


        return view($this->_config['view'], compact('banks', 'profile'));
    }

    public function index()
    {
        $newbank = [];
        $banks = [];
        $profile = $this->user()->load('bank');

        $bankss = collect($this->bankRepository->getBankInAccountAll()->toArray());

        foreach ($bankss as $i => $banks) {
            $newbank[$i] = $banks;
            foreach ($bankss[$i]['banks_account'] as $item) {
                $newbank[$i]['sort'] = $item['sort'];
            }

        }

        $keys = array_column($newbank, 'sort');
        array_multisort($keys, SORT_ASC, $newbank);

//        dd($newbank);
        $bankss = collect($newbank);

        $bankss = $bankss->transform(function ($item, $key) {
            $item['filepic'] = Storage::url('bank_img/' . $item['filepic']);
            return $item;
        });

//        dd($bankss);


        $rules = collect($this->bankRuleRepository->all())->toArray();
        $pass = false;
        if (count($rules) > 0) {
            foreach ($rules as $i => $item) {


                if ($pass) break;
                if ($item['types'] == 'IF') {
                    if ($profile->bank->code == $item['bank_code']) {
                        if ($item['method'] == 'CAN') {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 1) {
                                $banks = $bankss->whereIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereIn('code', [$item['bank_number']])->all();
                            }


                        } else {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 1) {
                                $banks = $bankss->whereNotIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereNotIn('code', [$item['bank_number']])->all();
                            }

                        }
                        $pass = true;
                    }
                } else {

                    if ($profile->bank->code != $item['bank_code']) {
                        if ($item['method'] == 'CAN') {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 1) {
                                $banks = $bankss->whereIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereIn('code', [$item['bank_number']])->all();
                            }

                        } else {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 1) {
                                $banks = $bankss->whereNotIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereNotIn('code', [$item['bank_number']])->all();
                            }

                        }
                        $pass = true;
                    }


                }
            }
        } else {
            $banks = $bankss;
        }


        return view($this->_config['view'], compact('banks', 'profile'));
    }

    public function index_()
    {
        $profile = $this->user()->load('bank');

        $bankss = collect($this->bankRepository->getBankInAccountAll()->toArray());


        $bankss = $bankss->transform(function ($item, $key) {
            $item['filepic'] = Storage::url('bank_img/' . $item['filepic']);
            return $item;
        });

//        dd($bankss);


        $rules = collect($this->bankRuleRepository->all())->toArray();
        $pass = false;
        if (count($rules) > 0) {
            foreach ($rules as $i => $item) {


                if ($pass) break;
                if ($item['types'] == 'IF') {
                    if ($profile->bank->code == $item['bank_code']) {
                        if ($item['method'] == 'CAN') {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 1) {
                                $banks = $bankss->whereIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereIn('code', [$item['bank_number']])->all();
                            }


                        } else {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 1) {
                                $banks = $bankss->whereNotIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereNotIn('code', [$item['bank_number']])->all();
                            }

                        }
                        $pass = true;
                    }
                } else {

                    if ($profile->bank->code != $item['bank_code']) {
                        if ($item['method'] == 'CAN') {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 1) {
                                $banks = $bankss->whereIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereIn('code', [$item['bank_number']])->all();
                            }

                        } else {
                            $bcode = explode(',', $item['bank_number']);
                            if (count($bcode) > 1) {
                                $banks = $bankss->whereNotIn('code', $bcode)->all();
                            } else {
                                $banks = $bankss->whereNotIn('code', [$item['bank_number']])->all();
                            }

                        }
                        $pass = true;
                    }


                }
            }
        } else {
            $banks = $bankss;
        }


        return view($this->_config['view'], compact('banks', 'profile'));
    }

    public function trueWallet(Request $request)
    {
        $datenow = now()->toDateTimeString();
        $user = $this->user()->name . ' ' . $this->user()->surname;
        $ip = $request->ip();

        $request->validate([
            'amount' => 'required|numeric',
            'bank_time' => 'required',
            'account_code' => 'required|integer'
        ]);

        $amount = $request->input('amount');
        $bank_time = $request->input('bank_time');
        $account = $request->input('account_code');

        $member = $this->user();

        $bank_account = app('Gametech\Payment\Repositories\BankAccountRepository')->find($account);

        $bank = app('Gametech\Payment\Repositories\BankRepository')->find($bank_account->banks);

        if ($amount < 1) {
            session()->flash('error', 'ยอดเงินที่ระบุไม่ถูกต้อง');
            return redirect()->route('customer.topup.index');
        }

        $chk = app('Gametech\Payment\Repositories\BankPaymentRepository')->findOneWhere(['txid' => $member->user_name, 'status' => 0, 'enable' => 'Y']);
        if ($chk) {
            session()->flash('error', 'ได้มีรายการแจ้งฝากเงินเข้ามาแล้ว โปรดรอทีมงานตรวจสอบ');
            return redirect()->route('customer.topup.index');
        }


        $bank_time = $bank_time . ':00';

        $detail = $member->tel;

        $hash = md5($account . $bank_time . $amount . $detail);

        $chk = app('Gametech\Payment\Repositories\BankPaymentRepository')->findOneWhere(['tx_hash' => $hash, 'account_code' => $account]);
        if ($chk) {
            session()->flash('error', 'ไม่สามารถทำรายการได้ เนื่องจากมีข้อมูลในระบบแล้ว');
            return redirect()->route('customer.topup.index');
        }

        $data = [
            'bank' => strtolower($bank->shortcode . '_' . $bank_account->acc_no),
            'detail' => $detail,
            'account_code' => $account,
            'autocheck' => 'Y',
            'bankstatus' => 1,
            'bank_name' => $bank->shortcode,
            'bank_time' => $bank_time,
            'channel' => 'MANUAL',
            'value' => $amount,
            'tx_hash' => $hash,
            'status' => 0,
            'member_topup' => $member->code,
            'txid' => $member->user_name,
            'user_create' => $member->user_name,
            'create_by' => $user
        ];

        $response = app('Gametech\Payment\Repositories\BankPaymentRepository')->create($data);
        if ($response->code) {
            session()->flash('success', 'แจ้งการฝากเงินผ่าน TrueWallet สำเร็จแล้ว โปรดรอทีมงานตรวจสอบ');
            return redirect()->route('customer.topup.index');
        } else {
            session()->flash('error', 'ไม่สามารถทำรายการได้ โปรดลองใหม่อีกครั้ง');
            return redirect()->route('customer.topup.index');
        }
    }

    public function pompay_create(Request $request)
    {
        $datenow = now()->toDateTimeString();
        $mobile = 'test';
        $bbl = new PomPayOut();
        $request->validate([
            'amount' => 'required|numeric'
        ]);

        $amount = (float)$request->input('amount');
        $amount = number_format($amount, 2, '.', '');
        $member = $this->user();

        if (config('app.user_url') === '') {
            $baseurl = 'https://' . (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        } else {
            $baseurl = 'https://' . config('app.user_url') . '.' . (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        }

//        $baseurl = 'https://'.(is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));

        $uuid = Str::uuid()->__toString();
        $clientId = config('app.pompay_clientId');
        $transactionId = $this->check_uuid($uuid);
        $custName = $member->name;
        $custSecondaryName = $member->user_name;
        $custBank = $bbl->Banks($member->bank_code);
        $custMobile = trim($member->tel);
        $custEmail = '';
        $returnUrl = $baseurl . '/pompay/return';
        $callbackUrl = $baseurl . '/pompay/callback';
        $paymentMethod = 'qr';
        $bankAcc = $member->acc_no;
        $amount = trim($amount);
        $clientSecret = config('app.pompay_clientSecret');
        $hash = hash('sha256', $clientId . $transactionId . $custName . $custSecondaryName . $custBank . $custMobile . $custEmail . $amount . $returnUrl . $callbackUrl . $paymentMethod . $bankAcc . $clientSecret);

        $pompay = [
            'clientId' => $clientId,
            'transactionId' => $transactionId,
            'custName' => $custName,
            'custSecondaryName' => $custSecondaryName,
            'custBank' => $custBank,
            'custMobile' => $custMobile,
            'custEmail' => $custEmail,
            'amount' => $amount,
            'paymentMethod' => $paymentMethod,
            'returnUrl' => $returnUrl,
            'callbackUrl' => $callbackUrl,
            'bankAcc' => $bankAcc,
            'hashVal' => $hash,
            'datetime' => $datenow,
            'hash' => $clientId . $transactionId . $custName . $custSecondaryName . $custBank . $custMobile . $custEmail . $amount . $returnUrl . $callbackUrl . $paymentMethod . $bankAcc . $clientSecret
        ];

        $path = storage_path('logs/pompay/webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- CREATE --', true), FILE_APPEND);
        file_put_contents($path, print_r($pompay, true), FILE_APPEND);

        if($custBank == 500){
            return view('wallet::customer.pompay.noservice');
        }

//        dd($pompay);

//        return Redirect::away('https://staging.pompay.asia/payment');
//        $response = Http::asForm()->post('https://staging.pompay.asia/payment', $pompay);
//        return $response->body();

        return view($this->_config['view'], compact('pompay'));
    }

    public function hengpay_create(Request $request)
    {

//        dd('hengpay');
        $url = '';
        $datenow = now()->toDateTimeString();
        $mobile = 'test';
        $api = new HengPay();
        $request->validate([
            'amount' => 'required|numeric'
        ]);

        $amount = (float)$request->input('amount');
        $amount = number_format($amount, 2, '.', '');
        $member = $this->user();

        if (Cache::has('hengpay_' . $member->code)) {
            return false;
        }

        Cache::put('hengpay_' . $member->code, 'lock', now()->addSeconds(5));



        $uuid = 'HNP'.str_pad($this->id(), 7, "0", STR_PAD_LEFT).'X'.date('His');
        $transactionId = $this->check_uuid2($uuid,$this->id());
        $shopName = config('app.hengpay_shop');

        $bank = BankAccount::where('banks', 100)->first();
//        dd($bank);
        $token = $bank->token;

        if (!$token) {
            $token = $api->GetToken();
        }

        $pompay = [
            'referenceNo' => $transactionId,
            'ShopName' => $shopName,
            'amount' => $amount
        ];

//        dd($pompay);

        $response = $api->create($token, $pompay);
        if ($response['success'] === true) {
//            $data = [
//                'member_code' => $member->code,
//                'amount' => $amount,
//                'referenceNo' => $transactionId,
//                'user_create' => $member->name,
//                'user_update' => $member->name,
//            ];
//            app('Gametech\Payment\Repositories\BankHengpayRepository')->create($data);

            $url = $response['url'];
        }

        return view($this->_config['view'], compact('url'));
    }

    public function lucky_create(Request $request)
    {

//        dd('hengpay');
        $url = '';
        $datenow = now()->toDateTimeString();
        $mobile = 'test';
        $api = new LuckyPay();
        $request->validate([
            'amount' => 'required|numeric'
        ]);

        if (config('app.user_url') === '') {
            $baseurl = 'https://' . (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        } else {
            $baseurl = 'https://' . config('app.user_url') . '.' . (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        }

        $amount = (float)$request->input('amount');
        $amount = number_format($amount, 2, '.', '');
        $member = $this->user();

        if (Cache::has('lucky_' . $member->code)) {
            return false;
        }

        Cache::put('lucky_' . $member->code, 'lock', now()->addSeconds(5));

        $time = time();

        if($amount < 300){
            return view('wallet::customer.luckypay.noservice');
        }

        $uuid = 'LUK'.str_pad($this->id(), 7, "0", STR_PAD_LEFT).'X'.date('His');
        $transactionId = $this->check_uuid3($uuid,$this->id());

//        $bank = BankAccount::where('banks', 101)->first();


        $param = [
            'clientCode' => config('app.luckypay_client'),
            'chainName' => 'BANK',
            'coinUnit' => 'THB',
            'clientNo' => $transactionId,
            'requestAmount' => $amount,
            'requestTimestamp' => $time,
            'callbackurl' => $baseurl.'/luckypay/callback',
            'hrefbackurl' => $baseurl,
            'sign' => $api->GetSign($transactionId,$time),
            'toPayQr' => 0,
        ];

//        dd($param);
        $url = config('app.luckypay_url').'/api/coin/pay/request';

        $response = $api->create($url, $param);
        if ($response['success'] === true) {

            $url = $response['url'];
        }else{
            return view('wallet::customer.luckypay.maintenance');
        }

        return redirect()->away($url);
    }

    public function papaya_create(Request $request)
    {

//        dd('hengpay');
        $url = '';
        $datenow = now()->toDateTimeString();
        $mobile = 'test';
        $api = new PapayaPay();
        $request->validate([
            'amount' => 'required|numeric'
        ]);

//        if (config('app.user_url') === '') {
//            $baseurl = 'https://' . (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
//        } else {
//            $baseurl = 'https://' . config('app.user_url') . '.' . (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
//        }

        $amount = (float)$request->input('amount');
        $amount = number_format($amount, 2, '.', '');
        $member = $this->user();

//        if (Cache::has('lucky_' . $member->code)) {
//            return false;
//        }
//
//        Cache::put('lucky_' . $member->code, 'lock', now()->addSeconds(5));

        $time = time();


        if($amount < 300){
            return view('wallet::customer.papayapay.noservice');
        }

        $uuid = 'PAY'.str_pad($this->id(), 7, "0", STR_PAD_LEFT).'X'.date('His');
        $transactionId = $this->check_uuid4($uuid,$this->id());

//        $bank = BankAccount::where('banks', 101)->first();

        $bankCode = $api->Banks($member->bank_code);
        if($bankCode === '500'){
            $param = [
                'qrCodeTransactionId' => $transactionId,
                'currency' => 'THB',
                'amount' => (float)$amount,
                'description' => 'Payment',
                'payMethod' => 'thaiqr'
            ];
        }else{
            $param = [
                'qrCodeTransactionId' => $transactionId,
                'currency' => 'THB',
                'amount' => (float)$amount,
                'description' => 'Payment',
                'payMethod' => 'thaiqr',
                'bankCode' => $bankCode,
                'accountNumber' => $member->acc_no,
                'accountName' => $member->name
            ];
        }

        $path = storage_path('logs/papayapay/out_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- PARAM --', true), FILE_APPEND);
        file_put_contents($path, print_r($param, true), FILE_APPEND);

//        dd($param);
        $url = config('app.papayapay_url').'/api/v2/create-qr-payment';

        $response = $api->create($url, $param);
        if ($response['success'] === true) {

            $url = $response['url'];
        }else{
            return view('wallet::customer.papayapay.maintenance');
        }

        return redirect()->away($url);
    }

    public function superrich_create(Request $request)
    {

//        dd('hengpay');
        $url = '';
        $datenow = now()->toDateTimeString();
        $mobile = 'test';
        $api = new SuperrichPay();
        $request->validate([
            'amount' => 'required|numeric'
        ]);

        if (config('app.user_url') === '') {
            $baseurl = 'https://' . (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        } else {
            $baseurl = 'https://' . config('app.user_url') . '.' . (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        }

        $amount = (float)$request->input('amount');
        $amount = number_format($amount, 2, '.', '');
        $member = $this->user();

        if (Cache::has('superrich_' . $member->code)) {
            return false;
        }

        Cache::put('superrich_' . $member->code, 'lock', now()->addSeconds(5));

        $time = time();



        $uuid = 'SUK'.str_pad($this->id(), 7, "0", STR_PAD_LEFT).'X'.date('His');
//        $transactionId = $this->check_uuid3($uuid,$this->id());

//        $bank = BankAccount::where('banks', 101)->first();
//        $token = $api->Auth();
        $username = $member->user_name; //Customer Username
        $auth = $api->Auth(); //Get from auth api
        $currency = "THB"; //Currency code
        $orderid = $uuid; //Merchant order ID
        $email = "user@example.com"; //Customer Email
        $phone_number = $member->tel; //Customer Phone number
        $redirect_url = $baseurl; //Redirect Url
        $customer_bank_holder_name = $member->name; //Customer Bank Holder Name
        $customer_bank_account = $member->acc_no; //Customer Bank Account
        $bank_id = $api->Banks($member->bank_code); //Bank Id
        $pay_method = "thaiqr"; //Either 1 option : "thaiqr" , "truemoney
        if($bank_id === '500'){
            $param = array(
                'username' => $username,
                'auth' => $auth,
                'amount' => $amount,
                'currency' => $currency,
                'orderid' => $orderid,
                'email' => $email,
                'phone_number' => $phone_number,
                'redirect_url' => $redirect_url,
                'pay_method' => 'truemoney'
            );

        }else{
            $param = array(
                'username' => $username,
                'auth' => $auth,
                'amount' => $amount,
                'currency' => $currency,
                'orderid' => $orderid,
                'email' => $email,
                'phone_number' => $phone_number,
                'redirect_url' => $redirect_url,
                'customer_bank_holder_name' => $customer_bank_holder_name,
                'customer_bank_account' => $customer_bank_account,
                'bank_id' => $bank_id,
                'pay_method' => 'thaiqr'
            );
        }




//        dd($param);
        $url = config('app.superrich_apiurl').'/merchant/generate_orders';


        $response = $api->create($url, $param);
        if ($response['success'] === true) {

            $url = $response['url'];
        }else{
            return view('wallet::customer.superrich.maintenance');
        }

        return redirect()->away($url);
    }

    public function check_uuid($uuid)
    {
        $data = app('Gametech\Payment\Repositories\BankPaymentRepository')->findOneWhere(['txid' => $uuid]);
        if (isset($data)) {
            $uuid = Str::uuid()->__toString();
            return $this->check_uuid($uuid);
        } else {
            return $uuid;
        }
    }

    public function check_uuid2($uuid,$id)
    {
        $data = app('Gametech\Payment\Repositories\BankPaymentRepository')->findOneWhere(['txid' => $uuid]);
        if (isset($data)) {
            $uuid = 'HNP'.str_pad($id, 7, "0", STR_PAD_LEFT).'X'.date('His');
            return $this->check_uuid2($uuid,$id);
        } else {
            return $uuid;
        }
    }

    public function check_uuid3($uuid,$id)
    {
        $data = app('Gametech\Payment\Repositories\BankPaymentRepository')->findOneWhere(['txid' => $uuid]);
        if (isset($data)) {
            $uuid = 'LUK'.str_pad($id, 7, "0", STR_PAD_LEFT).'X'.date('His');
            return $this->check_uuid3($uuid,$id);
        } else {
            return $uuid;
        }
    }

    public function check_uuid4($uuid,$id)
    {
        $data = app('Gametech\Payment\Repositories\BankPaymentRepository')->findOneWhere(['txid' => $uuid]);
        if (isset($data)) {
            $uuid = 'PAY'.str_pad($id, 7, "0", STR_PAD_LEFT).'X'.date('His');
            return $this->check_uuid4($uuid,$id);
        } else {
            return $uuid;
        }
    }

    public function commspay_create(Request $request)
    {

        $url = '';
        $datenow = now()->toDateTimeString();
        $mobile = 'test';
        $request->validate([
            'amount' => 'required|numeric',
        ]);


        $baseUrl = url('/');

        $amount = (float) $request->input('amount');
        $amount = number_format($amount, 2, '.', '');
        $member = $this->user();

        if($amount < 10){
            return false;
        }

        if (Cache::has('commspay_'.$member->code)) {
            return false;
        }

        Cache::put('commspay_'.$member->code, 'lock', now()->addSeconds(5));

        $time = time();

        $merchant_code = config('app.commspay_merchant_code');
        $merchant_api_key = config('app.commspay_api_key');
        $merchant_secert_key = config('app.commspay_secret_key');
        $transaction_code = Str::orderedUuid();
        $transaction_timestamp = time();
        $transaction_amount = floatval($amount);
        $payment_code = config('app.commspay_payment_code');
        $user_id = $member->user_name;
        $currency_code = 'USDT';
        $bank_code = 'USD_TRC20-USDT';
        $callback_url = $baseUrl.'/commspay/callback';
        $return_url = $baseUrl;

        $param = [
            'merchant_code' => trim($merchant_code),
            'merchant_api_key' => trim($merchant_api_key),
            'transaction_code' => trim($transaction_code),
            'transaction_timestamp' => trim($transaction_timestamp),
            'transaction_amount' => $transaction_amount,
            'payment_code' => trim($payment_code),
            'user_id' => trim($user_id),
            'currency_code' => trim($currency_code),
            'bank_code' => trim($bank_code),
            'callback_url' => trim($callback_url),
            'return_url' => trim($return_url),
        ];

        $chk= $param;
        $chk['datetime'] = $datenow;

        $path = storage_path('logs/commspay/out_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- CALLBACK --', true), FILE_APPEND);
        file_put_contents($path, print_r($chk, true), FILE_APPEND);


        //        dd($param);

        $postString = '';
        foreach ($param as $keyR => $value) {
            if ($keyR == 'transaction_amount') {
                $postString .= $keyR.'='.floatval($value).'&';
            } else {
                $postString .= $keyR.'='.$value.'&';
            }

        }
        $str = substr($postString, 0, -1);


        $key = $this->encrypt_decrypt('encrypt', $str, $merchant_api_key, $merchant_secert_key);

        $url = config('app.commspay_api_url').'/'.$merchant_code.'/v2/dopayment?key='.$key;


        return redirect()->away($url);
    }

    public function encrypt_decrypt($action, $string, $apikey = '{your_api_key}', $secretkey = '{your_secret_key}')
    {
        $output = false;
        $encrypt_method = 'AES-256-CBC';
        $secret_key = $apikey;
        $secret_iv = $secretkey;
        // hash
        $key = substr(hash('sha256', $secret_key, true), 0, 32);
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encrypt_method, $key, OPENSSL_RAW_DATA, $iv);
            $output = base64_encode($output);
            $output = urlencode($output);

        } elseif ($action == 'decrypt') {
            $output = openssl_decrypt(base64_decode(urldecode($string)), $encrypt_method, $key, OPENSSL_RAW_DATA, $iv);
        }

        return $output;
    }



}
