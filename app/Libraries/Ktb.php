<?php


namespace App\Libraries;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Ktb
{

    public function code($value)
    {
        if ($value == "014") {
            return "ธนาคารไทยพาณิชย์";
        }
        if ($value == "034") {
            return "ธนาคารเพื่อการเกษตรและสหกรณ์การเกษตร";
        }
        if ($value == "025") {
            return "ธนาคารกรุงศรีอยุธยา";
        }
        if ($value == "002") {
            return "ธนาคารกรุงเทพ";
        }
        if ($value == "022") {
            return "ธนาคารซีไอเอ็มบี";
        }
        if ($value == "017") {
            return "ธนาคารซิตี้แบงก์";
        }
        if ($value == "032") {
            return "ธนาคารดอยช์แบงก์";
        }
        if ($value == "033") {
            return "ธนาคารอาคารสงเคราะห์";
        }
        if ($value == "030") {
            return "ธนาคารออมสิน";
        }
        if ($value == "031") {
            return "ธนาคารฮ่องกงและเซี่ยงไฮ้";
        }
        if ($value == "070") {
            return "ธนาคารไอซีบีซี";
        }
        if ($value == "066") {
            return "ธนาคารอิสลามแห่งประเทศไทย";
        }
        if ($value == "004") {
            return "ธนาคารกสิกรไทย";
        }
        if ($value == "069") {
            return "ธนาคารเกียรตินาคิน";
        }
        if ($value == "TR") {
            return "ธนาคารกรุงไทย";
        }
        if ($value == "073") {
            return "ธนาคารแลนด์ แอนด์";
        }
        if ($value == "039") {
            return "ธนาคารมิซูโฮ";
        }
        if ($value == "020") {
            return "ธนาคารสแตนดาร์ดชาร์เตอร์ด";
        }
        if ($value == "018") {
            return "ธนาคารซูมิโตโม";
        }
        if ($value == "065") {
            return "ธนาคารธนชาต";
        }
        if ($value == "071") {
            return "ธนาคารไทยเครดิต";
        }
        if ($value == "011") {
            return "ธนาคารทหารไทย";
        }
        if ($value == "067") {
            return "ธนาคารทิสโก้";
        }
        if ($value == "024") {
            return "ธนาคารยูโอบี";
        }

        return "";
    }

