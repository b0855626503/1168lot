<?php

namespace Gametech\Admin\Http\Controllers;

use Gametech\Game\Models\GameUser;
use Gametech\Member\Models\Member;
use Gametech\Payment\Models\BankPayment;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;


class FixController extends AppBaseController
{
    protected $_config;

    protected $setting;

    public function __construct()
    {
        $this->_config = request('_config');

        $this->setting = core()->getConfigData();

        $this->middleware('admin');
    }


    public function index()
    {
        return view($this->_config['view']);
    }

    public function optimize()
    {
        Artisan::call('optimize:clear');
        $result = Artisan::output();
        return $this->sendResponse($result, 'Complete');
    }

    public function cashback()
    {
        $config = $this->setting;
        if($config['seamless'] == 'Y'){
            Artisan::call('new_cb:list');
        }else{
            Artisan::call('newcashback:list');
        }

//        if($output){
//            Artisan::call('newcashback:topup');
//        }
        $result = Artisan::output();
        return $this->sendResponse($result, 'สรุปรายการ CashBack แล้ว');
    }

    public function cashbackClearTopup($date)
    {
        if($date){
            $result = DB::table('members_cashback')->whereDate('date_cashback', $date)->delete();
        }
//        Artisan::call('newcashback:topup');

//        $result = Artisan::output();
        return $this->sendResponse($result, 'ลบรายการ');
    }

    public function cashbackTopup()
    {
        $config = $this->setting;
        if($config['seamless'] == 'Y'){
            Artisan::call('new_cb:topup');
        }else{
            Artisan::call('newcashback:topup');
        }
        $result = Artisan::output();
        return $this->sendResponse($result, 'ส่งรายการ Cashback เข้าคิวเติมให้ลูกค้า');
    }

    public function cashbackDelTopup($date)
    {
        if ($date) {
            Artisan::call('newcashback:deltopup ' . $date);
        } else {
            Artisan::call('newcashback:deltopup');
        }


        $result = Artisan::output();
        return $this->sendResponse($result, 'ส่งรายการ Cashback เข้าคิวเติมให้ลูกค้า');
    }

    public function ic()
    {
        $config = $this->setting;
        if($config['seamless'] == 'Y'){
            Artisan::call('new_ic:list');
        }else{
            Artisan::call('newic:list');
        }
//        if($output){
//            Artisan::call('newic:topup');
//        }
        $result = Artisan::output();
        return $this->sendResponse($result, 'สรุปรายการ CashBack แล้ว');
    }

    public function icClearTopup($date)
    {
        if($date){
            $result = DB::table('members_ic')->whereDate('date_cashback', $date)->delete();
        }
//        Artisan::call('newcashback:topup');

//        $result = Artisan::output();
        return $this->sendResponse($result, 'ลบรายการ');
    }

    public function icTopup()
    {
        $config = $this->setting;
        if($config['seamless'] == 'Y'){
            Artisan::call('new_ic:topup');
        }else{
            Artisan::call('newic:topup');
        }


//        if($output){
//            Artisan::call('newic:topup');
//        }
        $result = Artisan::output();
        return $this->sendResponse($result, 'ส่งรายการ IC เข้าคิวเติมให้ลูกค้า');
    }

    public function icDelTopup($date)
    {
        if ($date) {
            Artisan::call('newic:deltopup ' . $date);
        } else {
            Artisan::call('newic:deltopup');
        }

//        if($output){
//            Artisan::call('newic:topup');
//        }
        $result = Artisan::output();
        return $this->sendResponse($result, 'ส่งรายการ Cashback ลบให้ลูกค้า');
    }

    public function upspeed()
    {
        Artisan::call('queue:work --queue=cashback,ic --stop-when-empty');
//        if($output){
//            Artisan::call('newic:topup');
//        }
        $result = Artisan::output();
        return $this->sendResponse($result, 'แก้ไข การเติม Cashback IC โปรดอย่า F5');
    }

    public function bank()
    {
        $results = DB::select('select * from banks where code = :code', ['code' => 19]);
        if (!$results) {
            DB::insert('insert into banks (code, name_th, shortcode, bg_color, enable, name_en, status_auto, website, filepic, user_create, user_update, date_create, date_update) values (?, ?, ?, ?, ?, ? , ?, ?, ?, ?, ?, ? ,?)', [19, 'ทหารไทยธนชาต', 'TTB', '#FFFFFF', 'Y', 'TMBThanachart Bank Public Company Limited', 'N', 'https://www.ttbbank.com/', 'ttb.png', '- ', '- ', '2021-08-09 15:32:20', '2021-08-09 15:32:20']);
            Artisan::call('lada-cache:flush');
            $result = 'เพิ่มธนาคาร TTB แล้ว';
        } else {
            $result = 'มีข้อมูล ํธนาคาร TTB แล้ว';
        }
        return $result;
    }

