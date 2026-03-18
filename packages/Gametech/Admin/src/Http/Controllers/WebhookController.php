<?php

namespace Gametech\Admin\Http\Controllers;

use App\Events\RealTimeMessage;
use DateTime;
use Exception;
use Gametech\Member\Models\Member;
use Gametech\Payment\Models\BankPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookController extends AppBaseController
{
    protected $_config;

    public function __construct()
    {
        $this->_config = request('_config');

        //        $this->middleware('api');
    }

    public function index($mobile, Request $request)
    {
        $datenow = now()->toDateTimeString();

        $path = storage_path('logs/tw/webhooks_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, $datenow . PHP_EOL, FILE_APPEND);
        file_put_contents($path, print_r($request->all(), true) . PHP_EOL, FILE_APPEND);

        return response()->json([
            'success'   => true,
            'code'      => 200,
            'message'   => 'Success.',
        ], 200, [
            'Content-Type' => 'application/json',
        ]);

        // ไม่มี message → เงียบ ๆ กลับ 1
        if (!$request->has('message')) {
            return response()->json([
                'success'   => true,
                'code'      => 200,
                'message'   => 'Success.',
            ], 200, [
                'Content-Type' => 'application/json',
            ]);
        }

        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('tw', $mobile);
        if (!$data) {
            return response()->json([
                'success'   => true,
                'code'      => 200,
                'message'   => 'Success.',
            ], 200, [
                'Content-Type' => 'application/json',
            ]);
        }

        if ($data->webhook != 'Y') {
            return response()->json([
                'success'   => true,
                'code'      => 200,
                'message'   => 'Success.',
            ], 200, [
                'Content-Type' => 'application/json',
            ]);
        }

        $data->checktime = $datenow;
        $data->save();

        $message = $request->input('message');

        // ดึงฟิลด์ที่ต้องใช้ (จะได้ null หาก payload เป็น handshake/ไม่ครบ)
        $received_time = $this->getJwtPayloadField($message, 'received_time');
        $sender_mobile = $this->getJwtPayloadField($message, 'sender_mobile');
        $amountRaw     = $this->getJwtPayloadField($message, 'amount');       // หน่วยสตางค์จากผู้ให้บริการ (เช่น 15000 = 150.00)
        $event_type    = $this->getJwtPayloadField($message, 'event_type');

        // ถ้า payload เป็น handshake / หรือขาดฟิลด์สำคัญ → ไม่ทำอะไรต่อ
        if (is_null($received_time) || is_null($sender_mobile) || is_null($amountRaw) || is_null($event_type)) {
            // (เงียบ ๆ ตามข้อ 1) ถ้าต้องการ log เพิ่มเติม สามารถใส่ไว้ที่นี่ได้
            return response()->json([
                'success'   => true,
                'code'      => 200,
                'message'   => 'Success.',
            ], 200, [
                'Content-Type' => 'application/json',
            ]);
        }

        // แปลงจำนวนเงินจากสตางค์ → บาท (กัน type ก่อน)
        $amount = is_numeric($amountRaw) ? ((float)$amountRaw / 100) : null;
        if ($amount === null) {
            return response()->json([
                'success'   => true,
                'code'      => 200,
                'message'   => 'Success.',
            ], 200, [
                'Content-Type' => 'application/json',
            ]);
        }

        // Normalize เวลา: 2025-10-30T12:31:10+0700 → 2025-10-30 12:31:10
        $date = (string) $received_time;
        $date = Str::replace('T', ' ', $date);
        $date = Str::replace('+0700', '', $date);

        // สร้าง hash ป้องกันซ้ำ
        $hash = md5($data->code . $date . $amount . $sender_mobile);

        $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
        $newpayment->account_code = $data->code;
        $newpayment->bank         = 'twl_' . $mobile;
        $newpayment->bankstatus   = 1;
        $newpayment->bankname     = 'TW';
        $newpayment->report_id    = '';
        $newpayment->bank_time    = $date;
        $newpayment->type         = $event_type;
        $newpayment->title        = 'Webhook';
        $newpayment->channel      = 'WEBHOOK';
        $newpayment->value        = $amount;
        $newpayment->tx_hash      = $hash;
        $newpayment->detail       = $sender_mobile;
        $newpayment->atranferer   = $sender_mobile;
        $newpayment->time         = $date;
        $newpayment->create_by    = 'SYSAUTO';
        $newpayment->save();

        // คงพฤติกรรมเดิม (ไม่ต้องคืน JSON)
        return response()->json([
            'success'   => true,
            'code'      => 200,
            'message'   => 'Success.',
        ], 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function getJwtPayloadField($jwt, $field)
    {
        // ป้องกัน error: ถ้ารูปแบบไม่ใช่ header.payload.signature → คืน null
        $parts = explode('.', (string) $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        // base64url decode (เติม padding เผื่อขาด)
        $payload = $parts[1];
        $payload = strtr($payload, '-_', '+/');
        $padLen  = (4 - (strlen($payload) % 4)) % 4;
        if ($padLen > 0) {
            $payload .= str_repeat('=', $padLen);
        }

        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            return null;
        }

        $data = json_decode($decoded, true);
        if (!is_array($data)) {
            return null;
        }

        return $data[$field] ?? null;
    }
    public function tw(Request $request)
    {
        return true;
    }

    public function texttest(Request $request)
    {
        $datenow = now()->toDateTimeString();
        $mobile = 'test';
        $title = 'มีเงิน3.00บ.โอนเข้าบ/ชxx5985 จาก SCB X6093 นาย ภิรายุทธ ขว เหลือ625.00บ.12/01/24@16:36';

        $amount = Str::of($title)->between('มีเงิน', 'บ.โอนเข้า')->__toString();
        $content = Str::of($title)->between('จาก', 'เหลือ');
        $content = trim($content);

        $content = Str::of($content)->explode(' ');

        $messages['amount'] = $amount;
        $messages['bank'] = $content['0'];
        $messages['acc'] = $content['1'];
        $messages['name'] = $content['3'];
        //        $messages['current_date'] = $message['current_date'];
        //        $messages['current_time'] = $message['current_time'];

        dd($messages);

        $message = $request->all();

        $path = storage_path('logs/ttb/webhook_'.$mobile.'_'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r($message, true), FILE_APPEND);

        $title = $message['title'];
        $titles = stripos($title, 'โอนเข้า');
        if ($titles !== false) {

            $amount = Str::of($title)->between('มีเงิน', 'บ.');
            $content = Str::of($title)->between('จาก', 'เหลือ');
            $content = trim($content);

            $content = Str::of($content)->explode(' ');

            $messages['amount'] = $amount;
            $messages['bank'] = $content['0'];
            $messages['acc'] = $content['1'];
            $messages['name'] = $content['3'];
            $messages['current_date'] = $message['current_date'];
            $messages['current_time'] = $message['current_time'];

            file_put_contents($path, print_r($messages, true), FILE_APPEND);

            $date = str_replace('/', '-', $messages['current_date']);

            $time = $messages['current_time'];
            $datetime = $date.' '.$time;

            $hash = md5($data->code.$datetime.$amount.$messages['bank'].$messages['acc']);

            $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
            $newpayment->account_code = $data->code;
            $newpayment->bank = 'ttb_'.$mobile;
            $newpayment->bankstatus = 1;
            $newpayment->bankname = 'TTB';
            $newpayment->report_id = $messages['bank'];
            $newpayment->bank_time = $date;
            $newpayment->type = '';
            $newpayment->title = $messages['name'];
            $newpayment->channel = 'WEBHOOK';
            $newpayment->value = $amount;
            $newpayment->tx_hash = $hash;
            $newpayment->detail = $messages['bank'].' '.$messages['acc'];
            $newpayment->atranferer = str_replace('X', '', $messages['bank']);
            $newpayment->time = $date;
            $newpayment->create_by = 'SYSAUTO';
            $newpayment->save();

        }

        //        $amount = $messages['amount'] / 100;
        //        $date = Str::replace('T',' ',$messages['received_time']);
        //        $date = Str::replace('+0700','',$date);
        //
        //        $hash = md5($data->code . $date . $amount . $messages['sender_mobile']);
        //
        //        $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
        //        $newpayment->account_code = $data->code;
        //        $newpayment->bank = 'twl_' . $mobile;
        //        $newpayment->bankstatus = 1;
        //        $newpayment->bankname = 'TW';
        //        $newpayment->report_id = '';
        //        $newpayment->bank_time = $date;
        //        $newpayment->type = $messages['event_type'];
        //        $newpayment->title = 'Webhook';
        //        $newpayment->channel = 'WEBHOOK';
        //        $newpayment->value = $amount;
        //        $newpayment->tx_hash = $hash;
        //        $newpayment->detail = $messages['sender_mobile'];
        //        $newpayment->atranferer = $messages['sender_mobile'];
        //        $newpayment->time = $date;
        //        $newpayment->create_by = 'SYSAUTO';
        //        $newpayment->save();

    }

    public function ttb($mobile, Request $request)
    {
        $date = now()->toDateString();
        $datenow = now()->toDateTimeString();
        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('ttb', $mobile);
        if (! $data) {
            return 1;
        }

        $data->checktime = $datenow;
        $data->save();

        $messages = [];
        $message = $request->all();

        $path = storage_path('logs/ttb/webhook_'.$mobile.'_'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r($message, true), FILE_APPEND);

        //        $path = storage_path('ttb/webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        //        file_put_contents($path, print_r($message, true), FILE_APPEND);

        $title = $message['content'];
        $titles = stripos($title, 'โอนเข้า');
        if ($titles !== false) {

            $amount = Str::of($title)->between('มีเงิน', 'บ.โอนเข้า')->__toString();
            $content = Str::of($title)->between('จาก', 'เหลือ');
            $content = trim($content);
            $contents = stripos($content, 'GSB');
            if ($contents !== false) {
                $content = str_replace('น.ส.', 'น.ส. ', $content);
            }

            $content = Str::of($content)->explode(' ');
            $amount = str_replace(',', '', $amount);
            $messages['amount'] = number_format((float) $amount, 2, '.', '');
            $messages['bank'] = $content['0'];
            $messages['acc'] = $content['1'];
            $messages['name'] = $content['3'];
            $messages['current_date'] = $date;
            //            $messages['current_time'] = $message['current_time'];

            file_put_contents($path, print_r($messages, true), FILE_APPEND);

            //            $date = str_replace("/", "-", $messages['current_date']);

            $time = Str::of($title)->explode('@');
            $datetime = $date.' '.$time[1].':00';

            $hash = md5($data->code.$datetime.$amount.$messages['bank'].$messages['acc']);

            $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
            $newpayment->account_code = $data->code;
            $newpayment->bank = 'ttb_'.$mobile;
            $newpayment->bankstatus = 1;
            $newpayment->bankname = 'TTB';
            $newpayment->report_id = $messages['bank'];
            $newpayment->bank_time = $datetime;
            $newpayment->type = '';
            $newpayment->title = $messages['name'];
            $newpayment->channel = 'WEBHOOK';
            $newpayment->value = $amount;
            $newpayment->tx_hash = $hash;
            $newpayment->detail = $messages['bank'].' '.$messages['acc'].' '.$messages['name'];
            $newpayment->atranferer = str_replace('X', '', $messages['acc']);
            $newpayment->time = $datetime;
            $newpayment->create_by = 'SYSAUTO';
            $newpayment->save();

        }

    }

    public function aba($mobile, Request $request)
    {
        $datenow = now()->toDateTimeString();
        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('aba', $mobile);
        if (! $data) {
            return 1;
        }

        $data->checktime = $datenow;
        $data->save();

        $messages = [];
        $message = $request->all();

        $message['datetime'] = $datenow;
        $path = storage_path('logs/aba/webhook_'.$mobile.'_'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r($message, true), FILE_APPEND);

        $input = $message['message'];

        if (preg_match('/\$(\d+\.\d{2})/', $input, $matches)) {
            // เราจะได้ "10.00" จากนั้นแปลงเป็นตัวเลข
            $amounts = floatval($matches[1]);
        } else {
            $message['status'] = 'No Amount USD';
            file_put_contents($path, print_r($message, true), FILE_APPEND);

            return 1;
        }

        if (preg_match('/paid by\s+([A-Z\s]+)/i', $input, $matches)) {
            $title = trim($matches[1]);
            $titles = Str::of($title)->explode(' ');
            $titles = $titles[1].' '.$titles[0];
        } else {
            $message['status'] = 'No Amount Name';
            file_put_contents($path, print_r($message, true), FILE_APPEND);

            return 1;
        }

        if (preg_match('/\(\*(\d+)\)/', $input, $matches)) {
            $acc = $matches[1];
        } else {
            $message['status'] = 'No Amount Account';
            file_put_contents($path, print_r($message, true), FILE_APPEND);

            return 1;
        }

        if (preg_match('/on\s+([A-Za-z]{3}\s+\d{1,2},\s+\d{1,2}:\d{2}\s+[AP]M)/', $input, $matches)) {
            $dateStr = trim($matches[1]);
        } else {
            $message['status'] = 'No Amount Date';
            file_put_contents($path, print_r($message, true), FILE_APPEND);

            return 1;

        }
        $date = null;
        if ($dateStr) {
            // สร้าง DateTime object จากรูปแบบที่รู้จัก
            $dateObj = DateTime::createFromFormat('M d, h:i A', $dateStr);
            if ($dateObj) {
                // กำหนดปีให้เป็น 2025
                $dateObj->setDate(2025, $dateObj->format('n'), $dateObj->format('j'));
                // แปลงเป็นรูปแบบที่ต้องการ
                $date = $dateObj->format('Y-m-d H:i:s');
            }
        }

        if (preg_match('/Trx\. ID:\s*(\d+)/', $input, $matches)) {
            $refid = $matches[1];
        } else {
            $message['status'] = 'No Amount Ref';
            file_put_contents($path, print_r($message, true), FILE_APPEND);

            return 1;
        }

        //        $message = Str::of($message)->explode(',');
        //        for($i=0; $i < count($message); $i++){
        //            $key_value = explode(':', $message[$i]);
        //            if($key_value[0] == 'received_time'){
        //                $messages[$key_value[0]] = $key_value[1].":".$key_value[2].":".$key_value[3];
        //            }else{
        //                $messages[$key_value[0]] = $key_value[1];
        //            }
        //
        //        }
        //
        //
        //
        $amount = $amounts * 10;
        //        $date = Str::replace('T',' ',$messages['received_time']);
        //        $date = Str::replace('+0700','',$date);
        //
        $hash = md5($data->code.$date.$amounts.$acc);

        $member = Member::where('bank_code', 20)->whereRaw('RIGHT(acc_no, 3) = ?', [$acc])->where('name', $title)->orWhere('name', $titles)->first();
        if (! $member) {
            $member_user = '';
        } else {
            $member_user = ' ID : '.$member->user_name;
        }

        $newpayment = BankPayment::firstOrCreate(
            ['tx_hash' => $hash, 'account_code' => $data->code],
            [
                'bank' => 'aba_'.$mobile,
                'bankname' => 'ABA',
                'bankstatus' => 1,
                'report_id' => $refid,
                'bank_time' => $date,
                'type' => '',
                'title' => 'Webhook',
                'channel' => 'WEBHOOK',
                'value' => $amount,
                'atranferer' => $acc,
                'time' => $date,
                'create_by' => 'SYSAUTO',
                'detail' => 'Ref : '.$refid.' '.$title.' ฝาก '.$amounts.$member_user,
            ]
        );

        if ($newpayment) {
            $message['complete'] = 'Insert Complete';
        } else {
            $message['complete'] = 'Insert Fail';
        }

        file_put_contents($path, print_r($message, true), FILE_APPEND);
        if ($member) {
            broadcast(new RealTimeMessage('มีรายการฝากใหม่ รอเติม ('.$amount.') จาก '.$member->user_name));
        }

        return 1;

    }

    public function wing($mobile, Request $request)
    {
        $datenow = now()->toDateTimeString();
        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('wing', $mobile);
        if (! $data) {
            return 1;
        }

        $data->checktime = $datenow;
        $data->save();

        $messages = [];
        $message = $request->all();

        $message['datetime'] = $datenow;
        $path = storage_path('logs/wing/webhook_'.$mobile.'_'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r($message, true), FILE_APPEND);

        if ($message['type'] != 'noti') {
            return 1;
        }

        $title = $message['address'];
        $title = str_replace('ទទួលប្រាក់ពី ', '', $title);

        $content = $message['body'];
        $content = str_replace('ចំនួនទឹកប្រាក់: ', '', $content);
        $content = str_replace('ទៅគណនី: ', '|', $content);
        $content = str_replace('ពីគណនី: ', '|', $content);
        $content = str_replace('គោលបំណង: ', '|', $content);
        $content = Str::of($content)->explode('|');
        $amount = $content[0];
        $total = $amount;
        $amounts = stripos($amount, '-');
        if ($amounts !== false) {
            return true;
        }
        $amounts = stripos($amount, '$');
        if ($amounts !== false) {
            $amount = str_replace('$', '', $amount);
            $amount = (float) $amount * 10;
        }
        $amounts = stripos($amount, '៛');
        if ($amounts !== false) {
            return true;
        }
        if (! isset($content[2])) {
            return true;
        }

        $fromaccount = trim($content[2]);

        if (isset($content[3])) {
            $refid = trim($content[3]);
        } else {
            $refid = '';
        }

        $hash = md5($data->code.$message['date'].$amount.$fromaccount);

        $member = Member::where('bank_code', 24)->where('acc_no', $fromaccount)->first();
        if (! $member) {
            $member_user = '';
        } else {
            $member_user = ' ID : '.$member->user_name;
        }
        $message['status'] = 'Insert DB';
        file_put_contents($path, print_r($message, true), FILE_APPEND);

        $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
        $newpayment->account_code = $data->code;
        $newpayment->bank = 'wing_'.$mobile;
        $newpayment->bankstatus = 1;
        $newpayment->bankname = 'WING';
        $newpayment->report_id = '';
        $newpayment->bank_time = $datenow;
        $newpayment->type = '';
        $newpayment->title = 'Webhook';
        $newpayment->channel = 'WEBHOOK';
        $newpayment->value = $amount;
        $newpayment->tx_hash = $hash;
        $newpayment->detail = 'Ref : '.$refid.' '.$title.' ฝาก '.$total.' จากบัญชี : '.$fromaccount.$member_user;
        $newpayment->atranferer = $fromaccount;
        $newpayment->time = $datenow;
        $newpayment->create_by = 'SYSAUTO';
        $newpayment->save();

        if ($member) {
            broadcast(new RealTimeMessage('มีรายการฝากใหม่ รอเติม ('.$total.') จาก '.$member->user_name));
        }
        //        if ($titles !== false) {
        //
        //            $content = $message['content'];
        //
        //            $content = str_replace("To Account", "|to_account", $content);
        //            $content = str_replace("From Account", "|from_account", $content);
        //
        //
        //            $content = Str::of($content)->explode('|');
        //            for ($i = 0; $i < count($content); $i++) {
        //                $key_value = explode(':', $content[$i]);
        //                if ($key_value[0] == 'received_time') {
        //                    $messages[$key_value[0]] = $key_value[1] . ":" . $key_value[2] . ":" . $key_value[3];
        //                } else {
        //                    $messages[$key_value[0]] = $key_value[1];
        //                }
        //
        //            }
        //
        //            $messages['current_date'] = $message['current_date'];
        //            $messages['current_time'] = $message['current_time'];
        //
        //            file_put_contents($path, print_r($messages, true), FILE_APPEND);
        //
        //            $amount = $messages['Amount'];
        //            $total = $amount;
        //
        //            $amounts = stripos($amount, '$');
        //            if ($amounts !== false) {
        //                $amount = str_replace("$", "", $amount);
        //                $amount = (float)$amount * 10;
        //            }
        //
        //            $amounts = stripos($amount, '៛');
        //            if ($amounts !== false) {
        //                $amount = str_replace("៛", "", $amount);
        //                $amount = str_replace(",", "", $amount);
        //                $amount = (float)$amount / 100;
        //                return true;
        //            }
        //
        //
        //            $date = str_replace("/", "-", $messages['current_date']);
        //
        //            $time = $messages['current_time'];
        //            $datetime = $date . ' ' . $time;
        //
        //            $hash = md5($data->code . $datenow . $amount . $messages['from_account']);
        //
        //            $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
        //            $newpayment->account_code = $data->code;
        //            $newpayment->bank = 'wing_' . $mobile;
        //            $newpayment->bankstatus = 1;
        //            $newpayment->bankname = 'WING';
        //            $newpayment->report_id = '';
        //            $newpayment->bank_time = $datetime;
        //            $newpayment->type = '';
        //            $newpayment->title = 'Webhook';
        //            $newpayment->channel = 'WEBHOOK';
        //            $newpayment->value = $amount;
        //            $newpayment->tx_hash = $hash;
        //            $newpayment->detail = $total . ' From :' . $messages['from_account'];
        //            $newpayment->atranferer = $messages['from_account'];
        //            $newpayment->time = $date;
        //            $newpayment->create_by = 'SYSAUTO';
        //            $newpayment->save();
        //        }
        //        file_put_contents($path, print_r($newpayment, true), FILE_APPEND);

    }

    public function acleda($mobile, Request $request)
    {
        $datenow = now()->toDateTimeString();
        $usebank = '';
        $khqr = ' ';
        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('acleda', $mobile);
        if (! $data) {
            return 1;
        }

        $data->checktime = $datenow;
        $data->save();

        $messages = [];
        $message = $request->all();

        $message['datetime'] = $datenow;
        $path = storage_path('logs/acleda/webhook_'.$mobile.'_'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r($message, true), FILE_APPEND);

        if ($message['type'] != 'noti') {
            return 1;
        }

        if (! Str::contains($message['address'], 'ACLEDA mobile')) {
            $message['status'] = 'Address Wrong';
            file_put_contents($path, print_r($message, true), FILE_APPEND);

            return 1;
        }

        if (Str::contains($message['body'], 'received from')) {
            if (Str::contains($message['body'], '៛')) {
                $message['status'] = 'Currency Not $';
                file_put_contents($path, print_r($message, true), FILE_APPEND);

                return 1;
            }
            $message['status'] = 'Case Normal Insert DB';
            $content = $message['body'];
            $money = Str::of($content)->before('$')->__toString();
            $amounts = trim($money);
            $content = Str::of($content)->after('received from ');
            $content = str_replace('into your account', '|', $content);
            $content = str_replace('. Ref:', '|', $content);
            $content = Str::of($content)->explode('|');
            //           $acc = Str::of($content)->substr(0,12);
            //           $content = Str::of($content)->after($acc);
            //           $content = str_replace("Ref.ID", "|", $content);
            //           $content = Str::of($content)->explode('|');

            $title = trim($content[0]);
            $titles = Str::of($title)->explode(' ');
            $titles = $titles[1].' '.$titles[0];
            $acc = trim($content[1]);
            $refid = trim($content[2]);

            if (! $amounts) {
                $message['complete'] = 'No Amount';
                file_put_contents($path, print_r($message, true), FILE_APPEND);

                return 1;
            }

            $usebank = 'acleda';

        } elseif ($message['address'] == 'KHQR Payment ACLEDA mobile') {
            $message['status'] = 'Case Qr Insert DB';
            $input = $message['body'];
            $acc = '';
            if (preg_match('/Received\s+([\d]+\.[\d]{2})\s+USD/', $input, $matches)) {
                $amounts = floatval($matches[1]);
            } else {
                $message['complete'] = 'No Amount USD';
                file_put_contents($path, print_r($message, true), FILE_APPEND);

                return 1;
            }

            if (preg_match('/from\s+([^,]+)/', $input, $matches)) {
                $title = trim($matches[1]);
                $titles = Str::of($title)->explode(' ');
                $titles = $titles[1].' '.$titles[0];
            } else {
                $message['complete'] = 'No Name';
                file_put_contents($path, print_r($message, true), FILE_APPEND);

                return 1;
            }

            if (preg_match('/,([^,]+),on/', $input, $matches)) {
                $khqr = ' ('.trim($matches[1]).') ';

            }

            //            if (preg_match('/on\s+([\d]{1,2}-[A-Za-z]{3}-[\d]{4}\s+[0-9]{1,2}:[0-9]{2}[AP]M)/', $input, $matches)) {
            //                $dateStr = $matches[1];
            //                // แปลงวันที่โดยระบุ format ให้ตรงกับข้อมูลที่จับได้
            //                $dateObj = DateTime::createFromFormat('d-M-Y h:iA', $dateStr);
            //                if ($dateObj) {
            //                    $date = $dateObj->format('Y-m-d H:i:s');
            //                } else {
            //                    $message['complete'] = 'No Date';
            //                    file_put_contents($path, print_r($message, true), FILE_APPEND);
            //                    return 1;
            //                }
            //            } else {
            //                $message['complete'] = 'No Date';
            //                file_put_contents($path, print_r($message, true), FILE_APPEND);
            //                return 1;
            //            }

            if (preg_match('/Hash\.\s*([0-9a-f]+)/i', $input, $matches)) {
                $refid = $matches[1];
            } else {
                $refid = '';
            }

            //            $money =  Str::of($content)->between('Received  ', 'USD from')->__toString();
            //            $amounts = trim($money);
            //            $content = Str::of($content)->after('USD from');
            //            $content = Str::of($content)->explode('|');
            //            $title = trim($content[0]);
            //            $titles = Str::of($title)->explode(' ');
            //            $titles =  $titles[1].' '.$titles[0];
            //            if(isset($content[1])) {
            //                $acc = trim($content[1]);
            //            }else{
            //                $message['complete'] = 'No Account';
            //                file_put_contents($path, print_r($message, true), FILE_APPEND);
            //                return 1;
            //            }
            //
            //            if(isset($content[3])) {
            //                $refid = trim($content[3]);
            //            }else{
            //                $refid = '';
            //            }
            //
            //            if(!$amounts){
            //                $message['complete'] = 'No Amount';
            //                file_put_contents($path, print_r($message, true), FILE_APPEND);
            //                return 1;
            //            }

            $usebank = 'other';
            //            $khqr = ' (KHQR) ';
        } else {
            $content = $message['body'];

            preg_match('/ប្រាក់ចំនួន\s+([\d.]+)\s+\$\s+បានទទួលពី\s+(.*?)\s+ចូលក្នុងគណនី\s+([\d\s]+)។\s+លេខយោង\s+(\d+)/u', $content, $matches);
            if ($matches) {
                $amounts = trim($matches[1]);
                $title = $matches[2]; // ឈិន កញ្ញា
                $acc = $matches[3]; // 096 487 3546
                $refid = $matches[4]; // 51242168430
            } else {
                $message['complete'] = 'No Match';
                file_put_contents($path, print_r($message, true), FILE_APPEND);

                return 1;
            }

            if (! $title) {
                $message['complete'] = 'No Title';
                file_put_contents($path, print_r($message, true), FILE_APPEND);

                return 1;
            }

            $titles = explode(' ', $title);
            $titles = $titles[1].' '.$titles[0];

            $usebank = 'acleda';
        }

        //        file_put_contents($path, print_r($message, true), FILE_APPEND);

        $fromaccount = Str::replace(' ', '', $acc);
        $total = $amounts;
        $amount = (float) $amounts * 10;

        $hash = md5($data->code.$message['date'].$amount.$fromaccount);

        if ($usebank == 'acleda') {
            $member = Member::where('bank_code', 21)->where(function ($query) use ($title, $titles) {
                $query->where('name', $title)
                    ->orWhere('name', $titles);
            })->first();
        } else {
            $member = Member::where(function ($query) use ($title, $titles) {
                $query->where('name', $title)
                    ->orWhere('name', $titles);
            })->first();
        }

        if (! $member) {
            $member_code = 0;
            $member_user = '';
        } else {
            $member_code = $member->code;
            $member_user = ' ID : '.$member->user_name;
        }

        $newpayment = BankPayment::firstOrCreate(
            ['tx_hash' => $hash, 'account_code' => $data->code],
            [
                'bank' => 'acleda_'.$mobile,
                'bankname' => 'ACLEDA',
                'autocheck' => 'W',
                'bankstatus' => 1,
                'report_id' => $refid,
                'bank_time' => $datenow,
                'type' => '',
                'title' => 'Webhook',
                'channel' => 'WEBHOOK',
                'value' => $amount,
                'member_topup' => $member_code,
                'atranferer' => $fromaccount,
                'time' => $datenow,
                'create_by' => 'SYSAUTO',
                'detail' => 'Ref : '.$refid.$khqr.$title.' โอน '.$total.' $ '.$member_user,
            ]
        );

        if ($newpayment) {
            $message['complete'] = 'Insert Complete';
        } else {
            $message['complete'] = 'Insert Fail';
        }
        //        $newpayment->account_code = $data->code;
        //        $newpayment->bank = 'acleda_' . $mobile;
        //        $newpayment->bankstatus = 1;
        //        $newpayment->bankname = 'ACLEDA';
        //        $newpayment->report_id = '';
        //        $newpayment->bank_time = $datenow;
        //        $newpayment->type = '';
        //        $newpayment->title = 'Webhook';
        //        $newpayment->channel = 'WEBHOOK';
        //        $newpayment->value = $amount;
        //        $newpayment->tx_hash = $hash;
        //        $newpayment->detail = 'Ref : '.$refid.' '.$title . ' ฝาก ' . $total . ' จากบัญชี : ' . $fromaccount . $member_user;
        //        $newpayment->atranferer = $fromaccount;
        //        $newpayment->time = $datenow;
        //        $newpayment->create_by = 'SYSAUTO';
        //        $newpayment->save();

        file_put_contents($path, print_r($message, true), FILE_APPEND);
        if ($member) {
            broadcast(new RealTimeMessage('มีรายการฝากใหม่ รอเติม ('.$total.') จาก '.$member->user_name));
        }

        return 1;
        //        if ($titles !== false) {
        //
        //            $content = $message['content'];
        //
        //            $content = str_replace("To Account", "|to_account", $content);
        //            $content = str_replace("From Account", "|from_account", $content);
        //
        //
        //            $content = Str::of($content)->explode('|');
        //            for ($i = 0; $i < count($content); $i++) {
        //                $key_value = explode(':', $content[$i]);
        //                if ($key_value[0] == 'received_time') {
        //                    $messages[$key_value[0]] = $key_value[1] . ":" . $key_value[2] . ":" . $key_value[3];
        //                } else {
        //                    $messages[$key_value[0]] = $key_value[1];
        //                }
        //
        //            }
        //
        //            $messages['current_date'] = $message['current_date'];
        //            $messages['current_time'] = $message['current_time'];
        //
        //            file_put_contents($path, print_r($messages, true), FILE_APPEND);
        //
        //            $amount = $messages['Amount'];
        //            $total = $amount;
        //
        //            $amounts = stripos($amount, '$');
        //            if ($amounts !== false) {
        //                $amount = str_replace("$", "", $amount);
        //                $amount = (float)$amount * 10;
        //            }
        //
        //            $amounts = stripos($amount, '៛');
        //            if ($amounts !== false) {
        //                $amount = str_replace("៛", "", $amount);
        //                $amount = str_replace(",", "", $amount);
        //                $amount = (float)$amount / 100;
        //                return true;
        //            }
        //
        //
        //            $date = str_replace("/", "-", $messages['current_date']);
        //
        //            $time = $messages['current_time'];
        //            $datetime = $date . ' ' . $time;
        //
        //            $hash = md5($data->code . $datenow . $amount . $messages['from_account']);
        //
        //            $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
        //            $newpayment->account_code = $data->code;
        //            $newpayment->bank = 'wing_' . $mobile;
        //            $newpayment->bankstatus = 1;
        //            $newpayment->bankname = 'WING';
        //            $newpayment->report_id = '';
        //            $newpayment->bank_time = $datetime;
        //            $newpayment->type = '';
        //            $newpayment->title = 'Webhook';
        //            $newpayment->channel = 'WEBHOOK';
        //            $newpayment->value = $amount;
        //            $newpayment->tx_hash = $hash;
        //            $newpayment->detail = $total . ' From :' . $messages['from_account'];
        //            $newpayment->atranferer = $messages['from_account'];
        //            $newpayment->time = $date;
        //            $newpayment->create_by = 'SYSAUTO';
        //            $newpayment->save();
        //        }
        //        file_put_contents($path, print_r($newpayment, true), FILE_APPEND);

    }

    public function acleda_old($mobile, Request $request)
    {
        $datenow = now()->toDateTimeString();
        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('acleda', $mobile);
        if (! $data) {
            return 1;
        }

        $data->checktime = $datenow;
        $data->save();

        $messages = [];
        $message = $request->all();

        $message['datetime'] = $datenow;
        $path = storage_path('logs/acleda/webhook_'.$mobile.'_'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r($message, true), FILE_APPEND);

        if ($message['type'] != 'noti') {
            return 1;
        }

        if ($message['address'] != 'ACLEDA mobile') {
            return 1;
        }

        if (Str::contains($message['body'], 'You have received')) {

            $content = $message['body'];
            $money = Str::of($content)->between('You have received USD', 'from')->__toString();
            $amounts = trim($money);

            $content = Str::of($content)->after('from ');
            $acc = Str::of($content)->substr(0, 12);
            $content = Str::of($content)->after($acc);
            $content = str_replace('Ref.ID', '|', $content);
            $content = Str::of($content)->explode('|');
            $title = trim($content[0]);
            $refid = trim($content[1]);
        } else {
            $content = $message['body'];
            $money = Str::of($content)->between('លោកអ្នកបានទទួល ', 'ដុល្លារ ចូលក្នុងគណនី')->__toString();
            $amounts = trim($money);

            $content = Str::of($content)->after('ចចូលក្នុងគណនី ពី ');
            $acc = Str::of($content)->substr(0, 12);
            $content = Str::of($content)->after($acc);
            $content = str_replace('លេខយោង', '|', $content);
            $content = Str::of($content)->explode('|');
            $title = trim($content[0]);
            $refid = trim($content[1]);
        }

        if (! $amounts) {
            return true;
        }

        $fromaccount = Str::replace(' ', '', $acc);
        $total = $amounts;
        $amount = (float) $amounts * 10;

        $hash = md5($data->code.$message['date'].$amount.$fromaccount);

        $member = Member::where('bank_code', 21)->where('acc_no', $fromaccount)->first();
        if (! $member) {
            $member_user = '';
        } else {
            $member_user = ' ID : '.$member->user_name;
        }

        $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
        $newpayment->account_code = $data->code;
        $newpayment->bank = 'acleda_'.$mobile;
        $newpayment->bankstatus = 1;
        $newpayment->bankname = 'ACLEDA';
        $newpayment->report_id = '';
        $newpayment->bank_time = $datenow;
        $newpayment->type = '';
        $newpayment->title = 'Webhook';
        $newpayment->channel = 'WEBHOOK';
        $newpayment->value = $amount;
        $newpayment->tx_hash = $hash;
        $newpayment->detail = 'Ref : '.$refid.' '.$title.' ฝาก '.$total.' จากบัญชี : '.$fromaccount.$member_user;
        $newpayment->atranferer = $fromaccount;
        $newpayment->time = $datenow;
        $newpayment->create_by = 'SYSAUTO';
        $newpayment->save();

        if ($member) {
            broadcast(new RealTimeMessage('มีรายการฝากใหม่ รอเติม ('.$total.') จาก '.$member->user_name));
        }
        //        if ($titles !== false) {
        //
        //            $content = $message['content'];
        //
        //            $content = str_replace("To Account", "|to_account", $content);
        //            $content = str_replace("From Account", "|from_account", $content);
        //
        //
        //            $content = Str::of($content)->explode('|');
        //            for ($i = 0; $i < count($content); $i++) {
        //                $key_value = explode(':', $content[$i]);
        //                if ($key_value[0] == 'received_time') {
        //                    $messages[$key_value[0]] = $key_value[1] . ":" . $key_value[2] . ":" . $key_value[3];
        //                } else {
        //                    $messages[$key_value[0]] = $key_value[1];
        //                }
        //
        //            }
        //
        //            $messages['current_date'] = $message['current_date'];
        //            $messages['current_time'] = $message['current_time'];
        //
        //            file_put_contents($path, print_r($messages, true), FILE_APPEND);
        //
        //            $amount = $messages['Amount'];
        //            $total = $amount;
        //
        //            $amounts = stripos($amount, '$');
        //            if ($amounts !== false) {
        //                $amount = str_replace("$", "", $amount);
        //                $amount = (float)$amount * 10;
        //            }
        //
        //            $amounts = stripos($amount, '៛');
        //            if ($amounts !== false) {
        //                $amount = str_replace("៛", "", $amount);
        //                $amount = str_replace(",", "", $amount);
        //                $amount = (float)$amount / 100;
        //                return true;
        //            }
        //
        //
        //            $date = str_replace("/", "-", $messages['current_date']);
        //
        //            $time = $messages['current_time'];
        //            $datetime = $date . ' ' . $time;
        //
        //            $hash = md5($data->code . $datenow . $amount . $messages['from_account']);
        //
        //            $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
        //            $newpayment->account_code = $data->code;
        //            $newpayment->bank = 'wing_' . $mobile;
        //            $newpayment->bankstatus = 1;
        //            $newpayment->bankname = 'WING';
        //            $newpayment->report_id = '';
        //            $newpayment->bank_time = $datetime;
        //            $newpayment->type = '';
        //            $newpayment->title = 'Webhook';
        //            $newpayment->channel = 'WEBHOOK';
        //            $newpayment->value = $amount;
        //            $newpayment->tx_hash = $hash;
        //            $newpayment->detail = $total . ' From :' . $messages['from_account'];
        //            $newpayment->atranferer = $messages['from_account'];
        //            $newpayment->time = $date;
        //            $newpayment->create_by = 'SYSAUTO';
        //            $newpayment->save();
        //        }
        //        file_put_contents($path, print_r($newpayment, true), FILE_APPEND);

    }

    public function pompay_create(Request $request)
    {

        if (config('app.user_url') === '') {
            $baseurl = 'https://'.(is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        } else {
            $baseurl = 'https://'.config('app.user_url').'.'.(is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        }

        $clientId = 'GENXPAY3';
        $transactionId = '9826600862';
        $custName = 'เลปกร';
        $custSecondaryName = 'ศรีสมุทร';
        $custBank = 'scb';
        $custMobile = '0654043242';
        $custEmail = 'tide.18788@gmail.com';
        $amount = '50.00';
        $returnUrl = 'http://localhost:3000/return';
        $callbackUrl = 'http://localhost:3000/tansaction';
        $paymentMethod = 'qr';
        $bankAcc = '0334346720';
        $clientSecret = 'a37d4ae3b11a3c7b506a8251c3ab8296c8ba64ae';
        $hash = hash('sha256', $clientId.$transactionId.$custName.$custSecondaryName.$custBank.$custMobile.$custEmail.$amount.$returnUrl.$callbackUrl.$paymentMethod.$bankAcc.$clientSecret);

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
        ];

        return view($this->_config['view'], compact('pompay'));
    }

    public function scb_callback(Request $request)
    {
        $mobile = '1882028767';
        $date = now()->toDateString();
        $datenow = now()->toDateTimeString();
        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('scb', $mobile);
        if (! $data) {
            return 1;
        }

        $data->checktime = $datenow;
        $data->save();

        $messages = [];
        $message = $request->all();

        $path = storage_path('logs/scb/webhook_'.$mobile.'_'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r($message, true), FILE_APPEND);
        //        return true;
        //        $path = storage_path('ttb/webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        //        file_put_contents($path, print_r($message, true), FILE_APPEND);

        if (! isset($message['displayOriginatingAddress'])) {
            return true;
        }
        $displayOriginatingAddress = $message['displayOriginatingAddress'];
        if ($displayOriginatingAddress != '027777777') {
            return true;
        }

        $title = $message['text'];
        $titles = stripos($title, 'จาก');
        if ($titles !== false) {

            $from = Str::of($title)->between('จาก', 'เข้า')->__toString();

            $contents = explode(' ', $title);
            $amount = $contents[1];

            $amount = str_replace(',', '', $amount);
            $messages['amount'] = number_format((float) $amount, 2, '.', '');

            $banks = explode('/', $from);
            $bank = $banks['0'];
            if ($bank == 'KBNK') {
                $bank = 'KBANK';
            }
            $messages['bank'] = $bank;
            $messages['acc'] = $banks['1'];

            $datetimeall = explode('@', $contents[0]);
            $dates = explode('/', $datetimeall[0]);
            $date = date('Y').'-'.$dates[1].'-'.$dates[0];
            $time = $datetimeall[1].':00';
            $messages['current_date'] = $date;
            $messages['current_time'] = $time;

            $remains = $contents[3];
            $remains = str_replace('ใช้ได้', '', $remains);
            $remains = str_replace('บ', '', $remains);
            $remains = str_replace(',', '', $remains);
            $remain = number_format((float) $remains, 2, '.', '');
            $messages['remain'] = $remain;

            file_put_contents($path, print_r($messages, true), FILE_APPEND);

            //            $date = str_replace("/", "-", $messages['current_date']);

            //            $time = Str::of($title)->explode('@');
            $datetime = $date.' '.$time;

            $hash = md5($data->code.$datetime.$amount.$messages['bank'].$messages['acc'].$remain);

            $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
            $newpayment->account_code = $data->code;
            $newpayment->bank = 'scb_'.$mobile;
            $newpayment->bankstatus = 1;
            $newpayment->bankname = 'SCB';
            $newpayment->report_id = $messages['bank'];
            $newpayment->bank_time = $datetime;
            $newpayment->type = '';
            $newpayment->title = '';
            $newpayment->channel = 'SMS';
            $newpayment->value = $amount;
            $newpayment->tx_hash = $hash;
            $newpayment->detail = $messages['bank'].' '.$messages['acc'];
            $newpayment->atranferer = str_replace('X', '', $messages['acc']);
            $newpayment->time = $datetime;
            $newpayment->create_by = 'SYSAUTO';
            $newpayment->save();

            $data->balance = $remain;
            $data->save();

        }

    }

    public function scb_app($mobile, Request $request)
    {
        //        $mobile = '1882028767';
        $date = now()->toDateString();
        $datenow = now()->toDateTimeString();
        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('scb', $mobile);
        if (! $data) {
            return 1;
        }

        if ($data->webhook == 'N') {
            return 1;
        }

        $data->checktime = $datenow;
        $data->save();

        $messages = [];
        $message = $request->all();

        $path = storage_path('logs/scb/webhook_'.$mobile.'_'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r($message, true), FILE_APPEND);
        //        return true;

        //        $path = storage_path('logs/scb/webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        //        file_put_contents($path, print_r($message, true), FILE_APPEND);
        //        return true;
        //        $path = storage_path('ttb/webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        //        file_put_contents($path, print_r($message, true), FILE_APPEND);

        if ($message['username'] != $mobile) {
            return true;
        }

        $displayOriginatingAddress = $message['address'];

        if ($displayOriginatingAddress != '027777777' && $displayOriginatingAddress != '02 777 7777') {
            $message['status'] = 'Address Wrong';
            file_put_contents($path, print_r($message, true), FILE_APPEND);

            return true;
        }

        $title = $message['body'];
        $titles = stripos($title, 'จาก');
        if ($titles !== false) {

            $from = Str::of($title)->between('จาก', 'เข้า')->__toString();

            $contents = explode(' ', $title);
            $amount = $contents[1];

            $amount = str_replace(',', '', $amount);
            $messages['amount'] = number_format((float) $amount, 2, '.', '');

            $banks = explode('/', $from);
            $bank = $banks['0'];
            if ($bank == 'KBNK') {
                $bank = 'KBANK';
            }
            if ($bank == 'GSBA') {
                $bank = 'GSB';
            }
            $messages['bank'] = $bank;
            $messages['acc'] = $banks['1'];

            $datetimeall = explode('@', $contents[0]);
            $dates = explode('/', $datetimeall[0]);
            $date = date('Y').'-'.$dates[1].'-'.$dates[0];
            $time = $datetimeall[1].':00';
            $messages['current_date'] = $date;
            $messages['current_time'] = $time;
            $messages['date'] = $datenow;

            $remains = $contents[3];
            $remains = str_replace('ใช้ได้', '', $remains);
            $remains = str_replace('บ', '', $remains);
            $remains = str_replace(',', '', $remains);
            $remain = number_format((float) $remains, 2, '.', '');
            $messages['remain'] = $remain;

            file_put_contents($path, print_r($messages, true), FILE_APPEND);

            //            $date = str_replace("/", "-", $messages['current_date']);

            //            $time = Str::of($title)->explode('@');
            $datetime = $date.' '.$time;

            $hash = md5($data->code.$datetime.$amount.$messages['bank'].$messages['acc']);

            $diff = core()->DateDiffMin($datetime);
            if ($diff > 5) {
                $msg = $messages['current_date'].' '.$messages['current_time'].' '.$messages['bank'].' '.$messages['acc'].' ('.$messages['amount'].') รายการเกิน 5 นาที ระบบจะข้ามรายการนี้';
                broadcast(new RealTimeMessage($msg));
                $messages['status'] = 'Over 5 Min';
                file_put_contents($path, print_r($messages, true), FILE_APPEND);

                return true;
            }

            $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
            $newpayment->account_code = $data->code;
            $newpayment->bank = 'scb_'.$mobile;
            $newpayment->bankstatus = 1;
            $newpayment->bankname = 'SCB';
            $newpayment->report_id = $messages['bank'];
            $newpayment->bank_time = $datetime;
            $newpayment->type = '';
            $newpayment->title = '';
            $newpayment->channel = 'SMS';
            $newpayment->value = $amount;
            $newpayment->tx_hash = $hash;
            $newpayment->detail = $messages['bank'].' '.$messages['acc'];
            $newpayment->atranferer = str_replace('x', '', $messages['acc']);
            $newpayment->time = $datetime;
            $newpayment->create_by = 'SYSAUTO';
            $newpayment->save();

            $messages['status'] = 'Insert Complete';
            file_put_contents($path, print_r($messages, true), FILE_APPEND);

            $data->balance = $remain;
            $data->save();

        }

    }

    public function scb($mobile, Request $request)
    {
        //        return true;
        //        $mobile = '1882028767';
        $date = now()->toDateString();
        $datenow = now()->toDateTimeString();
        //        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('scb', $mobile);
        //        if (! $data) {
        //            return 1;
        //        }
        //
        //        if ($data->webhook == 'N') {
        //            return 1;
        //        }

        //        $data->checktime = $datenow;
        //        $data->save();
        //
        //        $messages = [];
        $message = $request->all();

        $path = storage_path('logs/scb/webhook_'.$mobile.'_'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r($message, true), FILE_APPEND);

        return true;
        //        $path = storage_path('ttb/webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        //        file_put_contents($path, print_r($message, true), FILE_APPEND);

        if (! isset($message['phone'])) {
            return true;
        }
        $displayOriginatingAddress = $message['phone'];
        if ($displayOriginatingAddress != '027777777') {
            return true;
        }

        $title = $message['text'];
        $titles = stripos($title, 'จาก');
        if ($titles !== false) {

            $from = Str::of($title)->between('จาก', 'เข้า')->__toString();

            $contents = explode(' ', $title);
            $amount = $contents[1];

            $amount = str_replace(',', '', $amount);
            $messages['amount'] = number_format((float) $amount, 2, '.', '');

            $banks = explode('/', $from);
            $bank = $banks['0'];
            if ($bank == 'KBNK') {
                $bank = 'KBANK';
            }
            if ($bank == 'GSBA') {
                $bank = 'GSB';
            }
            $messages['bank'] = $bank;
            $messages['acc'] = $banks['1'];

            $datetimeall = explode('@', $contents[0]);
            $dates = explode('/', $datetimeall[0]);
            $date = date('Y').'-'.$dates[1].'-'.$dates[0];
            $time = $datetimeall[1].':00';
            $messages['current_date'] = $date;
            $messages['current_time'] = $time;
            $messages['date'] = $datenow;

            $remains = $contents[3];
            $remains = str_replace('ใช้ได้', '', $remains);
            $remains = str_replace('บ', '', $remains);
            $remains = str_replace(',', '', $remains);
            $remain = number_format((float) $remains, 2, '.', '');
            $messages['remain'] = $remain;

            file_put_contents($path, print_r($messages, true), FILE_APPEND);

            //            $date = str_replace("/", "-", $messages['current_date']);

            //            $time = Str::of($title)->explode('@');
            $datetime = $date.' '.$time;

            $hash = md5($data->code.$datetime.$amount.$messages['bank'].$messages['acc']);

            //            $diff = core()->DateDiffMin($datetime);
            //            if ($diff > 5) {
            //                $msg = $messages['current_date'].' '.$messages['current_time'].' '.$messages['bank'].' '.$messages['acc'].' ('.$messages['amount'].') รายการเกิน 5 นาที ระบบจะข้ามรายการนี้';
            //                broadcast(new RealTimeMessage($msg));
            //                return 0;
            //            }

            $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
            $newpayment->account_code = $data->code;
            $newpayment->bank = 'scb_'.$mobile;
            $newpayment->bankstatus = 1;
            $newpayment->bankname = 'SCB';
            $newpayment->report_id = $messages['bank'];
            $newpayment->bank_time = $datetime;
            $newpayment->type = '';
            $newpayment->title = '';
            $newpayment->channel = 'SMS';
            $newpayment->value = $amount;
            $newpayment->tx_hash = $hash;
            $newpayment->detail = $messages['bank'].' '.$messages['acc'];
            $newpayment->atranferer = str_replace('x', '', $messages['acc']);
            $newpayment->time = $datetime;
            $newpayment->create_by = 'SYSAUTO';
            $newpayment->save();

            $data->balance = $remain;
            $data->save();

        }

    }

    public function scb_login(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        $data['status'] = 'ok';
        $data['url'] = "https://service.cambo789.com/$username/$password/webhook";

        Http::post($data['url'], ['type' => 'app', 'status' => 'login', 'date' => now()->toDateTimeString()]);

        return response()->json($data);

    }

    public function scb_superrich($mobile, Request $request)
    {
        //        $mobile = '1882028767';
        $date = now()->toDateString();
        $datenow = now()->toDateTimeString();

        $data = DB::connection('superrich')->table('bankaccount')->where('accountno', $mobile)->where('status', 1)->where('status_auto', 'Y')->where('enable', 'Y')->first();
        //        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('scb', $mobile);
        if (! $data) {
            return 1;
        }
        //
        //        if ($data->webhook == 'N') {
        //            return 1;
        //        }
        //
        //        $data->checktime = $datenow;
        //        $data->save();

        $messages = [];
        $message = $request->all();

        $path = storage_path('logs/scb/webhook_superrich_'.$mobile.'_'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r($message, true), FILE_APPEND);
        //        return true;

        //        $path = storage_path('logs/scb/webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        //        file_put_contents($path, print_r($message, true), FILE_APPEND);
        //        return true;
        //        $path = storage_path('ttb/webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        //        file_put_contents($path, print_r($message, true), FILE_APPEND);

        if ($message['username'] != $mobile) {
            return true;
        }

        $displayOriginatingAddress = $message['address'];
        if ($displayOriginatingAddress != '027777777' && $displayOriginatingAddress != '02 777 7777') {
            return true;
        }

        $title = $message['body'];
        $titles = stripos($title, 'จาก');
        if ($titles !== false) {

            $from = Str::of($title)->between('จาก', 'เข้า')->__toString();

            $contents = explode(' ', $title);
            $amount = $contents[1];

            $amount = str_replace(',', '', $amount);
            $messages['amount'] = number_format((float) $amount, 2, '.', '');

            $banks = explode('/', $from);
            $bank = $banks['0'];
            if ($bank == 'KBNK') {
                $bank = 'KBANK';
            }
            if ($bank == 'GSBA') {
                $bank = 'GSB';
            }
            $messages['bank'] = $bank;
            $messages['acc'] = $banks['1'];

            $datetimeall = explode('@', $contents[0]);
            $dates = explode('/', $datetimeall[0]);
            $date = date('Y').'-'.$dates[1].'-'.$dates[0];
            $time = $datetimeall[1].':00';
            $messages['current_date'] = $date;
            $messages['current_time'] = $time;
            $messages['date'] = $datenow;

            $remains = $contents[3];
            $remains = str_replace('ใช้ได้', '', $remains);
            $remains = str_replace('บ', '', $remains);
            $remains = str_replace(',', '', $remains);
            $remain = number_format((float) $remains, 2, '.', '');
            $messages['remain'] = $remain;

            file_put_contents($path, print_r($messages, true), FILE_APPEND);

            //            $date = str_replace("/", "-", $messages['current_date']);

            //            $time = Str::of($title)->explode('@');
            $datetime = $date.' '.$time;

            $hash = md5($data->code.$datetime.$amount.$messages['bank'].$messages['acc']);

            $checktime = strtotime(date('Y-m-d H:i:s'));
            $check = DB::connection('superrich')->table('bank_payment')->where('tx_hash', $hash)->first();
            if (! $check) {
                DB::connection('superrich')->table('bank_payment')->insert([
                    'bank' => strtolower('scb_'.$mobile),
                    'bankstatus' => 1,
                    'bankname' => 'SCB',
                    'date_create' => date('Y-m-d H:i:s'),
                    'checktime' => $checktime,
                    'time' => $checktime,
                    'channel' => 'SMS',
                    'value' => $amount,
                    'tx_hash' => $hash,
                    'detail' => $messages['bank'].' '.$messages['acc'],
                    'atranferer' => '',
                ]);

                DB::connection('superrich')->table('bankbalance')->where('bank', $data->bank.'_'.$data->accountno)->update(['updatetime' => $checktime, 'balance' => $remain]);
            }

        }

        return true;
    }

    public function scb_superrich69($mobile, Request $request)
    {
        //        $mobile = '1882028767';
        $date = now()->toDateString();
        $datenow = now()->toDateTimeString();

        $data = DB::connection('superrich69')->table('bankaccount')->where('accountno', $mobile)->where('status', 1)->where('status_auto', 'Y')->where('enable', 'Y')->first();
        //        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('scb', $mobile);
        if (! $data) {
            return 1;
        }
        //
        //        if ($data->webhook == 'N') {
        //            return 1;
        //        }
        //
        //        $data->checktime = $datenow;
        //        $data->save();

        $messages = [];
        $message = $request->all();

        $path = storage_path('logs/scb/webhook_superrich69_'.$mobile.'_'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r($message, true), FILE_APPEND);
        //        return true;

        //        $path = storage_path('logs/scb/webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        //        file_put_contents($path, print_r($message, true), FILE_APPEND);
        //        return true;
        //        $path = storage_path('ttb/webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        //        file_put_contents($path, print_r($message, true), FILE_APPEND);

        if ($message['username'] != $mobile) {
            return true;
        }

        $displayOriginatingAddress = $message['address'];
        if ($displayOriginatingAddress != '027777777' && $displayOriginatingAddress != '02 777 7777') {
            return true;
        }

        $title = $message['body'];
        $titles = stripos($title, 'จาก');
        if ($titles !== false) {

            $from = Str::of($title)->between('จาก', 'เข้า')->__toString();

            $contents = explode(' ', $title);
            $amount = $contents[1];

            $amount = str_replace(',', '', $amount);
            $messages['amount'] = number_format((float) $amount, 2, '.', '');

            $banks = explode('/', $from);
            $bank = $banks['0'];
            if ($bank == 'KBNK') {
                $bank = 'KBANK';
            }
            if ($bank == 'GSBA') {
                $bank = 'GSB';
            }
            $messages['bank'] = $bank;
            $messages['acc'] = $banks['1'];

            $datetimeall = explode('@', $contents[0]);
            $dates = explode('/', $datetimeall[0]);
            $date = date('Y').'-'.$dates[1].'-'.$dates[0];
            $time = $datetimeall[1].':00';
            $messages['current_date'] = $date;
            $messages['current_time'] = $time;
            $messages['date'] = $datenow;

            $remains = $contents[3];
            $remains = str_replace('ใช้ได้', '', $remains);
            $remains = str_replace('บ', '', $remains);
            $remains = str_replace(',', '', $remains);
            $remain = number_format((float) $remains, 2, '.', '');
            $messages['remain'] = $remain;

            file_put_contents($path, print_r($messages, true), FILE_APPEND);

            //            $date = str_replace("/", "-", $messages['current_date']);

            //            $time = Str::of($title)->explode('@');
            $datetime = $date.' '.$time;

            $hash = md5($data->code.$datetime.$amount.$messages['bank'].$messages['acc']);

            $checktime = strtotime(date('Y-m-d H:i:s'));
            $check = DB::connection('superrich69')->table('bank_payment')->where('tx_hash', $hash)->first();
            if (! $check) {
                DB::connection('superrich69')->table('bank_payment')->insert([
                    'bank' => strtolower('scb_'.$mobile),
                    'bankstatus' => 1,
                    'bankname' => 'SCB',
                    'checktime' => $checktime,
                    'date_create' => date('Y-m-d H:i:s'),
                    'time' => $checktime,
                    'channel' => 'SMS',
                    'value' => $amount,
                    'tx_hash' => $hash,
                    'detail' => $messages['bank'].' '.$messages['acc'],
                    'atranferer' => '',
                ]);

                DB::connection('superrich69')->table('bankbalance')->where('bank', $data->bank.'_'.$data->accountno)->update(['updatetime' => $checktime, 'balance' => $remain]);
            }

        }

        return true;
    }

    public function kbank_noti($mobile, Request $request)
    {
        //        $mobile = '1882028767';
        $date = now()->toDateString();
        $datenow = now()->toDateTimeString();
        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('kbank', $mobile);
        if (! $data) {
            return 1;
        }

        if ($data->webhook == 'N') {
            return 1;
        }

        $data->checktime = $datenow;
        $data->save();

        $messages = [];
        $message = $request->all();

        $path = storage_path('logs/kbank/webhook_'.$mobile.'_'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r($message, true), FILE_APPEND);

        return true;

        //        $path = storage_path('logs/scb/webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        //        file_put_contents($path, print_r($message, true), FILE_APPEND);
        //        return true;
        //        $path = storage_path('ttb/webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        //        file_put_contents($path, print_r($message, true), FILE_APPEND);

        if ($message['username'] != $mobile) {
            return true;
        }

        $displayOriginatingAddress = $message['address'];

        if ($displayOriginatingAddress != '027777777' && $displayOriginatingAddress != '02 777 7777') {
            $message['status'] = 'Address Wrong';
            file_put_contents($path, print_r($message, true), FILE_APPEND);

            return true;
        }

        $title = $message['body'];
        $titles = stripos($title, 'จาก');
        if ($titles !== false) {

            $from = Str::of($title)->between('จาก', 'เข้า')->__toString();

            $contents = explode(' ', $title);
            $amount = $contents[1];

            $amount = str_replace(',', '', $amount);
            $messages['amount'] = number_format((float) $amount, 2, '.', '');

            $banks = explode('/', $from);
            $bank = $banks['0'];
            if ($bank == 'KBNK') {
                $bank = 'KBANK';
            }
            if ($bank == 'GSBA') {
                $bank = 'GSB';
            }
            $messages['bank'] = $bank;
            $messages['acc'] = $banks['1'];

            $datetimeall = explode('@', $contents[0]);
            $dates = explode('/', $datetimeall[0]);
            $date = date('Y').'-'.$dates[1].'-'.$dates[0];
            $time = $datetimeall[1].':00';
            $messages['current_date'] = $date;
            $messages['current_time'] = $time;
            $messages['date'] = $datenow;

            $remains = $contents[3];
            $remains = str_replace('ใช้ได้', '', $remains);
            $remains = str_replace('บ', '', $remains);
            $remains = str_replace(',', '', $remains);
            $remain = number_format((float) $remains, 2, '.', '');
            $messages['remain'] = $remain;

            file_put_contents($path, print_r($messages, true), FILE_APPEND);

            //            $date = str_replace("/", "-", $messages['current_date']);

            //            $time = Str::of($title)->explode('@');
            $datetime = $date.' '.$time;

            $hash = md5($data->code.$datetime.$amount.$messages['bank'].$messages['acc']);

            $diff = core()->DateDiffMin($datetime);
            if ($diff > 5) {
                $msg = $messages['current_date'].' '.$messages['current_time'].' '.$messages['bank'].' '.$messages['acc'].' ('.$messages['amount'].') รายการเกิน 5 นาที ระบบจะข้ามรายการนี้';
                broadcast(new RealTimeMessage($msg));
                $messages['status'] = 'Over 5 Min';
                file_put_contents($path, print_r($messages, true), FILE_APPEND);

                return true;
            }

            $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
            $newpayment->account_code = $data->code;
            $newpayment->bank = 'scb_'.$mobile;
            $newpayment->bankstatus = 1;
            $newpayment->bankname = 'SCB';
            $newpayment->report_id = $messages['bank'];
            $newpayment->bank_time = $datetime;
            $newpayment->type = '';
            $newpayment->title = '';
            $newpayment->channel = 'SMS';
            $newpayment->value = $amount;
            $newpayment->tx_hash = $hash;
            $newpayment->detail = $messages['bank'].' '.$messages['acc'];
            $newpayment->atranferer = str_replace('x', '', $messages['acc']);
            $newpayment->time = $datetime;
            $newpayment->create_by = 'SYSAUTO';
            $newpayment->save();

            $messages['status'] = 'Insert Complete';
            file_put_contents($path, print_r($messages, true), FILE_APPEND);

            $data->balance = $remain;
            $data->save();

        }

    }
}
