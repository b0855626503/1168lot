<?php

namespace Gametech\Admin\Http\Controllers;

use App\Events\RealTimeMessage;
use App\Libraries\KbankBiz;
use App\Libraries\Ktb;
use App\Libraries\Scb;
use App\Libraries\simple_html_dom;
use App\Notifications\RealTimeNotification;
use Gametech\Payment\Models\BankAccount;
use Gametech\Payment\Models\BankPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Http\Client\Response;

class TestController extends AppBaseController
{
    private $PATH = '';

    protected $_config;

    public function __construct()
    {
        $this->_config = request('_config');


    }

    public function checkip(Request $request){
        $method1 = request()->ip();
//        $method2 = request()->getClientIp();
        $method2 = request()->ip();
        dd($method2);
//        echo $request->getClientIp();
    }


    public function index()
    {


        function clean($text)
        {
            $text = trim($text);
            $text = str_replace("&nbsp;", "", $text);
            return $text;
        }

//        config();
//        function $dom->load($str)
//        {
//            $dom = new simple_html_dom();
//
//            return $dom;
//        }


        $datenow = now()->toDateTimeString();

        $mobile_number = '0922945691';
        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOne('kbank', $mobile_number);

//        dd($bank);

        $balance = 0;
        $USERNAME = $bank->user_name; //"give855";
        $PASSWORD = $bank->user_pass; //"Zxcv@3622";


        $ACCOUNT_NAME = substr($bank['acc_no'], 0, 3) . "-" . substr($bank['acc_no'], 3, 1) . "-" . substr($bank['acc_no'], 4, 5) . "-" . substr($bank['acc_no'], 9, 1); //"719-220819-2"
        $accname = str_replace("-", "", $bank['acc_no']);
        $PATH = storage_path('cookies');
        $COOKIEFILE = $PATH . "/kbank" . $accname; //$COOKIEFILE .


        if (!is_writable($COOKIEFILE)) {
            echo 'Cookie file missing or not writable.';
            //continue;
        }
//echo $ACCOUNT_NAME;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        //   curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Googlebot/2.1; +http://www.google.com/bot.html) Chrome/W.X.Y.Z‡ Safari/537.36");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_DEFAULT);
        curl_setopt($ch, CURLOPT_CAINFO, $PATH . "/cacert.pem");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $COOKIEFILE);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $COOKIEFILE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        $hasspan = "";

        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, null);
        curl_setopt($ch, CURLOPT_URL, 'https://online.kasikornbankgroup.com/K-Online/indexHome.jsp');
        $data = curl_exec($ch);

        $dom = new simple_html_dom();
        $html = $dom->load($data);


        //echo "TEST";
        $hasspan = $html->find('span#7', 0);

//        dd($hasspan);

        if ($hasspan != "ออกจากระบบ") {
            echo "LOGIN";
            $form_field = array();
            $form_field['isConfirm	'] = 'T';
            $post_string = '';
            foreach ($form_field as $key => $value) {
                $post_string .= $key . '=' . urlencode($value) . '&';
            }
            $post_string = substr($post_string, 0, -1);

// pre login page
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
            curl_setopt($ch, CURLOPT_URL, 'https://online.kasikornbankgroup.com/K-Online/preLogin/popupPreLogin.jsp?lang=th&isConfirm=T');
            $data = curl_exec($ch);

// load login
            curl_setopt($ch, CURLOPT_URL, 'https://online.kasikornbankgroup.com/K-Online/login.do');
            curl_setopt($ch, CURLOPT_POST, 0);
            curl_setopt($ch, CURLOPT_POSTFIELDS, null);
            $data = curl_exec($ch);

            $html = $dom->load($data);

            $form_field = array();
            foreach ($html->find('form input') as $element) {
                $form_field[$element->name] = $element->value;
            }
            $form_field['userName'] = $USERNAME;
            $form_field['password'] = $PASSWORD;
            $post_string = '';
            foreach ($form_field as $key => $value) {
                $post_string .= $key . '=' . urlencode($value) . '&';
            }
            $post_string = substr($post_string, 0, -1);


// login
            curl_setopt($ch, CURLOPT_REFERER, 'https://online.kasikornbankgroup.com/K-Online/login.do');
            curl_setopt($ch, CURLOPT_URL, 'https://online.kasikornbankgroup.com/K-Online/login.do');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
            $data = curl_exec($ch);

            curl_setopt($ch, CURLOPT_POST, 0);
            curl_setopt($ch, CURLOPT_POSTFIELDS, null);
            curl_setopt($ch, CURLOPT_URL, 'https://online.kasikornbankgroup.com/K-Online/indexHome.jsp');
            $data = curl_exec($ch);

            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, null);
            curl_setopt($ch, CURLOPT_REFERER, 'https://online.kasikornbankgroup.com/K-Online/indexHome.jsp');
            curl_setopt($ch, CURLOPT_URL, 'https://online.kasikornbankgroup.com/K-Online/checkSession.jsp');
            $data = curl_exec($ch);

            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, null);
            curl_setopt($ch, CURLOPT_REFERER, 'https://online.kasikornbankgroup.com/K-Online/indexHome.jsp');
            curl_setopt($ch, CURLOPT_URL, 'https://online.kasikornbankgroup.com/K-Online/clearSession.jsp');
            $data = curl_exec($ch);