    public function gettransaction($accountTokenNo, $userTokenId)
    {
//https://access.line.me/oauth2/v2.1/authorize?response_type=code&client_id=1640616017&redirect_uri=https://www.krungthaiconnext.ktb.co.th/KTB-Line-Balance/authen?tabName=currentBalance&state=gAqqrlqeay&scope=openid+profile
        error_reporting(0);
//        date_default_timezone_set("Asia/Bangkok");
        $year = date("Y");
        $month = date("m");
        $date_today = date("d");
        $date_end = date("t");
        // $startDate = $year . $month . $date_today;
        // $startDate = date('Ymd');
        $startDate = $year . $month . '01';
        $endDate = $year . $month . $date_end;
        // echo(date('Ymd'));
        // $endDate = date('Ymt', strtotime('today'));

        //$accountTokenNo = "A20211228a8c50021ecc04c89991ab6b57fc948c8";
        //$userTokenId = "9jibDtdJGTpqY0xJPfVFrozL73xfM+2Y9d3/mvzdvLVDVdNhVLKPfNzt3dDMdDv2IIfpRHz+UXtuaCavAQIl4I9gc4ws3IgW4goGZTwIoSVlVzko9OsNdM833BPRUpM93ijsywbAIryrwzLPhgdeYHrtkGdmFYzXz8QjMRAclF5Brb8vlF3YfSyFN2PQbFc1m9Fq5CgrQ8da3Fuh7AocdRE1sa4QnZYdxBgsHLFzbmeTHCyRCGikT+4DpOitLQEf6U3YSBLmYjPpi8D+VbuoyLBYTSWz9cdPlx2p7JeNb0Oka2o6lRfAFDE8sblUnpZkym3sCZtGl/SX3dlLnDC2ThsPKRxHve980h9x8ayRbzaZ2UGbeItfzuPPwLSlBZnFMI/Oah8QdZFNO+7ANO+9EokP6qpphVI1iYSUsd3HdBLIZ0Iqjo39KkzjFyl9j71TJiqnFCJq8tt0Yoy0Vr2+lGySIafJ0juhGh/sHH/kSVfV1YWeFp02O7138kSzHlL/rbp44DgPYjoSExDbXpZMQLSi3CABQVe+zwghQ5dcWkSGzefN3m0zfs1vEI4lcC4VNbzHvlDm6+/RH5W+Zqcs1PBIZUc4MFsuaeZYVGX643Unv4RB69CwYAVSDEOZHjqpHcpeSbozsZEos76tq8rVUe2m0xasvAYoz1fhNbL8aLw+wd5Xpxld2QGvkAh6NnB0hGaMth9CpIXSHb9HnZeKIApY3cFtL4pkYOEH35b0S1VVs8BEUuajSJkJdHHP3lyscPDn99tkbHHg+z4HrDcNi6IDfYx6toBx0HUz28bnnF/v460SBUb3dcwTBXxpkzf/zU2lp1K5I5edVkBxobQ8Sz/otfxAApWHm6XGwVGYDmIuR3Em0I/pIEBAKKQkhx2QWl9bALfQ0KUcptlGKbtndw==";



        $param = "{\"userTokenId\":\"" . $userTokenId . "\",\"accountTokenNo\":\"" . $accountTokenNo . "\",\"startDate\":\"" . $startDate . "\",\"endDate\":\"" . $endDate . "\",\"uid\":0}";

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://www.krungthaiconnext.ktb.co.th/KTB-Line-Balance/getTransactionHistory",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $param,
            CURLOPT_HTTPHEADER => array(
                "Connection:  keep-alive",
                "Cache-Control: no-cache",
                "Content-Length: 1283",
                "Pragma: no-cache",
                "Accept:  */*",
                "Accept-Encoding: gzip, deflate, br",
                "lineToken: " . $userTokenId,
                "X-Requested-With:  XMLHttpRequest",
                "tokenID: [object HTMLInputElement]",
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36",
                "Content-Type:  application/json",
                "Host: www.krungthaiconnext.ktb.co.th",
                "Origin: https://www.krungthaiconnext.ktb.co.th",
                'sec-ch-ua: "Chromium";v="92", " Not A;Brand";v="99", "Google Chrome";v="92"',
                "sec-ch-ua-mobile: ?0",
                "Sec-Fetch-Site:  same-origin",
                "Sec-Fetch-Mode:  cors",
                "Sec-Fetch-Dest:  empty",
                "Referer:  https://www.krungthaiconnext.ktb.co.th/KTB-Line-Balance/depositDetail",
                "Accept-Language: th-TH,th;q=0.9"
            ),
        ));

        $response = curl_exec($curl);

//        dd($response);

        curl_close($curl);
        $data = json_decode($response, true);

//        dd($data);

