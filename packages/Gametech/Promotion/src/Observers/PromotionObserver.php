<?php

namespace Gametech\Promotion\Observers;

use Gametech\Core\Models\Log;
use Gametech\LogAdmin\Http\Traits\ActivityLogger;
use Gametech\Promotion\Models\Promotion as EventData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class PromotionObserver
{
    use ActivityLogger;

    public function created(EventData $data): void
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) return;

        $log = new Log;
        $log->emp_code    = $admin->code;
        $log->mode        = 'ADD';
        $log->menu        = 'promotions';
        $log->record      = $data->code;
        // ก่อนสร้างยังไม่มีค่าเดิม
        $log->item_before = json_encode($data->getOriginal(),   JSON_UNESCAPED_UNICODE);
        // หลังสร้าง เก็บแอตทริบิวต์ปัจจุบันทั้งหมด
        $log->item        = json_encode($data->getAttributes(), JSON_UNESCAPED_UNICODE);
        $log->ip          = Request::ip();
        $log->user_create = $admin->user_name;
        $log->save();
    }

    public function updated(EventData $data): void
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) return;

        $log = new Log;
        $log->emp_code    = $admin->code;
        $log->mode        = 'EDIT';
        $log->menu        = 'promotions';
        $log->record      = $data->code;
        $log->item_before = json_encode($data->getOriginal(), JSON_UNESCAPED_UNICODE);
        $log->item        = json_encode($data->getChanges(),  JSON_UNESCAPED_UNICODE);
        $log->ip          = Request::ip();
        $log->user_create = $admin->user_name;
        $log->save();
    }

    public function deleted(EventData $data): void
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) return;

        $log = new Log;
        $log->emp_code    = $admin->code;
        $log->mode        = 'DEL';
        $log->menu        = 'promotions';
        $log->record      = $data->code;
        $log->item_before = json_encode($data->getOriginal(), JSON_UNESCAPED_UNICODE);
        $log->item        = json_encode($data->getChanges(),  JSON_UNESCAPED_UNICODE);
        $log->ip          = Request::ip();
        $log->user_create = $admin->user_name;
        $log->save();
    }
}
