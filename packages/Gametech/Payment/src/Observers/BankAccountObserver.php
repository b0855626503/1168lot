<?php

namespace Gametech\Payment\Observers;

use App\Events\RealTimeMessage;
use App\Events\RealTimeNewMessage;
use App\Events\WalletDisplayChanged;
use Gametech\Core\Models\Config;
use Gametech\Core\Models\Log;
use Gametech\LogAdmin\Http\Traits\ActivityLogger;
use Gametech\Payment\Models\BankAccount as EventData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class BankAccountObserver
{
    use ActivityLogger;

    public function created(EventData $data)
    {
        // ดึงผู้ใช้ปัจจุบัน (admin)
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return; // ห้าม exit ใน observer
        }

        // บันทึก Log ในทรานแซกชันเดียวกับการสร้าง
        $log = new Log;
        $log->emp_code    = $admin->code;
        $log->mode        = 'ADD';
        $log->menu        = 'banks_account';
        $log->record      = $data->code;
        $log->item_before = json_encode($data->getOriginal(), JSON_UNESCAPED_UNICODE);
        $log->item        = json_encode($data->getChanges(),  JSON_UNESCAPED_UNICODE);
        $log->ip          = Request::ip();
        $log->user_create = $admin->user_name;
        $log->save();

        // Broadcast หลังคอมมิตจริงเท่านั้น
        DB::afterCommit(function () use ($data, $admin) {

            broadcast(new RealTimeNewMessage(
                'มีการเพิ่มเลขบัญชีรับฝาก ' . $data->acc_no . ' โดย ' . $admin->user_name,
                [
                    'ui' => 'toast',
                    'as' => 'RealTime.Message.All',
                    'toast' => [
                        'className' => 'gt-toast gt-toast-error',
                        'duration' => 30000,
                        'gravity' => 'top',
                        'position' => 'right',
                        'avatar' => '/assets/admin/icons/alert.webp',
                    ],
                ]
            ));
//            broadcast(new RealTimeMessage(
//                'มีการเพิ่มเลขบัญชีรับฝาก ' . $data->acc_no . ' โดย ' . $admin->user_name
//            ))->toOthers();
        });
    }

    public function updated(EventData $data)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return;
        }

        // บันทึก Log การแก้ไข
        $log = new Log;
        $log->emp_code    = $admin->code;
        $log->mode        = 'EDIT';
        $log->menu        = 'banks_account';
        $log->record      = $data->code;
        $log->item_before = json_encode($data->getOriginal(), JSON_UNESCAPED_UNICODE);
        $log->item        = json_encode($data->getChanges(),  JSON_UNESCAPED_UNICODE);
        $log->ip          = Request::ip();
        $log->user_create = $admin->user_name;
        $log->save();

        // เก็บค่าเดิมที่จำเป็นก่อนคอมมิต
        $accNoBefore = $data->getOriginal('acc_no');
        $accNoAfter  = $data->acc_no;

        $rateChanged = $data->wasChanged('rate') || $data->wasChanged('rate_update');
        $displayWalletChanged = $data->wasChanged('display_wallet');
        $accNoChanged         = $data->wasChanged('acc_no');

        // ทำ side effects หลังคอมมิตเสมอ
        DB::afterCommit(function () use (
            $data,
            $admin,
            $rateChanged,
            $displayWalletChanged,
            $accNoChanged,
            $accNoBefore,
            $accNoAfter
        ) {
            // แจ้งเตือนเปลี่ยนเลขบัญชี
            if ($accNoChanged) {

                broadcast(new RealTimeNewMessage(
                    'มีการเปลี่ยนเลขบัญชีรับฝากจาก ' .  $accNoBefore . ' เป็น ' . $accNoAfter . ' โดย ' . $admin->user_name,
                    [
                        'ui' => 'toast',
                        'as' => 'RealTime.Message.All',
                        'toast' => [
                            'className' => 'gt-toast gt-toast-error',
                            'duration' => 30000,
                            'gravity' => 'top',
                            'position' => 'right',
                            'avatar' => '/assets/admin/icons/alert.webp',
                        ],
                    ]
                ));

//                broadcast(new RealTimeMessage(
//                    'มีการเปลี่ยนเลขบัญชีรับฝากจาก ' . $accNoBefore . ' เป็น ' . $accNoAfter . ' โดย ' . $admin->user_name
//                ))->toOthers();
            }

            // ซิงก์ค่า rate ไปยัง Config (ถ้าคุณตั้งใจให้ Config สะท้อน rate ของบัญชีนี้)
            if ($rateChanged) {
                // ใช้ updateQuietly เลี่ยง Observer/Events อื่น ๆ ที่อาจพ่วง Config
                $config = Config::query()->first();
                if ($config) {
                    $config->updateQuietly([
                        'rate'        => $data->rate,
                        'rate_update' => $data->rate_update,
                    ]);
                }
            }

            // แจ้งเตือนเมื่อเปลี่ยนสถานะการแสดง Wallet
            if ($displayWalletChanged) {
                broadcast(new WalletDisplayChanged(
                    (int) $data->code,
                    (string) $data->display_wallet,
                    $admin->user_name
                ))->toOthers(); // กัน echo loop ฝั่งผู้ที่กดเอง
            }
        });
    }

    public function deleted(EventData $data)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return;
        }

        // บันทึก Log การลบ
        $log = new Log;
        $log->emp_code    = $admin->code;
        $log->mode        = 'DEL';
        $log->menu        = 'banks_account';
        $log->record      = $data->code;
        $log->item_before = json_encode($data->getOriginal(), JSON_UNESCAPED_UNICODE);
        $log->item        = json_encode($data->getChanges(),  JSON_UNESCAPED_UNICODE);
        $log->ip          = Request::ip();
        $log->user_create = $admin->user_name;
        $log->save();

        // Broadcast หลังคอมมิตจริง
        DB::afterCommit(function () use ($data, $admin) {

            broadcast(new RealTimeNewMessage(
                'มีการลบเลขบัญชีรับฝาก ' . $data->acc_no . ' โดย ' . $admin->user_name,
                [
                    'ui' => 'toast',
                    'as' => 'RealTime.Message.All',
                    'toast' => [
                        'className' => 'gt-toast gt-toast-error',
                        'duration' => 30000,
                        'gravity' => 'top',
                        'position' => 'right',
                        'avatar' => '/assets/admin/icons/alert.webp',
                    ],
                ]
            ));

        });
    }
}
