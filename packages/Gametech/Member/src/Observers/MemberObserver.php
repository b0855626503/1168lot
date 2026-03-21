<?php

namespace Gametech\Member\Observers;

use App\Events\RealTimeMessage;
use App\Events\RealTimeNewMessage;
use App\Services\Dashboard\DashboardSummarySyncService;
use Gametech\Core\Models\Log;
use Gametech\LogAdmin\Http\Traits\ActivityLogger;
use Gametech\Member\Models\Member as EventData;
use Gametech\Member\Models\MemberEditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Request;

class MemberObserver
{
    use ActivityLogger;

    public function created(EventData $data): void
    {
        DB::afterCommit(function () use ($data) {
            $this->dispatchDashboardSync($data);
            Event::dispatch('member.created.after', [$data]);
        });
    }

    public function updated(EventData $data): void
    {
        if ($this->shouldSyncDashboard($data)) {
            DB::afterCommit(function () use ($data) {
                $this->dispatchDashboardSync($data);
            });
        }

        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return;
        }

        // Log รวมการแก้ไข (เดิมทำทุกเคสอยู่แล้ว)
        $this->writeMainLog($admin->code, $admin->user_name, $data);

        // เขียน MemberEditLog เฉพาะฟิลด์ที่กำหนด
        $username = $data->user_name;

        // ฟิลด์ที่ต้องการลงบันทึกแบบเฉพาะเจาะจง
        $fields = [
            'firstname' => ['mode' => 'ชื่อ', 'menu' => 'firstname', 'remark' => 'แก้ไขชื่อลูกค้า'],
            'lastname' => ['mode' => 'นามสกุล', 'menu' => 'lastname', 'remark' => 'แก้ไขนามสกุลลูกค้า'],
            'tel' => ['mode' => 'เบอร์โทร', 'menu' => 'tel', 'remark' => 'แก้ไขเบอร์โทรลูกค้า'],
            'bank_code' => ['mode' => 'ธนาคาร', 'menu' => 'bank_code', 'remark' => 'แก้ไขธนาคารลูกค้า'],
            'acc_no' => ['mode' => 'เลขบัญชี', 'menu' => 'acc_no', 'remark' => 'แก้ไขเลขบัญชีลูกค้า'],
            'lineid' => ['mode' => 'ไอดีไลน์', 'menu' => 'lineid', 'remark' => 'แก้ไขไอดีไลน์ลูกค้า'],
            'maxwithdraw_day' => ['mode' => 'ยอดถอนสูงสุด/วัน', 'menu' => 'maxwithdraw_day', 'remark' => 'แก้ไข ยอดถอนสูงสุด/วัน ลูกค้า'],
            'promotion' => ['mode' => 'สถานะการรับโปร', 'menu' => 'promotion', 'remark' => 'แก้ไขสถานะการรับโปรลูกค้า'],
            'status_pro' => ['mode' => 'สถานะโปรสมาชิกใหม่', 'menu' => 'status_pro', 'remark' => 'แก้ไขสถานะโปรสมาชิกใหม่ลูกค้า'],
            'enable' => ['mode' => 'สถานะใช้งาน', 'menu' => 'enable', 'remark' => 'แก้ไขสถานะใช้งานลูกค้า'],
        ];

        foreach ($fields as $field => $meta) {
            if ($data->wasChanged($field)) {
                $this->writeMemberEditLog(
                    empCode: $admin->code,
                    empUser: $admin->user_name,
                    mode: $meta['mode'],
                    menu: $meta['menu'],
                    remark: $meta['remark'],
                    member: $data,
                    memberUser: $username,
                    before: (string)$data->getOriginal($field),
                    after: (string)$data->{$field}
                );
            }
        }

        // รหัสผ่าน: บันทึกแบบ mask ไม่เก็บ plaintext
        if ($data->wasChanged('user_pass')) {
            $this->writeMemberEditLog(
                empCode: $admin->code,
                empUser: $admin->user_name,
                mode: 'รหัสผ่าน',
                menu: 'user_pass',
                remark: 'แก้ไขรหัสผ่านลูกค้า',
                member: $data,
                memberUser: $username,
                before: $data->getOriginal('user_pass'),
                after: $data->user_pass
            );
        }

