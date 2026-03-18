<?php


namespace App\Libraries;

use Curl\Curl;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use voku\helper\HtmlDomParser;
use Illuminate\Http\Client\Response;

class Scb
{
    private $Cookies = array();
    private $UserName = null;
    private $Password = null;
    private $data = array();

    private $baseUrl = "https://www.scbeasy.com/";

    public function __construct($UserName, $Password, $AccountNumber)
    {
        $this->UserName = $UserName;
        $this->Password = $Password;
        $this->AccountNumber = $AccountNumber;
        $dir = storage_path('cookies');
        $cookiesPath = $dir . "/cookies-" . $UserName . ".txt";
        $dataPath = $dir . "/data-" . $UserName . ".json";

        if (!File::exists(storage_path('logs/scb'))) {
            File::makeDirectory(storage_path('logs/scb'));
        }

        if (!file_exists($cookiesPath) || !file_exists($dataPath)) {
            $this->Login();
        } else {
            $this->Cookies = unserialize(file_get_contents($cookiesPath));
            $this->data = json_decode(file_get_contents($dataPath), true);
        }

//        $exists = Storage::exists($dir . '/' . $cookiesPath);
//        if (!$exists) {
//            $this->Login();
//        } else {
//
//            $this->Cookies = unserialize(Storage::get($dir . '/' . $cookiesPath));
//            $this->data = json_decode(Storage::get($dir . '/' . $dataPath), true);
//
//        }
    }

    public function extract_int($str)
    {
        $str = str_replace(",", "", $str);
        preg_match('/[0-9,]{1,}\.[0-9]{2}/', $str, $temp);
        return ($temp[0] ?? '');
    }