//        dd($data['data'][0]['endingBalance']);
        // echo"<pre>";
        // print_r($data);
        // echo"</pre>";
        // exit();
        $json = [];
        $master = [];
        $total_balance = [];
        $balance = $data['data'][0]['endingBalance'];
        if ($balance == "") {
            $tr["balance"] = 0;
            $tr["data"] = array();
            return json_encode($tr);

        }

        $tr["balance"] = $balance;
        $tr["data"] = array();

        $i = 0;
        foreach ($data['data'] as $value) {
            $transAmt = $value['transAmt']; //ยอดฝาก
            $transDate = $value['transDate']; //วันที
            $transTime = $value['transTime']; //เวลา
            $transCmt = $value['transCmt']; //เลขบันชี
            $transRefId = $value['transRefId']; //อ้างอิง
            $transCodeDescTh = $value['transCodeDescTh']; //โอนออก
            preg_match_all('/.(.*?)(?=-)|TR/', $transCmt, $output_array);
            $bank_name = $this->code($output_array[0][0]);
            if ($transCodeDescTh == "โอนเงินออก") {
                preg_match_all('/(?<= )\d{10}/', $transCmt, $output_array);
                $bank_number = $output_array[0][0];
            } else {
                preg_match_all('/(?<=\-).+|(?<=fr )\d{10}/', $transCmt, $output_array);
                $bank_number = $output_array[0][0];
            }
            $master_date = date("Y-m-d", strtotime($transDate));
            // echo('<br>'.$master_date.'<br>');
            if ($i < 51) {
                $tr["data"][$i]["transAmt"] = $transAmt;
                $tr["data"][$i]["transDate"] = $master_date . ' ' . $transTime;
                $tr["data"][$i]["transTime"] = $transTime;
                $tr["data"][$i]["transCmt"] = $bank_number;
                $tr["data"][$i]["bank_name"] = $bank_name;
                $tr["data"][$i]["transRefId"] = $transRefId . '::' . $transCodeDescTh . ' จาก ' . $bank_name;
                /*
            array_push($json, array(
            // "no" => $i,
            "transAmt" => $transAmt,
            "transDate" => $master_date . ' ' . $transTime,
            "transTime" => $transTime,
            "transCmt" => $bank_number,
            "bank_name" => $bank_name,
            "transRefId" => $transRefId,
            "transCodeDescTh" => $transCodeDescTh,
            ));
             */
            }
            $i++;
        }

        array_push($master, array(
            "balance" => $total_balance,
            "transaction" => $json,
        ));

        return json_encode($tr);
    }

    public function gettransactiontest($accountTokenNo, $userTokenId)
    {
//https://access.line.me/oauth2/v2.1/authorize?response_type=code&client_id=1640616017&redirect_uri=https://www.krungthaiconnext.ktb.co.th/KTB-Line-Balance/authen?tabName=currentBalance&state=gAqqrlqeay&scope=openid+profile
        error_reporting(0);
//        date_default_timezone_set("Asia/Bangkok");
        $year = date("Y");
        $month = date("m");
        $date_today = date("d");
        $date_end = date("t");
        // $startDate = $year . $month . $date_today;
        // $startDate = date('Ymd');
        $startDate = $year . $month . '01';
        $endDate = $year . $month . $date_end;
        // echo(date('Ymd'));
        // $endDate = date('Ymt', strtotime('today'));

        //$accountTokenNo = "A20211228a8c50021ecc04c89991ab6b57fc948c8";
        //$userTokenId = "9jibDtdJGTpqY0xJPfVFrozL73xfM+2Y9d3/mvzdvLVDVdNhVLKPfNzt3dDMdDv2IIfpRHz+UXtuaCavAQIl4I9gc4ws3IgW4goGZTwIoSVlVzko9OsNdM833BPRUpM93ijsywbAIryrwzLPhgdeYHrtkGdmFYzXz8QjMRAclF5Brb8vlF3YfSyFN2PQbFc1m9Fq5CgrQ8da3Fuh7AocdRE1sa4QnZYdxBgsHLFzbmeTHCyRCGikT+4DpOitLQEf6U3YSBLmYjPpi8D+VbuoyLBYTSWz9cdPlx2p7JeNb0Oka2o6lRfAFDE8sblUnpZkym3sCZtGl/SX3dlLnDC2ThsPKRxHve980h9x8ayRbzaZ2UGbeItfzuPPwLSlBZnFMI/Oah8QdZFNO+7ANO+9EokP6qpphVI1iYSUsd3HdBLIZ0Iqjo39KkzjFyl9j71TJiqnFCJq8tt0Yoy0Vr2+lGySIafJ0juhGh/sHH/kSVfV1YWeFp02O7138kSzHlL/rbp44DgPYjoSExDbXpZMQLSi3CABQVe+zwghQ5dcWkSGzefN3m0zfs1vEI4lcC4VNbzHvlDm6+/RH5W+Zqcs1PBIZUc4MFsuaeZYVGX643Unv4RB69CwYAVSDEOZHjqpHcpeSbozsZEos76tq8rVUe2m0xasvAYoz1fhNbL8aLw+wd5Xpxld2QGvkAh6NnB0hGaMth9CpIXSHb9HnZeKIApY3cFtL4pkYOEH35b0S1VVs8BEUuajSJkJdHHP3lyscPDn99tkbHHg+z4HrDcNi6IDfYx6toBx0HUz28bnnF/v460SBUb3dcwTBXxpkzf/zU2lp1K5I5edVkBxobQ8Sz/otfxAApWHm6XGwVGYDmIuR3Em0I/pIEBAKKQkhx2QWl9bALfQ0KUcptlGKbtndw==";



        $param = "{\"userTokenId\":\"" . $userTokenId . "\",\"accountTokenNo\":\"" . $accountTokenNo . "\",\"startDate\":\"" . $startDate . "\",\"endDate\":\"" . $endDate . "\",\"uid\":0}";

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://www.krungthaiconnext.ktb.co.th/KTB-Line-Balance/getTransactionHistory",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $param,
            CURLOPT_HTTPHEADER => array(
                "Connection:  keep-alive",
                "Cache-Control: no-cache",
                "Content-Length: 1027",
                "Pragma: no-cache",
                "Accept:  */*",
                "Accept-Encoding: gzip, deflate, br",
                "lineToken: " . $userTokenId,
                "X-Requested-With:  XMLHttpRequest",
                "tokenID: [object HTMLInputElement]",
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36",
                "Content-Type:  application/json",
                "Host: www.krungthaiconnext.ktb.co.th",
                "Origin: https://www.krungthaiconnext.ktb.co.th",
                'sec-ch-ua: "Chromium";v="92", " Not A;Brand";v="99", "Google Chrome";v="92"',
                "sec-ch-ua-mobile: ?0",
                "Sec-Fetch-Site:  same-origin",
                "Sec-Fetch-Mode:  cors",
                "Sec-Fetch-Dest:  empty",
                "Referer:  https://www.krungthaiconnext.ktb.co.th/KTB-Line-Balance/deposit/account-detail",
                "Accept-Language: th-TH,th;q=0.9"
            ),
        ));

        $response = curl_exec($curl);

        dd($response);

        curl_close($curl);
        $data = json_decode($response, true);