// redirect after login
            curl_setopt($ch, CURLOPT_POST, 0);
            curl_setopt($ch, CURLOPT_POSTFIELDS, null);
            curl_setopt($ch, CURLOPT_URL, 'https://online.kasikornbankgroup.com/K-Online/ib/redirectToIB.jsp?r=7027');
            $data = curl_exec($ch);
            $html = $dom->load($data);
            $form_field = array();
            foreach ($html->find('form input') as $element) {
                $form_field[$element->name] = $element->value;
            }
            $post_string = '';
            foreach ($form_field as $key => $value) {
                $post_string .= $key . '=' . urlencode($value) . '&';
            }
            $post_string = substr($post_string, 0, -1);

// welcom page
            curl_setopt($ch, CURLOPT_URL, 'https://ebank.kasikornbankgroup.com/retail/security/Welcome.do');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
            $data = curl_exec($ch);
            if (preg_match('/.*?Unsuccessful Login.*?/', $data)) {
                exit;
            }

        }


        curl_setopt($ch, CURLOPT_URL, 'https://ebank.kasikornbankgroup.com/retail/RetailWelcome.do');
        curl_setopt($ch, CURLOPT_POST, 0);
        $data = curl_exec($ch);
        $html = $dom->load($data);
//        print_r($html);
        $s = "เลขที่บัญชี";
        $table = $html->find('table[rules="rows"]', 1);
        if (!(empty($table))) {
            foreach ($table->find('tr') as $tr) {
                $td1 = clean($tr->find('td', 0)->plaintext);
                $pos = strpos($td1, $s);
                if ($pos !== false) {
                    continue;
                }
                //echo $accname."==".$td1;
                if ($td1 == $ACCOUNT_NAME) {
                    $balance = floatval(preg_replace('/[^0-9\.\+\-]/', '', str_replace(",", "", $tr->find('td', 3)->plaintext)));
                    break;
                }
            }
        }

        echo 'Balance ' . $balance;
        echo '<br>';

        if ($balance >= 0) {
            $bank->balance = $balance;
        }

        $bank->checktime = $datenow;
        $bank->save();


// last statement page
        curl_setopt($ch, CURLOPT_URL, 'https://ebank.kasikornbankgroup.com/retail/cashmanagement/TodayAccountStatementInquiry.do');
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, null);
        $data = curl_exec($ch);

        $data = iconv("windows-874", "utf-8", $data);

        $html = $dom->load($data);
        $form_field = array();
        foreach ($html->find('form[name="TodayStatementForm"] input') as $element) {
            $form_field[$element->name] = $element->value;
        }
// select account
        $s = $ACCOUNT_NAME;
        foreach ($html->find('select[name="acctId"] option') as $element) {
            $text = clean($element->plaintext);
            $pos = strpos($text, $s);
            if ($pos !== false) {
                $form_field['acctId'] = $element->value;
            }
        }
        $post_string = '';
        foreach ($form_field as $key => $value) {
            $post_string .= $key . '=' . urlencode($value) . '&';
        }
        $post_string = substr($post_string, 0, -1);

        curl_setopt($ch, CURLOPT_URL, 'https://ebank.kasikornbankgroup.com/retail/cashmanagement/TodayAccountStatementInquiry.do');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        $data = curl_exec($ch);


        $total = array();
        $s = 'วันที่';
        $html = $dom->load($data);
        $table = $html->find('table[rules="rows"]', 0);