    public function Login()
    {
        $UserName = $this->UserName;
        $Password = $this->Password;

        $dir = storage_path('cookies');
        $cookiesPath = $dir . "/cookies-" . $UserName . ".txt";
        $dataPath = $dir . "/data-" . $UserName . ".json";

        if (file_exists($cookiesPath)) {
            unlink($cookiesPath);
        }
        if (file_exists($dataPath)) {
            unlink($dataPath);
        }


//        $exists = Storage::exists($cookiesPath);
//        if ($exists) {
//            Storage::delete($cookiesPath);
//        }
//        $exists = Storage::exists($dataPath);
//        if ($exists) {
//            Storage::delete($dataPath);
//        }

//        $param = $this->data;
//        $cookies = $this->Cookies;

//        $response = rescue(function () {
//
//            $url = $this->baseUrl . 'v1.4/site/presignon/index.asp';
//
//            return Http::timeout(60)->withoutVerifying()->get($url);
//
//        }, function ($e) {
//
//            return $e;
//
//        }, true);
//
//        if ($response->successful()) {
//            $this->Cookies = collect($response->cookies->toArray())->keyBy('Name')->map->Value;
////            $this->Cookies = $response->cookies();
//            $path = storage_path('cookies/cookies-' . $UserName . '.txt');
//            file_put_contents($path, $this->Cookies);
//
//            $cookies = $this->Cookies;
//            $fields = [
//                'LOGIN' => $UserName,
//                'PASSWD' => $Password,
//                'LANG' => "T",
//                'lgin.x' => '0',
//                'lgin.y' => '0',
//            ];
//
//            $responses = rescue(function () use ($fields, $cookies) {
//
//                $url = $this->baseUrl . 'online/easynet/page/lgn/login.aspx';
//
//                return Http::timeout(60)->withCookies($cookies, $this->baseUrl)->withoutVerifying()->asForm()->post($url, $fields);
//
//            }, function ($e) {
//
//                return $e;
//
//            }, true);
//
//            $path = storage_path('logs/scb/debug_' . $this->AccountNumber . '_' . now()->format('Y_m_d') . '.log');
//            file_put_contents($path, print_r($responses, true));
//
//
//            if ($responses->successful()) {
//                $html = $responses->body();
//
////                $path = storage_path('logs/scb/debug_' . $this->AccountNumber . '_' . now()->format('Y_m_d') . '.log');
////                file_put_contents($path, print_r($html, true));
//
//                $doms = new simple_html_dom();
//                $dom = $doms->load($html);
////        dd($dom->find('input[name=SESSIONEASY]',0)->value);
////        $doms = HtmlDomParser::str_get_html($html);
////        dd($doms->findOne('input[name=SESSIONEASY]'));
//
//                $re__VIEWSTATE = '/id="__VIEWSTATE" value="(.*)"/m';
//                preg_match_all($re__VIEWSTATE, $html, $__VIEWSTATE, PREG_SET_ORDER, 0);
//                $re__VIEWSTATEGENERATOR = '/id="__VIEWSTATEGENERATOR" value="(.*)"/m';
//                preg_match_all($re__VIEWSTATEGENERATOR, $html, $__VIEWSTATEGENERATOR, PREG_SET_ORDER, 0);
//
//                $SESSIONEASY = ($dom->find('input[name=SESSIONEASY]', 0)->value ?? null);
////        $SESSIONEASY = $dom->find('input[name=SESSIONEASY]', 0)->getAttribute('value');
////                $SESSIONEASY = $dom->findOne('input[name=SESSIONEASY]')->getAttribute("value");
////        dd($SESSIONEASY);
//                if (is_null($SESSIONEASY)) $this->Login();
//
////        $curl->setCookies($curl->getResponseCookies());
//
//                $__VIEWSTATE = $__VIEWSTATE[0][1];
//                $__VIEWSTATEGENERATOR = $__VIEWSTATEGENERATOR[0][1];
//                $fields = array(
//                    '__VIEWSTATEGENERATOR' => $__VIEWSTATEGENERATOR,
//                    '__VIEWSTATE' => $__VIEWSTATE,
//                    'SESSIONEASY' => $SESSIONEASY,
//                    'SELACC_SHOW' => "0"
//                );
//                $this->data = $fields;
////        Storage::put($dataPath, json_encode($fields));
//                file_put_contents($dataPath, json_encode($fields));
//
//            }

//            $path = storage_path('logs/scb/debug' . $this->AccountNumber . '_' . now()->format('Y_m_d') . '.log');
//            file_put_contents($path, print_r($html, true));
//
//            $doms = new simple_html_dom();
//            $dom = $doms->load($html);
////        dd($dom->find('input[name=SESSIONEASY]',0)->value);
////        $doms = HtmlDomParser::str_get_html($html);
////        dd($doms->findOne('input[name=SESSIONEASY]'));
//
//            $re__VIEWSTATE = '/id="__VIEWSTATE" value="(.*)"/m';
//            preg_match_all($re__VIEWSTATE, $html, $__VIEWSTATE, PREG_SET_ORDER, 0);
//            $re__VIEWSTATEGENERATOR = '/id="__VIEWSTATEGENERATOR" value="(.*)"/m';
//            preg_match_all($re__VIEWSTATEGENERATOR, $html, $__VIEWSTATEGENERATOR, PREG_SET_ORDER, 0);
//
//            $SESSIONEASY = ($dom->find('input[name=SESSIONEASY]', 0)->value ?? '');
////        $SESSIONEASY = $dom->find('input[name=SESSIONEASY]', 0)->getAttribute('value');
////        $SESSIONEASY =  $dom->findOne('input[name=SESSIONEASY]')->getAttribute("value");
////        dd($SESSIONEASY);
//            if (!isset($SESSIONEASY) && empty($SESSIONEASY)) $this->Login();
//
////        $curl->setCookies($curl->getResponseCookies());
//
//            $__VIEWSTATE = $__VIEWSTATE[0][1];
//            $__VIEWSTATEGENERATOR = $__VIEWSTATEGENERATOR[0][1];
//            $fields = array(
//                '__VIEWSTATEGENERATOR' => $__VIEWSTATEGENERATOR,
//                '__VIEWSTATE' => $__VIEWSTATE,
//                'SESSIONEASY' => $SESSIONEASY,
//                'SELACC_SHOW' => "0"
//            );
//            $this->data = $fields;
////        Storage::put($dataPath, json_encode($fields));
//            file_put_contents($dataPath, json_encode($fields));

//        }


        $curl = new Curl();
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_TIMEOUT, 60);
        $curl->get($this->baseUrl . 'v1.4/site/presignon/index.asp');

//        $curl->setCookies($curl->getResponseCookies());
//
//
        $this->Cookies = $curl->getResponseCookies();
        $path = storage_path('cookies/cookies-' . $UserName . '.txt');
        file_put_contents($path, serialize($this->Cookies));

