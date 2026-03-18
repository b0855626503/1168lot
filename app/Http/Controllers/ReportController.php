<?php

namespace App\Http\Controllers;

use App\Helpers\TelegramBot;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;


class ReportController extends Controller
{

    /** สมัคร (บันทึก/อัปเดต) Push Subscription ให้ customer ปัจจุบัน */
    public function daily(Request $req)
    {
        $date = $req->input('date');
        $data = [];

        $exitCode = Artisan::call('dailystat:check', [
            'date' => $date
        ]);

        $data = DB::table('daily_stat')->whereDate('date', $date)->get()->toArray();

//        $message = 'กำลังคำนวนอยู่ จะเสร็จแล้ว';
//        TelegramBot::Send('notify/send', $message, ['parse_mode' => 'HTML']);

        return response()->json($data);
    }

}
