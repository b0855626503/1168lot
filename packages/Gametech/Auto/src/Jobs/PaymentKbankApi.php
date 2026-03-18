<?php

namespace Gametech\Auto\Jobs;


use App\Libraries\Kbank;
use App\Libraries\simple_html_dom;
use Gametech\Payment\Models\BankPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Throwable;


class PaymentKbankApi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public $tries = 1;

    public $maxExceptions = 5;

    public $retryAfter = 130;

    protected $id;



    public function __construct($id)
    {
        $this->id = $id;
    }


    public function handle()
    {
        $kbank = new Kbank();

        $datenow = now()->toDateTimeString();
        $header = [];
        $response = [];
        $mobile_number = $this->id;
        $update = true;

        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOne('kbank', $mobile_number);


        $balance = 0;
        $USERNAME = $bank->user_name; //"give855";
        $PASSWORD = $bank->user_pass; //"Zxcv@3622";


        $ACCOUNT_NAME = substr($bank['acc_no'], 0, 3) . "-" . substr($bank['acc_no'], 3, 1) . "-" . substr($bank['acc_no'], 4, 5) . "-" . substr($bank['acc_no'], 9, 1); //"719-220819-2"
        $accname = str_replace("-", "", $bank['acc_no']);
        $PATH = storage_path('cookies');
        $COOKIEFILE = $PATH . "/kbank" . $accname; //$COOKIEFILE .

        if(!File::exists(storage_path('logs/kbank'))){
            File::makeDirectory(storage_path('logs/kbank'));
        }

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
                $td1 = $kbank->clean($tr->find('td', 0)->plaintext);
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

        $path = storage_path('logs/kbank/getbalance_' . $accname . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($balance, true));

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
            $text = $kbank->clean($element->plaintext);
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
                $td1 = $kbank->clean($tr->find('td', 0)->plaintext);
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

                if(core()->DateDiff($list['date']) > 1) continue;

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
                $newpayment->detail = $list['acc_num'];
                $newpayment->time = $list['date'];
                $newpayment->create_by = 'SYSAUTO';
                $newpayment->ip_topup = '';
                $newpayment->save();

                $path = storage_path('logs/kbank/gettran_' . $accname . '_' . now()->format('Y_m_d') . '.log');
                file_put_contents($path, print_r($list, true));

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
                            $td1 = $kbank->clean($tr->find('td', 0)->plaintext);
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

                            if(core()->DateDiff($list['date']) > 1) continue;

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
                            $newpayment->detail = $list['acc_num'];
                            $newpayment->time = $list['date'];
                            $newpayment->create_by = 'SYSAUTO';
                            $newpayment->ip_topup = '';
                            $newpayment->save();


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

    public function failed(Throwable $exception)
    {
        report($exception);
    }
}