//        dd($data);

//        dd($data['data'][0]['endingBalance']);
        // echo"<pre>";
        // print_r($data);
        // echo"</pre>";
        // exit();
        $json = [];
        $master = [];
        $total_balance = [];
        $balance = $data['data'][0]['endingBalance'];
        if ($balance == "") {
            $tr["balance"] = 0;
            $tr["data"] = array();
            return json_encode($tr);

        }

        $tr["balance"] = $balance;
        $tr["data"] = array();

        $i = 0;
        foreach ($data['data'] as $value) {
            $transAmt = $value['transAmt']; //ยอดฝาก
            $transDate = $value['transDate']; //วันที
            $transTime = $value['transTime']; //เวลา
            $transCmt = $value['transCmt']; //เลขบันชี
            $transRefId = $value['transRefId']; //อ้างอิง
            $transCodeDescTh = $value['transCodeDescTh']; //โอนออก
            preg_match_all('/.(.*?)(?=-)|TR/', $transCmt, $output_array);
            $bank_name = $this->code($output_array[0][0]);
            if ($transCodeDescTh == "โอนเงินออก") {
                preg_match_all('/(?<= )\d{10}/', $transCmt, $output_array);
                $bank_number = $output_array[0][0];
            } else {
                preg_match_all('/(?<=\-).+|(?<=fr )\d{10}/', $transCmt, $output_array);
                $bank_number = $output_array[0][0];
            }
            $master_date = date("Y-m-d", strtotime($transDate));
            // echo('<br>'.$master_date.'<br>');
            if ($i < 51) {
                $tr["data"][$i]["transAmt"] = $transAmt;
                $tr["data"][$i]["transDate"] = $master_date . ' ' . $transTime;
                $tr["data"][$i]["transTime"] = $transTime;
                $tr["data"][$i]["transCmt"] = $bank_number;
                $tr["data"][$i]["bank_name"] = $bank_name;
                $tr["data"][$i]["transRefId"] = $transRefId . '::' . $transCodeDescTh . ' จาก ' . $bank_name;
                /*
            array_push($json, array(
            // "no" => $i,
            "transAmt" => $transAmt,
            "transDate" => $master_date . ' ' . $transTime,
            "transTime" => $transTime,
            "transCmt" => $bank_number,
            "bank_name" => $bank_name,
            "transRefId" => $transRefId,
            "transCodeDescTh" => $transCodeDescTh,
            ));
             */
            }
            $i++;
        }

        array_push($master, array(
            "balance" => $total_balance,
            "transaction" => $json,
        ));

        return json_encode($tr);
    }


}
