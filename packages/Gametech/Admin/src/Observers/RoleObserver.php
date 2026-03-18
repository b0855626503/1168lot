<?php

namespace Gametech\Admin\Observers;

use Gametech\Admin\Models\Role as EventData;
use Gametech\Core\Models\Log;
use Gametech\LogAdmin\Http\Traits\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class RoleObserver
{
    use ActivityLogger;

    public function created(EventData $data): void
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) return;

        $log = new Log;
        $log->emp_code    = $admin->code;
        $log->mode        = 'ADD';
        $log->menu        = 'roles';
        $log->record      = $data->code;
        // ก่อนสร้างจะไม่มีค่าเดิม
        $log->item_before = json_encode($data->getOriginal(),   JSON_UNESCAPED_UNICODE);
        // หลังสร้าง เก็บสถานะปัจจุบันทั้งหมดของโมเดล
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
        $log->menu        = 'roles';
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
        $log->menu        = 'roles';
        $log->record      = $data->code;
        $log->item_before = json_encode($data->getOriginal(), JSON_UNESCAPED_UNICODE);
        $log->item        = json_encode($data->getChanges(),  JSON_UNESCAPED_UNICODE);
        $log->ip          = Request::ip();
        $log->user_create = $admin->user_name;
        $log->save();
    }
}