//        print_r($table);
        if (!(empty($table))) {
            $dup = 0;
            foreach ($table->find('tr') as $tr) {
                $td1 = clean($tr->find('td', 0)->plaintext);
                $pos = strpos($td1, $s);
                if ($pos !== false) {
                    continue;
                }

                $list = [
                    'value' => '',
                    'time' => '',
                    'channel' => '',
                    'detail' => '',
                    'fee' => '',
                    'acc_num' => '',
                    'tx_hash' => '',
                ];

                preg_match_all('/<td class=inner_table_.*?>\s?(.*?)<\/td>/', $tr, $temp2);

                foreach ($temp2[1] as $key => $val) {
                    switch ($key) {
                        case 0:
                            $val = str_replace('<br>', '', $val);
                            $n = preg_split('/\s+/', substr($val, 0, -3));
                            $ndate = explode("/", $n[0]);
                            $list['time'] = strtotime('20' . $ndate[2] . '-' . $ndate[1] . '-' . $ndate[0] . ' ' . $n[1]);
                            $list['date'] = '20' . $ndate[2] . '-' . $ndate[1] . '-' . $ndate[0] . ' ' . $n[1];
                            break;
                        case 1:
                            $list['channel'] = $val;
                            break;
                        case 2:
                            $list['detail'] = $val;
                            break;
                        case 3:
                            if ($val != '') {
                                $list['value'] = "-" . floatval(preg_replace('/[^0-9\.\+\-]/', '', $val));
                            }
                            break;
                        case 4:
                            if ($val != '') {
                                $list['value'] = floatval(preg_replace('/[^0-9\.\+\-]/', '', $val));
                            }
                            break;
                        case 5:
                            $list['fee'] = floatval(preg_replace('/[^0-9\.\+\-]/', '', $val));
                            break;
                        case 6:
                            $list['acc_num'] = str_replace(array('x', '-'), array('*', ''), $val);
                            break;
                        case 7:
                            $list['detail'] .= ' (' . $val . ')';
                            break;
                    }

                }
                $list['value'] = str_replace(",", "", $list['value']);
                $list['tx_hash'] = md5($accname . $list['time'] . $list['value']);
                if ($list['value'] == "") {
                    continue;
                }


                $chk = BankPayment::where('tx_hash', $list['tx_hash'])->where('account_code', $bank->code)->count();

                if ($chk == 0) {
                    $newpayment = BankPayment::firstOrNew(['tx_hash' => $list['tx_hash'], 'account_code' => $bank->code]);
                    $newpayment->account_code = $bank->code;
                    $newpayment->bank = 'kbank_' . $accname;
                    $newpayment->bankstatus = 1;
                    $newpayment->bankname = 'KBANK';
                    $newpayment->bank_time = $list['date'];

                    $newpayment->atranferer = $list['acc_num'];
                    $newpayment->channel = $list['channel'];
                    $newpayment->value = $list['value'];
                    $newpayment->tx_hash = $list['tx_hash'];
                    $newpayment->detail = $list['detail'];
                    $newpayment->time = $list['date'];
                    $newpayment->create_by = 'SYSAUTO';
                    $newpayment->ip_topup = '';
                    $newpayment->save();

                    $total[] = $list['tx_hash'];
                }
            }

            // check next page
            $next = $html->find("a[href*='action=detail']");
            $totalPage = count($next);
            //$html->clear();
            //unset($html);
            //unset($html);
            if (!(empty($next))) {
                $currentPage = 1;
                foreach ($next as $a) {
                    $dup++;
                    $currentPage++;
                    if ($currentPage < ($totalPage - 1)) {
                        continue;
                    }
                    $total_next = array();

                    $_query = strstr($a->href, '?');

                    curl_setopt($ch, CURLOPT_URL, 'https://ebank.kasikornbankgroup.com/retail/cashmanagement/TodayAccountStatementInquiry.do' . $_query);
                    curl_setopt($ch, CURLOPT_POST, 0);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, null);
                    $data = curl_exec($ch);

                    $html = $dom->load($data);
                    $table = $html->find('table[rules="rows"]', 0);
                    if (!(empty($table))) {
                        foreach ($table->find('tr') as $tr) {
                            $td1 = clean($tr->find('td', 0)->plaintext);
                            $pos = strpos($td1, $s);
                            if ($pos !== false) {
                                continue;
                            }

                            $list = [
                                'value' => '',
                                'time' => '',
                                'channel' => '',
                                'detail' => '',
                                'fee' => '',
                                'acc_num' => '',
                                'tx_hash' => '',
                            ];

                            preg_match_all('/<td class=inner_table_.*?>\s?(.*?)<\/td>/', $tr, $temp2);
                            foreach ($temp2[1] as $key => $val) {
                                switch ($key) {
                                    case 0:
                                        $val = str_replace('<br>', '', $val);
                                        $n = preg_split('/\s+/', substr($val, 0, -3));
                                        $ndate = explode("/", $n[0]);
                                        $list['time'] = strtotime('20' . $ndate[2] . '-' . $ndate[1] . '-' . $ndate[0] . ' ' . $n[1]);
                                        $list['date'] = '20' . $ndate[2] . '-' . $ndate[1] . '-' . $ndate[0] . ' ' . $n[1];
                                        /*
                                        $list['time'] = $val;
                                        $list['time'] = str_replace('/', '-', $list['time']);
                                        $list['time'] = str_replace('<br>', '', $list['time']);
                                        preg_match('/([0-9]{2})-([0-9]{2})-([0-9]{2})\s{30}([0-9]{2}:[0-9]{2}:[0-9]{2})/', $list['time'], $ar);
                                        $list['time'] = strtotime($ar[1] . '-' . $ar[2] . '-20' . $ar[3] . 'T' . $ar[4] . '+0700');
                                         */
                                        break;
                                    case 1:
                                        $list['channel'] = $val;
                                        break;
                                    case 2:
                                        $list['detail'] = $val;
                                        break;
                                    case 3:
                                        if ($val != '') {
                                            $list['value'] = "-" . floatval(preg_replace('/[^0-9\.\+\-]/', '', $val));
                                        }
                                        break;
                                    case 4:
                                        if ($val != '') {
                                            $list['value'] = floatval(preg_replace('/[^0-9\.\+\-]/', '', $val));
                                        }
                                        break;
                                    case 5:
                                        $list['fee'] = floatval(preg_replace('/[^0-9\.\+\-]/', '', $val));
                                        break;
                                    case 6:
                                        $list['acc_num'] = str_replace(array('x', '-'), array('*', ''), $val);
                                        break;
                                    case 7:
                                        $list['detail'] .= ' (' . $val . ')';
                                        break;
                                }
                            }
                            $list['value'] = str_replace(",", "", $list['value']);
                            $list['tx_hash'] = md5($accname . $list['time'] . $list['value']);
                            if ($list['value'] == "") {
                                continue;
                            }
                            $chk = BankPayment::where('tx_hash', $list['tx_hash'])->where('account_code', $bank->code)->count();

                            if ($chk == 0) {

                                $newpayment = BankPayment::firstOrNew(['tx_hash' => $list['tx_hash'], 'account_code' => $bank->code]);
                                $newpayment->account_code = $bank->code;
                                $newpayment->bank = 'kbank_' . $accname;
                                $newpayment->bankstatus = 1;
                                $newpayment->bankname = 'KBANK';
                                $newpayment->bank_time = $list['date'];

                                $newpayment->atranferer = $list['acc_num'];
                                $newpayment->channel = $list['channel'];
                                $newpayment->value = $list['value'];
                                $newpayment->tx_hash = $list['tx_hash'];
                                $newpayment->detail = $list['detail'];
                                $newpayment->time = $list['date'];
                                $newpayment->create_by = 'SYSAUTO';
                                $newpayment->ip_topup = '';
                                $newpayment->save();

                                $total[] = $list['tx_hash'];
                            } else {
//                                $total[] = $list['tx_hash'];
//                                $dp = array_count_values($total);
//                                for ($d = $chk; $d < $dp[$list['tx_hash']]; $d++) {
//
//                                    $newpayment = BankPayment::firstOrNew(['tx_hash' => $list['tx_hash'], 'account_code' => $bank->code]);
//                                    $newpayment->account_code = $bank->code;
//                                    $newpayment->bank = 'kbank_' . $accname;
//                                    $newpayment->bankstatus = 1;
//                                    $newpayment->bankname = 'KBANK';
//                                    $newpayment->bank_time = $list['date'];
//
//                                    $newpayment->atranferer = $list['acc_num'];
//                                    $newpayment->channel = $list['channel'];
//                                    $newpayment->value = $list['value'];
//                                    $newpayment->tx_hash = $list['tx_hash'];
//                                    $newpayment->detail = $list['detail'];
//                                    $newpayment->time = $list['date'];
//                                    $newpayment->create_by = 'SYSAUTO';
//                                    $newpayment->ip_topup = '';
//                                    $newpayment->save();
//                                   }
                            }

                        }
                        //$total_next[] = $list['tx_hash'];
                        //$total = array_merge($total, $total_next);
                    }
                    //$html->clear();
                    //unset($html);
                }
            } // next
        }
    }
	
	public function TestBroadcast($message)
	{
		broadcast(new RealTimeMessage($message));
	}

    public function scb()
    {

        function extract_int($str)
        {
            $str = str_replace(",", "", $str);
            preg_match('/[0-9,]{1,}\.[0-9]{2}/', $str, $temp);
            return $temp[0];
        }

        $datenow = now()->toDateTimeString();

        $mobile_number = '7192371759';
        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOne('scb', $mobile_number);


        $bank_number = $bank['acc_no'];
        $bank_username = $bank['user_name'];
        $bank_password = $bank['user_pass'];

        $em = new Scb($bank_username, $bank_password, $bank_number);
        $collect = $em->getTransection();
        $balance = extract_int($em->getbalance());
        $collect = json_decode($collect, true);

        if ($balance >= 0) {
            $bank->balance = $balance;
        }

        $bank->checktime = $datenow;
        $bank->save();

        if (!is_null($collect)) {
            $DATE = '';
            $TIME = '';
            $outputs = [];
            foreach ($collect as $item) {
                $Account = $item['Account'];
                $type = $item['type'];

                if ($type == 'in') {
                    if (!strstr($Account, 'SCB')) {
                        $DEPOSIT_CLIENTID = substr($Account, strpos($Account, 'X') + 1, strlen($Account) - strpos($Account, 'X'));

                    } else {

                        $DEPOSIT_CLIENTID = substr($Account, strpos($Account, 'x') + 1, 4);
                    }


                    $DATE = str_replace('/', '-', substr($item['date'], 0, 10));
                    $TIME = substr($item['date'], -8);
                    $DEPOSIT_DATE = date('Y-m-d', strtotime($DATE)) . ' ' . $TIME;
                    $DEPOSIT_AMOUNT = str_replace(',', '', number_format($item["amount"]));


                    $txhashPlaintext = $DEPOSIT_DATE . $DEPOSIT_CLIENTID . $DEPOSIT_AMOUNT;
                    $txhash = md5($txhashPlaintext);

                    $chk = BankPayment::where('tx_hash', $txhash)->where('account_code', $bank->code)->count();
                    if ($chk == 0) {

                        $newpayment = BankPayment::firstOrNew(['tx_hash' => $txhash, 'account_code' => $bank->code]);
                        $newpayment->account_code = $bank->code;
                        $newpayment->bank = 'scb_' . $bank->acc_no;
                        $newpayment->bankstatus = 1;
                        $newpayment->bankname = 'SCB';
                        $newpayment->bank_time = $DEPOSIT_DATE;
                        $newpayment->time = $DEPOSIT_DATE;
                        $newpayment->atranferer = $DEPOSIT_CLIENTID;
                        $newpayment->channel = $item['way'];
                        $newpayment->value = $DEPOSIT_AMOUNT;
                        $newpayment->tx_hash = $txhash;
                        $newpayment->detail = $item['Account'];
                        $newpayment->create_by = 'SYSAUTO';
                        $newpayment->ip_topup = '';
                        $newpayment->save();

                    }

                } elseif ($type == 'out') {

                    $DEPOSIT_CLIENTID = '';


                    $DATE = str_replace('/', '-', substr($item['date'], 0, 10));
                    $TIME = substr($item['date'], -8);
                    $DEPOSIT_DATE = date('Y-m-d', strtotime($DATE)) . ' ' . $TIME;
                    $DEPOSIT_AMOUNT = str_replace(',', '', number_format($item["amount"]));


                    $txhashPlaintext = $DEPOSIT_DATE . $DEPOSIT_CLIENTID . $DEPOSIT_AMOUNT;
                    $txhash = md5($txhashPlaintext);

                    $chk = BankPayment::where('tx_hash', $txhash)->where('account_code', $bank->code)->count();
                    if ($chk == 0) {

                        $newpayment = BankPayment::firstOrNew(['tx_hash' => $txhash, 'account_code' => $bank->code]);
                        $newpayment->account_code = $bank->code;
                        $newpayment->bank = 'scb_' . $bank->acc_no;
                        $newpayment->bankstatus = 1;
                        $newpayment->bankname = 'SCB';
                        $newpayment->bank_time = $DEPOSIT_DATE;
                        $newpayment->time = $DEPOSIT_DATE;
                        $newpayment->atranferer = $DEPOSIT_CLIENTID;
                        $newpayment->channel = $item['way'];
                        $newpayment->value = $DEPOSIT_AMOUNT;
                        $newpayment->tx_hash = $txhash;
                        $newpayment->detail = $item['Account'];
                        $newpayment->create_by = 'SYSAUTO';
                        $newpayment->ip_topup = '';
                        $newpayment->save();

                    }
                }
            }
        }
    }

    public function chuba()
    {
        $session_id = request()->getSession()->getId();
//        define('PASSKEY', 'd6as86d67-c67s84-4s8c9-88s889-3e36bs845e1s89');
        $passkey = 'd6as86d67-c67s84-4s8c9-88s889-3e36bs845e1s89';
        $url = "http://manage.cluba8.com/api_service/create-check-account";
        $ch = curl_init($url);
        $data = array(
            "accountStatus" => 1,
            "accountType" => 1,
            "agentLoginName" => "testzero99",
            "balance" => 0,
            "birthDate" => "2021-06-01",
            "email" => "",
            "firstName" => "Gametecg",
            "gender" => "M",
            "lastName" => "Boat",
            "loginName" => "CH1BA12823",
            "mode" => "real",
            "password" => "Aa685953",
            "timeStamp" => "2021-07-06T10:20:33",
        );
        echo "<pre>";
        echo "<h3>Create User Request</h3>";
        print_r($data);
        echo "</pre>";
        $postString = "";
        foreach ($data as $keyR => $value) {
            $postString .= $keyR . '=' . $value . '&';
        }
        $postString = substr($postString, 0, -1);
        $hashKey = md5($postString);
        $payload = $data;
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $headers2 = array('Content-Type: application/json', 'Pass-Key: ' . $passkey, 'Session-Id: ' . $session_id, 'Hash: ' . $hashKey);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        echo "<pre>";
        echo "<h3>Create User Response</h3>";
        print_r($result);
    }

    public function test()
    {
        $data = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9 .eyJldmVudF90eXBlIjoiUDJQIiwicmVjZWl2ZWRfdGltZSI6IjIwMjItMDEtMzFUMTM6MDI6MjMrMDcwMCIsImFtb3VudCI6MTAwMDAsInNlbmRlcl9tb2JpbGUiOiIwMTIzNDU2Nzg5IiwibWVzc2FnZSI6IiIsImlhdCI6MTY1MzUzODc5M30 .wb-4vOY6ASVl3nVlALC0y1TIl-Gs0XEk5AqYdqtoFz0';
        $message = base64_decode($data);
        $message = Str::replace('}{',',',$message);
        $message = Str::replace('"','',$message);
        $message = Str::of($message)->between('{', '}');
//        $message = Str::replace(':','=>',$message);
//        $keys = array_filter(explode(':', $message));
//        $values = array_filter(explode(':', $message));
//      $message = explode(",",$message);
//        $keyPairs = array_combine($keys, $values);
//        $message = collect($message)->toArray();
        $message = Str::of($message)->explode(',');
        for($i=0; $i < count($message); $i++){
            $key_value = explode(':', $message[$i]);
//            dd($key_value);
            if($key_value[0] == 'received_time'){
                $messages[$key_value[0]] = $key_value[1].":".$key_value[2].":".$key_value[3];
            }else{
                $messages[$key_value[0]] = $key_value[1];
            }

    }
        $date = Str::replace('T',' ',$messages['received_time']);
        $date = Str::replace('+0700','',$date);
        $messages['received_time'] = $date;
//        $message = collect($message)->toArray();
//        dd(($messages));
        dd($messages);
//        echo $messages['amount'];
    }

    public function cutString($content, $text1, $text2)
    {
        $fcontents2 = stristr($content, $text1);
        $rest2 = substr($fcontents2, strlen($text1));
        $extra2 = stristr($fcontents2, $text2);
        $titlelen2 = strlen($rest2) - strlen($extra2);
        $gettitle2 = trim(substr($rest2, 0, $titlelen2));
        return $gettitle2;
    }

    public function ktb(){
        $mobile_number = '2140527607';
        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOne('ktb', $mobile_number);


        $bank_number = $bank['acc_no'];
        $bank_username = $bank['user_name'];
        $bank_password = $bank['user_pass'];
        $accountTokenNo = $bank['acctoken'];
        $userTokenId = $bank['usertoken'];



        $ktb = new Ktb();
        $gettran = $ktb->gettransactiontest($accountTokenNo, $userTokenId);
        $resobj = json_decode($gettran, true);
        $balance = $resobj['balance'];
        $collect = $resobj['data'];
    }

    public function noti(){

        $member = app('Gametech\Member\Repositories\MemberRepository')->find(6);

//        Notification::send($member, new RealTimeNotification('เติมเงินสำเร็จแล้ว'));
        $member->notify(new RealTimeNotification('Hello World'));
        echo 'complete';
    }

    public function alert(){

//        broadcast(new RealTimeUpdate());

//        $member = app('Gametech\Member\Repositories\MemberRepository')->find(6);
        event(new \App\Events\RealTimeMessage('Hello World'));
//        Notification::send($member, new RealTimeNotification('เติมเงินสำเร็จแล้ว'));
//        $member->notify(new RealTimeNotification('Hello World'));
        echo 'complete';
    }

    public function chkbank(){
        $payment = BankPayment::latest()->first();
//        $bank = BankAccount::find($payment->account_code)->bank->shortcode;
        $bank = BankAccount::where('status_topup','Y')->where('enable','Y')->where('bank_type',1)->where('code',$payment->account_code)->first()->bank->shortcode;
        dd($bank);
    }


    public function getsign($user){

        $data = 'Zhso17'.$user;
        $time = 1676966812828;
        $this->auth = '529E9E66-009C-4376-805D-5EA37712DC5B';
        $sign = hash('sha256',strtolower($data).$time.strtolower($this->auth));
        return $sign;
    }

    public function kbank(){
//        $kbank = new Kbank();

        $datenow = now()->toDateTimeString();
        $header = [];
        $response = [];
        $mobile_number = '0568862848';
        $update = true;

        $USERNAME = 'aongard542496'; //"give855";
        $PASSWORD = 'Aa-542496'; //"Zxcv@3622";

//        $ACCOUNT_NAME = substr($bank['acc_no'], 0, 3) . "-" . substr($bank['acc_no'], 3, 1) . "-" . substr($bank['acc_no'], 4, 5) . "-" . substr($bank['acc_no'], 9, 1); //"719-220819-2"
//        $accname = str_replace("-", "", $bank['acc_no']);

        $em = new KbankBiz();
        $em->setLogin($USERNAME, $PASSWORD);
        $em->setAccountNumber('1603425509');
        if ($em->login()) {
            $collect = $em->getTransaction();
            return $collect;
        }


        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOne('kbank', $mobile_number);
        if(!$bank){
            return true;
        }

        if ($bank->local == 'Y') {

            $balance = 0;
            $USERNAME = $bank->user_name; //"give855";
            $PASSWORD = $bank->user_pass; //"Zxcv@3622";

            $ACCOUNT_NAME = substr($bank['acc_no'], 0, 3) . "-" . substr($bank['acc_no'], 3, 1) . "-" . substr($bank['acc_no'], 4, 5) . "-" . substr($bank['acc_no'], 9, 1); //"719-220819-2"
            $accname = str_replace("-", "", $bank['acc_no']);

            $em = new KbankBiz();
            $em->setLogin($USERNAME, $PASSWORD);
            $em->setAccountNumber($accname);

            if ($em->login()) {

                $balance = $em->getBalance();
                if ($balance >= 0) {
                    $bank->balance = $balance;
                }
                $bank->checktime = $datenow;
                $bank->save();

                $path = storage_path('logs/kbank/getbalance_' . $accname . '_' . now()->format('Y_m_d') . '.log');
                file_put_contents($path, print_r($balance, true));

                $collect = $em->getTransaction();

                $path = storage_path('logs/kbank/gettransaction_' . $accname . '_' . now()->format('Y_m_d') . '.log');
                file_put_contents($path, print_r($collect, true));

                if (count($collect) > 0) {

                    foreach ($collect as $list) {

                        if (core()->DateDiff($list['date']) > 1) continue;

                        $list['value'] = str_replace(",", "", $list['in']);
                        $list['tx_hash'] = md5($accname . $list['date'] . $list['value']);
                        if ($list['value'] == "") {
                            continue;
                        }

                        $newpayment = BankPayment::firstOrNew(['tx_hash' => $list['tx_hash'], 'account_code' => $bank->code]);
                        $newpayment->account_code = $bank->code;
                        $newpayment->bank = 'kbank_' . $accname;
                        $newpayment->bankstatus = 1;
                        $newpayment->bankname = 'KBANK';
                        $newpayment->bank_time = $list['date'];
                        $newpayment->report_id = rtrim($list['report_id'], 'A');
                        $newpayment->atranferer = $list['fromaccno'];
                        $newpayment->channel = $list['channel'];
                        $newpayment->value = $list['value'];
                        $newpayment->tx_hash = $list['tx_hash'];
                        $newpayment->detail = $list['info'];
                        $newpayment->title = $list['title'];
                        $newpayment->time = $list['date'];
                        $newpayment->create_by = 'SYSAUTO';
                        $newpayment->ip_topup = '';
                        $newpayment->save();

                    }

                }
            }

        } else {

            $accname = str_replace("-", "", $bank['acc_no']);

            $url = 'https://z.z7z.work/kbank/' . $mobile_number . '/transection.php';

            $response = rescue(function () use ($url) {
                return Http::timeout(15)->withHeaders([
                    'access-key' => '0dbbe3a5-8a3d-4505-9a8e-5790b4a6c90d'
                ])->post($url);

            }, function ($e) {

                return $e;

            }, true);

            if ($response->failed()) {
                return false;
            }

            if ($response->successful()) {

                $response = $response->json();

                $lists = $response['data']['transection'];
                $balance = $response['data']['acc_balance'];

                $bank->balance = str_replace(',', '', number_format((float)$balance, 2));
                $bank->checktime = $datenow;
                $bank->save();

                if (count($lists) > 0) {

                    foreach ($lists as $list) {

                        if (core()->DateDiff($list['date_time']) > 1) continue;


                        $amount = floatval(preg_replace('/[^0-9\.\+\-]/', '', $list['amount']));
                        $amount = str_replace(",", "", $amount);

                        if ($list['amount'] < 0) {
                            $amount = '-' . $amount;
                        }

                        if ($list['ref_bank'] == 'undefined') {
                            $list['ref_bank'] = '';
                        }

                        if ($list['ref_name'] == 'undefined') {
                            $list['ref_name'] = 'ไม่มีข้อมูล';
                        }


                        $list['tx_hash'] = md5($accname . $list['date_time'] . $amount);

                        $list['ref'] = str_replace('X', '*', $list['ref']);

                        if ($list['ref'] == 'undefined') {
                            $list['ref'] = '';
                        }

                        $newpayment = BankPayment::firstOrNew(['tx_hash' => $list['tx_hash'], 'account_code' => $bank->code]);
                        $newpayment->account_code = $bank->code;
                        $newpayment->bank = 'kbank_' . $accname;
                        $newpayment->bankstatus = 1;
                        $newpayment->bankname = 'KBANK';
                        $newpayment->bank_time = $list['date_time'];

                        $newpayment->title = $list['list'];
                        $newpayment->report_id = rtrim($list['ref_bank'], 'A');
                        $newpayment->atranferer = $list['ref'];
                        $newpayment->channel = $newpayment->report_id;
                        $newpayment->value = $amount;
                        $newpayment->tx_hash = $list['tx_hash'];
                        if ($list['ref_bank'] == 'KBANK') {
                            $newpayment->detail = $list['ref'];
                        } else {
                            $newpayment->detail = $list['ref'] . ' (' . $list['ref_name'] . ')';
                        }

                        $newpayment->time = $list['date_time'];
                        $newpayment->create_by = 'SYSAUTO';
                        $newpayment->ip_topup = '';
                        $newpayment->save();

                    }

                }

            }

        }
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

        $return[0]['desc'] = 'เชคยอด kbiz';
        $return[0]['url'] = $url;
        $return[0]['body'] = $response->body();
        $return[0]['json'] = $response->json();
        $return[0]['successful'] = $response->successful();
        $return[0]['failed'] = $response->failed();
        $return[0]['clientError'] = $response->clientError();
        $return[0]['serverError'] = $response->serverError();

        $url = 'https://api-kbank.me2me.biz/kbiz/' . $account . '/status?action=start';

        $response = rescue(function () use ($url) {
            return Http::timeout(15)->withHeaders([
                'access-key' => 'b499fe72-a9fb-4a6a-817d-c096c39a6896'
            ])->post($url);

        }, function ($e) {
            return $e;
        });

        $return[1]['desc'] = 'เปิดบอท kbiz';
        $return[1]['url'] = $url;
        $return[1]['body'] = $response->body();
        $return[1]['json'] = $response->json();
        $return[1]['successful'] = $response->successful();
        $return[1]['failed'] = $response->failed();
        $return[1]['clientError'] = $response->clientError();
        $return[1]['serverError'] = $response->serverError();

        $url = 'https://api-kbank.me2me.biz/kbiz/' . $account . '/transection';

        $response = rescue(function () use ($url) {
            return Http::timeout(15)->withHeaders([
                'access-key' => 'b499fe72-a9fb-4a6a-817d-c096c39a6896'
            ])->post($url);

        }, function ($e) {
            return $e;
        });

        $return[2]['desc'] = 'เชครายการ kbiz';
        $return[2]['url'] = $url;
        $return[2]['body'] = $response->body();
        $return[2]['json'] = $response->json();
        $return[2]['successful'] = $response->successful();
        $return[2]['failed'] = $response->failed();
        $return[2]['clientError'] = $response->clientError();
        $return[2]['serverError'] = $response->serverError();

        $url = 'https://kbanks.z7z.work/' . $account . '/getbalance.php';

        $response = rescue(function () use ($url) {
            return Http::timeout(15)->withHeaders([
                'access-key' => 'ca23e34e-74fb-477a-a64b-e58b9ef4b51e'
            ])->post($url);

        }, function ($e) {
            return $e;
        });

        $return[3]['desc'] = 'เชครายการ Kbank z7z';
        $return[3]['url'] = $url;
        $return[3]['body'] = $response->body();
        $return[3]['json'] = $response->json();
        $return[3]['successful'] = $response->successful();
        $return[3]['failed'] = $response->failed();
        $return[3]['clientError'] = $response->clientError();
        $return[3]['serverError'] = $response->serverError();


        $url = 'https://kbanks.z7z.work/' . $account . '/transaction.php';

        $response = rescue(function () use ($url) {
            return Http::timeout(15)->withHeaders([
                'access-key' => 'ca23e34e-74fb-477a-a64b-e58b9ef4b51e'
            ])->post($url);

        }, function ($e) {
            return $e;
        });

        $return[4]['desc'] = 'เชครายการ Kbank z7z';
        $return[4]['url'] = $url;
        $return[4]['body'] = $response->body();
        $return[4]['json'] = $response->json();
        $return[4]['successful'] = $response->successful();
        $return[4]['failed'] = $response->failed();
        $return[4]['clientError'] = $response->clientError();
        $return[4]['serverError'] = $response->serverError();

        dd($return);
    }

    public function ScbApi($account){

        $url = 'https://scb.z7z.work/' . $account . '/getbalance';

        $response = rescue(function () use ($url) {
            return Http::timeout(15)->withHeaders([
                'access-key' => '53cb498c-8516-420f-bd65-90754e19bfbf'
            ])->acceptJson()->post($url);

        }, function ($e) {
            return $e;
        });

        $return[0]['desc'] = 'เชคยอด SCB z7z';
        $return[0]['url'] = $url;
        $return[0]['body'] = $response->body();
        $return[0]['json'] = $response->json();
        $return[0]['successful'] = $response->successful();
        $return[0]['failed'] = $response->failed();
        $return[0]['clientError'] = $response->clientError();
        $return[0]['serverError'] = $response->serverError();

        $url = 'https://scb.z7z.work/' . $account . '/transection';

        $response = rescue(function () use ($url) {
            return Http::timeout(15)->withHeaders([
                'access-key' => '53cb498c-8516-420f-bd65-90754e19bfbf'
            ])->post($url);

        }, function ($e) {
            return $e;
        });

        $return[1]['desc'] = 'เชครายการ SCB z7z';
        $return[1]['url'] = $url;
        $return[1]['body'] = $response->body();
        $return[1]['json'] = $response->json();
        $return[1]['successful'] = $response->successful();
        $return[1]['failed'] = $response->failed();
        $return[1]['clientError'] = $response->clientError();
        $return[1]['serverError'] = $response->serverError();

//        $return[0]['desc'] = 'เชคยอด SCB z7z';
//        $return[0]['url'] = $url;
//        $return[0]['body'] = $response->body();
//        $return[0]['json'] = $response->json();
//        $return[0]['successful'] = $response->successful();
//        $return[0]['failed'] = $response->failed();
//        $return[0]['clientError'] = $response->clientError();
//        $return[0]['serverError'] = $response->serverError();

//        $url = 'https://scb.z7z.work/' . $account . '/transfer';
//
//        $data = [
//            'ToBank' => '7322436536',
//            'ToBankCode' => 'kbank',
//            'amount' => 1
//        ];
//
//        $response = rescue(function () use ($url,$data) {
//            return Http::timeout(15)->withHeaders([
//                'access-key' => '53cb498c-8516-420f-bd65-90754e19bfbf'
//            ])->asForm()->post($url,$data);
//
//        }, function ($e) {
//            return $e;
//        });
//
//        $return[2]['desc'] = 'เทสโอน SCB z7z';
//        $return[2]['url'] = $url;
//        $return[2]['body'] = $response->body();
//        $return[2]['json'] = $response->json();
//        $return[2]['successful'] = $response->successful();
//        $return[2]['failed'] = $response->failed();
//        $return[2]['clientError'] = $response->clientError();
//        $return[2]['serverError'] = $response->serverError();

        dd($return);

    }

    public function BayApi($account){

        $url = 'https://bays.z7z.work/' . $account . '/transection.php';

        $response = rescue(function () use ($url) {
            return Http::timeout(15)->withHeaders([
                'access-key' => '0dbbe3a5-8a3d-4505-9a8e-5790b4a6c90d'
            ])->post($url);

        }, function ($e) {
            return $e;
        });

        $data = $response->json();
        $lists = $data['data'];
        krsort($lists);

        $return[0]['desc'] = 'เชคยอด Bay z7z';
        $return[0]['url'] = $url;
        $return[0]['body'] = $response->body();
        $return[0]['json']['data'] = $lists;
        $return[0]['json']['available_balance'] = $data['available_balance'];
        $return[0]['successful'] = $response->successful();
        $return[0]['failed'] = $response->failed();
        $return[0]['clientError'] = $response->clientError();
        $return[0]['serverError'] = $response->serverError();



        dd($return);

    }
}