        $cookies = $this->Cookies;
        $fields = [
            'LOGIN' => $UserName,
            'PASSWD' => $Password,
            'LANG' => "T",
            'lgin.x' => '0',
            'lgin.y' => '0',
        ];

        $responses = rescue(function () use ($fields, $cookies) {

            $url = $this->baseUrl . 'online/easynet/page/lgn/login.aspx';

            return Http::timeout(60)->withCookies($cookies, 'www.scbeasy.com')->withoutVerifying()->asForm()->post($url, $fields);

        }, function ($e) {

            return $e;

        }, true);

        $path = storage_path('logs/scb/debug_' . $this->AccountNumber . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($responses, true));


        if ($responses->successful()) {
            $html = $responses->body();

//                $path = storage_path('logs/scb/debug_' . $this->AccountNumber . '_' . now()->format('Y_m_d') . '.log');
//                file_put_contents($path, print_r($html, true));

            $doms = new simple_html_dom();
            $dom = $doms->load($html);
//        dd($dom->find('input[name=SESSIONEASY]',0)->value);
//        $doms = HtmlDomParser::str_get_html($html);
//        dd($doms->findOne('input[name=SESSIONEASY]'));

            $re__VIEWSTATE = '/id="__VIEWSTATE" value="(.*)"/m';
            preg_match_all($re__VIEWSTATE, $html, $__VIEWSTATE, PREG_SET_ORDER, 0);
            $re__VIEWSTATEGENERATOR = '/id="__VIEWSTATEGENERATOR" value="(.*)"/m';
            preg_match_all($re__VIEWSTATEGENERATOR, $html, $__VIEWSTATEGENERATOR, PREG_SET_ORDER, 0);

            $SESSIONEASY = ($dom->find('input[name=SESSIONEASY]', 0)->value ?? null);
//        $SESSIONEASY = $dom->find('input[name=SESSIONEASY]', 0)->getAttribute('value');
//                $SESSIONEASY = $dom->findOne('input[name=SESSIONEASY]')->getAttribute("value");
//        dd($SESSIONEASY);
            if (!is_null($SESSIONEASY)) {

//        $curl->setCookies($curl->getResponseCookies());

                $__VIEWSTATE = $__VIEWSTATE[0][1];
                $__VIEWSTATEGENERATOR = $__VIEWSTATEGENERATOR[0][1];
                $fields = array(
                    '__VIEWSTATEGENERATOR' => $__VIEWSTATEGENERATOR,
                    '__VIEWSTATE' => $__VIEWSTATE,
                    'SESSIONEASY' => $SESSIONEASY,
                    'SELACC_SHOW' => "0"
                );
                $this->data = $fields;
//        Storage::put($dataPath, json_encode($fields));
                file_put_contents($dataPath, json_encode($fields));
            }else{
                unlink($dataPath);
            }
        }else{
            unlink($cookiesPath);
        }
////        $fh = fopen($cookiesPath, 'w+');
////        fwrite($fh, serialize($this->Cookies));
////        fclose($fh);
////        Storage::put($cookiesPath, serialize($this->Cookies));
//
//
//        $fields = [
//            'LOGIN' => $UserName,
//            'PASSWD' => $Password,
//            'LANG' => "T",
//            'lgin.x' => '0',
//            'lgin.y' => '0',
//        ];
//
////        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, 3);
//        $curl->setOpt(CURLOPT_TIMEOUT, 60);
//        $curl->post($this->baseUrl . 'online/easynet/page/lgn/login.aspx', $fields);

//        $path = storage_path('logs/scb/debug_' . $this->AccountNumber . '_' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r($curl, true) , FILE_APPEND);

//        $html = $curl->response;


//        dd($html);
//        if ($curl->getHttpStatusCode() == 0) $this->Login();

//        $doms = new simple_html_dom();
//        $dom = $doms->load($html);
////        dd($dom->find('input[name=SESSIONEASY]',0)->value);
////        $doms = HtmlDomParser::str_get_html($html);
////        dd($doms->findOne('input[name=SESSIONEASY]'));
//
//        $re__VIEWSTATE = '/id="__VIEWSTATE" value="(.*)"/m';
//        preg_match_all($re__VIEWSTATE, $html, $__VIEWSTATE, PREG_SET_ORDER, 0);
//        $re__VIEWSTATEGENERATOR = '/id="__VIEWSTATEGENERATOR" value="(.*)"/m';
//        preg_match_all($re__VIEWSTATEGENERATOR, $html, $__VIEWSTATEGENERATOR, PREG_SET_ORDER, 0);
//
//        $SESSIONEASY = ($dom->find('input[name=SESSIONEASY]', 0)->value ?? '');
////        $SESSIONEASY = $dom->find('input[name=SESSIONEASY]', 0)->getAttribute('value');
////        $SESSIONEASY =  $dom->findOne('input[name=SESSIONEASY]')->getAttribute("value");
////        dd($SESSIONEASY);
//        if (!isset($SESSIONEASY) && empty($SESSIONEASY)) $this->Login();
//
////        $curl->setCookies($curl->getResponseCookies());
//
//        $__VIEWSTATE = $__VIEWSTATE[0][1];
//        $__VIEWSTATEGENERATOR = $__VIEWSTATEGENERATOR[0][1];
//        $fields = array(
//            '__VIEWSTATEGENERATOR' => $__VIEWSTATEGENERATOR,
//            '__VIEWSTATE' => $__VIEWSTATE,
//            'SESSIONEASY' => $SESSIONEASY,
//            'SELACC_SHOW' => "0"
//        );
//        $this->data = $fields;
////        Storage::put($dataPath, json_encode($fields));
//        file_put_contents($dataPath, json_encode($fields));


    }

    public function getTransection()
    {
        $list = array();
        $param = $this->data;
        $cookies = $this->Cookies;

        $response = rescue(function () use ($param, $cookies) {

            $url = $this->baseUrl . 'online/easynet/page/acc/acc_bnk_tst.aspx';

            return Http::timeout(60)->withCookies($cookies, 'www.scbeasy.com')->withoutVerifying()->asForm()->post($url, $param);

        }, function ($e) {

            return $e;

        }, true);

        $path = storage_path('logs/scb/gettranraw_' . $this->AccountNumber . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($response->body(), true));


        if ($response->successful()) {
            $html = $response->body();

            if (strpos($html, 'maximum session') !== false) {
                $this->Login();
                return $this->getTransection();
            }
            if (strpos($html, 'SCB Maintenance Page') !== false) {
                $this->Login();
                return $this->getTransection();
            }
//        if ($curl->getHttpStatusCode() == 0) return $this->getTransection();

            $theData = array();
            $dom = HtmlDomParser::str_get_html($html);

            $e = $dom->findOne('table#DataProcess_GridView');
//        foreach ($dom->find('table#DataProcess_GridView') as $e) {
            foreach ($e->find('tr') as $row) {

                $rowData = array();
                foreach ($row->find('td') as $cell) {

                    // push the cell's text to the array
                    $rowData[] = $cell->innertext;
                }
                if (isset($rowData[0]) && $rowData[0] != "")
                    $theData[] = $rowData;
            }
//        }

            $path = storage_path('logs/scb/gettran_' . $this->AccountNumber . '_' . now()->format('Y_m_d') . '.log');
            file_put_contents($path, print_r($theData, true));


            array_pop($theData);
//        $size = sizeof($theData);
            $theData = array_reverse($theData);
//        $maxSize = 10;
            rsort($theData);
            // if ($size > $maxSize) $size = $maxSize;
            for ($i = 0; $i < count($theData); $i++) {

                if ($theData[$i][2] == 'X2') {
                    $amout = str_replace(',', '', $theData[$i][4]);
                } else {
                    $amout = str_replace(',', '', $theData[$i][5]);
                }
                $check = substr($amout, 0, 1);
                if ($check == '+') {
                    $list[$i]['type'] = 'in';
                    $list[$i]['amount'] = str_replace('+', '', $amout);
                } else {
                    $list[$i]['type'] = 'out';
                    $list[$i]['amount'] = $amout;
                }

                $list[$i]['way'] = $theData[$i][3];
                $list[$i]['Account'] = $theData[$i][6];
                $list[$i]['date'] = $theData[$i][0] . ' ' . $theData[$i][1] . ':00';
            }


        }

//        $curl = new Curl();
//        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
//        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
//        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
//        $curl->setOpt(CURLOPT_TIMEOUT, 60);
//        $curl->setCookies($this->Cookies);
//        $curl->post($this->baseUrl . 'online/easynet/page/acc/acc_bnk_tst.aspx', http_build_query($this->data));
//        $theData = array();
//        $path = storage_path('logs/scb/debug_' . $this->AccountNumber . '_' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r($curl, true) , FILE_APPEND);

//        $html = $curl->response;

//        $path = storage_path('logs/scb/gettranraw_' . $this->AccountNumber . '_' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r($curl, true));

//        if (is_null($html)) {
//            $this->Login();
//            return $this->getTransection();
//        }
//        if (strpos($html, 'maximum session') !== false) {
//            $this->Login();
//            return $this->getTransection();
//        }
//        if (strpos($html, 'SCB Maintenance Page') !== false) {
//            $this->Login();
//            return $this->getTransection();
//        }
////        if ($curl->getHttpStatusCode() == 0) return $this->getTransection();
//
//
//        $dom = HtmlDomParser::str_get_html($html);
//
//        $e = $dom->findOne('table#DataProcess_GridView');
////        foreach ($dom->find('table#DataProcess_GridView') as $e) {
//        foreach ($e->find('tr') as $row) {
//
//            $rowData = array();
//            foreach ($row->find('td') as $cell) {
//
//                // push the cell's text to the array
//                $rowData[] = $cell->innertext;
//            }
//            if (isset($rowData[0]) && $rowData[0] != "")
//                $theData[] = $rowData;
//        }
////        }
//
//        $path = storage_path('logs/scb/gettran_' . $this->AccountNumber . '_' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r($theData, true));
//
//        $list = array();
//        array_pop($theData);
////        $size = sizeof($theData);
//        $theData = array_reverse($theData);
////        $maxSize = 10;
//        rsort($theData);
//        // if ($size > $maxSize) $size = $maxSize;
//        for ($i = 0; $i < count($theData); $i++) {
//
//            if ($theData[$i][2] == 'X2') {
//                $amout = str_replace(',', '', $theData[$i][4]);
//            } else {
//                $amout = str_replace(',', '', $theData[$i][5]);
//            }
//            $check = substr($amout, 0, 1);
//            if ($check == '+') {
//                $list[$i]['type'] = 'in';
//                $list[$i]['amount'] = str_replace('+', '', $amout);
//            } else {
//                $list[$i]['type'] = 'out';
//                $list[$i]['amount'] = $amout;
//            }
//
//            $list[$i]['way'] = $theData[$i][3];
//            $list[$i]['Account'] = $theData[$i][6];
//            $list[$i]['date'] = $theData[$i][0] . ' ' . $theData[$i][1] . ':00';
//        }
//
        return json_encode($list);
    }

    public function getBalance()
    {
        $balance = 0.00;
        $param = $this->data;
        $cookies = $this->Cookies;

        $response = rescue(function () use ($param, $cookies) {

            $url = $this->baseUrl . 'online/easynet/page/acc/acc_mpg.aspx';

            return Http::timeout(60)->withCookies($cookies, 'www.scbeasy.com')->withoutVerifying()->asForm()->post($url, $param);

        }, function ($e) {

            return $e;

        }, true);

        if ($response->successful()) {
            $html = $response->body();
            $theData = array();

            if (strpos($html, 'maximum session') !== false) {
                $this->Login();
                return $this->getBalance();
            }
            if (strpos($html, 'SCB Maintenance Page') !== false) {
                $this->Login();
                return $this->getBalance();
            }
//        if ($curl->getHttpStatusCode() == 0) return $this->getBalance();


            $dom = HtmlDomParser::str_get_html($html);
            $e = $dom->findOne('table#DataProcess_SaCaGridView');
//        foreach ($dom->find('table#DataProcess_SaCaGridView') as $e) {
            foreach ($e->find('tr') as $row) {

                $rowData = array();
                foreach ($row->find('td') as $cell) {

                    // push the cell's text to the array
                    $rowData[] = $cell->innertext;
                }
                if (isset($rowData[0]) && $rowData[0] != "")
                    $theData[] = $rowData;
            }
//        }

//        $path = storage_path('logs/scb/getbalance_' . $this->AccountNumber . '_' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r($theData, true));

            $theData[3][3] = ($theData[3][3] ?? 0);
            $rawBalance = str_replace('&#13;', '', $theData[3][3]);
            $rawBalance = trim($rawBalance);
            $rawBalance = str_replace(array("\r", "\t", "\n"), "", $rawBalance);
            $balance = str_replace(',', '', $rawBalance);
            $path = storage_path('logs/scb/getbalance_' . $this->AccountNumber . '_' . now()->format('Y_m_d') . '.log');
            file_put_contents($path, print_r($balance, true));
            if (!is_numeric($balance)) {
                $balance = 0.00;
            }

        }

//        $curl = new Curl();
//        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
//        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
//        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
//        $curl->setOpt(CURLOPT_TIMEOUT, 60);
//        $curl->setCookies($this->Cookies);
//        $curl->post($this->baseUrl . 'online/easynet/page/acc/acc_mpg.aspx', http_build_query($this->data));
//        $theData = array();

//        $path = storage_path('logs/scb/debug_' . $this->AccountNumber . '_' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r($curl, true) , FILE_APPEND);

//        $html = $curl->response;

//        $path = storage_path('logs/scb/getbalanceraw_' . $this->AccountNumber . '_' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r($html, true));

//        if (is_bool($html)) {
////            $this->Login();
//            $this->deleteFile();
//            return false;
//        }

//        if (is_null($html)) {
//            $this->Login();
//            return $this->getBalance();
//        }
//        if (strpos($html, 'maximum session') !== false) {
//            $this->Login();
//            return $this->getBalance();
//        }
//        if (strpos($html, 'SCB Maintenance Page') !== false) {
//            $this->Login();
//            return $this->getBalance();
//        }
////        if ($curl->getHttpStatusCode() == 0) return $this->getBalance();
//
//
//        $dom = HtmlDomParser::str_get_html($html);
//        $e = $dom->findOne('table#DataProcess_SaCaGridView');
////        foreach ($dom->find('table#DataProcess_SaCaGridView') as $e) {
//        foreach ($e->find('tr') as $row) {
//
//            $rowData = array();
//            foreach ($row->find('td') as $cell) {
//
//                // push the cell's text to the array
//                $rowData[] = $cell->innertext;
//            }
//            if (isset($rowData[0]) && $rowData[0] != "")
//                $theData[] = $rowData;
//        }
////        }
//
////        $path = storage_path('logs/scb/getbalance_' . $this->AccountNumber . '_' . now()->format('Y_m_d') . '.log');
////        file_put_contents($path, print_r($theData, true));
//
//        $theData[3][3] = ($theData[3][3] ?? 0);
//        $rawBalance = str_replace('&#13;', '', $theData[3][3]);
//        $rawBalance = trim($rawBalance);
//        $rawBalance = str_replace(array("\r", "\t", "\n"), "", $rawBalance);
//        $balance = str_replace(',', '', $rawBalance);
//        $path = storage_path('logs/scb/getbalance_' . $this->AccountNumber . '_' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r($balance, true));
//        if (!is_numeric($balance)) {
//            $balance = 0.00;
//        }

        return $balance;
    }

    private function deleteFile()
    {
        $UserName = $this->UserName;
        $dir = storage_path('cookies');
        $cookiesPath = $dir . "/cookies-" . $UserName . ".txt";
        $dataPath = $dir . "/data-" . $UserName . ".json";

        if (file_exists($cookiesPath)) {
            unlink($cookiesPath);
        }
        if (file_exists($dataPath)) {
            unlink($dataPath);
        }

    }
}