    public function bankname()
    {
        $results = DB::table('bank_payment')->where('bank', 'like', 'twl%')->update(['bankname' => 'TW']);

        return $results;
    }

    public function faststart($date)
    {

        Artisan::call('faststart:date ' . $date);
        return Artisan::output();

    }

    public function sumtoday()
    {
        Artisan::call('dailystat:check');
//        if($output){
//            Artisan::call('newic:topup');
//        }
        $result = Artisan::output();
        return 'คำนวนสรุป วันนี้ เสร็จแล้ว';
    }

    public function sumyesterday()
    {
        $yesterday = now()->subDays(1)->toDateString();
        Artisan::call('dailystat:check ' . $yesterday);
//        if($output){
//            Artisan::call('newic:topup');
//        }
        $result = Artisan::output();
        return 'คำนวนสรุป ย้อนหลัง เสร็จแล้ว';
    }

    public function fixmember()
    {

        Member::chunk(200, function ($members) {
            foreach ($members as $member) {
                $user = GameUser::where('member_code',$member->code)->first();

                if(!$user){
                    GameUser::create([
                        'game_code' => 1,
                        'member_code' => $member->code,
                        'user_name' => $member->user_name,
                        'user_pass' => $member->user_pass,
                        'balance' => 0,
                        'user_create' => 'SYSTEM',
                        'user_update' => 'SYSTEM',
                    ]);
                }
            }
        });

//        foreach (Member::all()->cursor() as $member) {
//            $user = GameUser::where('member_code',$member->code)->first();
//
//            if(!$user){
//                GameUser::create([
//                    'game_code' => 1,
//                    'member_code' => $member->code,
//                    'user_name' => $member->user_name,
//                    'user_pass' => $member->user_pass,
//                    'balance' => 0,
//                    'user_create' => 'SYSTEM',
//                    'user_update' => 'SYSTEM',
//                ]);
//            }
//        }

        return 'แก้ไขสมาชิกแล้ว';
    }
    public function clearParment(){
        BankPayment::where('status',0)->update(['status' => 2]);
        return 'เคลียรายการเงินเข้าแล้ว';
    }

    public function updb()
    {

        Artisan::call('migrate --force');
        return Artisan::output();

    }

    public function queuerestart()
    {

        Artisan::call('queue:restart');
        return Artisan::output();

    }

    public function twcurl(){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://sv1.168gametech.com/tw/Transaction_0637836207_2023_02_17.json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    public function KbankApi($account){

        $url = 'https://api-kbank.me2me.biz/kbiz/' . $account . '/getbalance';

        $response = rescue(function () use ($url) {
            return Http::timeout(15)->withHeaders([
                'access-key' => 'b499fe72-a9fb-4a6a-817d-c096c39a6896'
            ])->post($url);

        }, function ($e) {
            return $e;
        });

        $return['body'] = $response->body();
        $return['json'] = $response->json();
        $return['successful'] = $response->successful();
        $return['failed'] = $response->failed();
        $return['clientError'] = $response->clientError();
        $return['serverError'] = $response->serverError();

        $url = 'https://api-kbank.me2me.biz/kbiz/' . $account . '/transaction';

        $response = rescue(function () use ($url) {
            return Http::timeout(15)->withHeaders([
                'access-key' => 'b499fe72-a9fb-4a6a-817d-c096c39a6896'
            ])->post($url);

        }, function ($e) {
            return $e;
        });

        $return['body2'] = $response->body();
        $return['json2'] = $response->json();
        $return['successful2'] = $response->successful();
        $return['failed2'] = $response->failed();
        $return['clientError2'] = $response->clientError();
        $return['serverError2'] = $response->serverError();

        dd($return);
    }

    public function ScbApi($account){

        $url = 'https://scb.z7z.work/' . $account . '/getbalance';

        $response = rescue(function () use ($url) {
            return Http::timeout(15)->withHeaders([
                'access-key' => 'b499fe72-a9fb-4a6a-817d-c096c39a6896'
            ])->post($url);

        }, function ($e) {
            return $e;
        });

        $return['body'] = $response->body();
        $return['json'] = $response->json();
        $return['successful'] = $response->successful();
        $return['failed'] = $response->failed();
        $return['clientError'] = $response->clientError();
        $return['serverError'] = $response->serverError();


        $url = 'https://scb.z7z.work/' . $account . '/transection';

        $response = rescue(function () use ($url) {
            return Http::timeout(15)->withHeaders([
                'access-key' => 'b499fe72-a9fb-4a6a-817d-c096c39a6896'
            ])->post($url);

        }, function ($e) {
            return $e;
        });

        $return['body2'] = $response->body();
        $return['json2'] = $response->json();
        $return['successful2'] = $response->successful();
        $return['failed2'] = $response->failed();
        $return['clientError2'] = $response->clientError();
        $return['serverError2'] = $response->serverError();

        dd($return);

    }

}