        // สองเคสพิเศษ: balance / balance_free (คงไว้ตามพฤติกรรมเดิม)
        // หากอยากลด log ซ้ำ ให้ยกเลิกส่วนนี้ หรือรวมเข้า main log อย่างเดียว
        if ($data->wasChanged('balance') || $data->wasChanged('balance_free') || $data->wasChanged('bonus') || $data->wasChanged('cashback') || $data->wasChanged('ic') || $data->wasChanged('faststart')) {
            // มีการบันทึก main log ไปแล้วข้างต้นอยู่แล้ว
            // ถ้าต้องการแจ้งเตือน realtime เมื่อแตะ balance ให้ปลดคอมเมนต์ได้
            // DB::afterCommit(fn() => broadcast(new RealTimeMessage('ยอดเงินของ ' . $username . ' ถูกปรับโดย ' . $admin->user_name)));
        } else {

            // แจ้ง real-time ว่ามีการเปลี่ยนข้อมูลสมาชิก (หลังคอมมิต)
            DB::afterCommit(function () use ($data, $admin) {


                broadcast(new RealTimeNewMessage(
                    'มีการเปลียน ข้อมูลสมาชิก ' . $data->user_name . ' โดย ' . $admin->user_name,
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
//                broadcast(new RealTimeMessage('มีการเปลียนข้อมูลสมาชิก ' . $data->user_name . ' โดย ' . $admin->user_name));
            });
        }
    }

    public function deleted(EventData $data): void
    {
        DB::afterCommit(function () use ($data) {
            $this->dispatchDashboardSync($data);
        });

        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return;
        }

        $log = new Log;
        $log->emp_code = $admin->code;
        $log->mode = 'DEL';
        $log->menu = 'members';
        $log->record = $data->code;
        $log->item_before = json_encode($data->getOriginal(), JSON_UNESCAPED_UNICODE);
        $log->item = json_encode($data->getChanges(), JSON_UNESCAPED_UNICODE);
        $log->ip = Request::ip();
        $log->user_create = $admin->user_name;
        $log->save();

        broadcast(new RealTimeNewMessage(
            'มีการลบ ข้อมูลสมาชิก ' . $data->user_name . ' โดย ' . $admin->user_name,
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

    }

    // ─────────────────────────────────────────────────────────────────────────

    private function writeMainLog(int $empCode, string $empName, EventData $data): void
    {
        $log = new Log;
        $log->emp_code = $empCode;
        $log->mode = 'EDIT';
        $log->menu = 'members';
        $log->record = $data->code;
        $log->item_before = json_encode($data->getOriginal(), JSON_UNESCAPED_UNICODE);
        $log->item = json_encode($data->getChanges(), JSON_UNESCAPED_UNICODE);
        $log->ip = Request::ip();
        $log->user_create = $empName;
        $log->save();
    }

    private function writeMemberEditLog(
        int       $empCode,
        string    $empUser,
        string    $mode,
        string    $menu,
        string    $remark,
        EventData $member,
        string    $memberUser,
        string    $before,
        string    $after
    ): void
    {
        $m = new MemberEditLog;
        $m->emp_code = $empCode;
        $m->emp_user = $empUser;
        $m->mode = $mode;
        $m->menu = $menu;
        $m->remark = $remark;
        $m->member_code = $member->code;
        $m->member_user = $memberUser;
        $m->item_before = $before;
        $m->item = $after;
        $m->ip = Request::ip();
        $m->user_create = $empUser;
        $m->save();
    }

    private function maskSecret(string $value): string
    {
        if ($value === '') return '';
        // เช่น แสดงตัวหน้า/ท้ายเล็กน้อย
        $len = mb_strlen($value);
        if ($len <= 4) return str_repeat('*', $len);
        return mb_substr($value, 0, 1) . str_repeat('*', max(0, $len - 2)) . mb_substr($value, -1);
    }

    private function shouldSyncDashboard(EventData $data): bool
    {
        $keys = ['upline_code', 'campaign_id', 'date_regis', 'date_create', 'enable'];
        foreach ($keys as $key) {
            if ($data->wasChanged($key)) {
                return true;
            }
        }

        return false;
    }

    private function dispatchDashboardSync(EventData $data): void
    {
        try {
            app(DashboardSummarySyncService::class)->dispatchForModelChange('register', $data, ['register', 'conversion', 'funnel']);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
