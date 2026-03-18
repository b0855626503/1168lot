<?php


namespace Gametech\Core\Observers;


use Gametech\Core\Models\Log;
use Gametech\LogAdmin\Http\Traits\ActivityLogger;
use Gametech\Core\Models\Config as EventData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ConfigObserver
{
    use ActivityLogger;

    public function updated(EventData $data)
    {
        $userId = 0;
        $userName = '';
        if (Auth::guard('admin')->check()) {
            $userId = Request::user('admin')->code;
            $userName = Request::user('admin')->user_name;
        }

        if ($userId > 0) {
            $log = new Log;
            $log->emp_code = $userId;
            $log->mode = 'EDIT';
            $log->menu = 'configs';
            $log->record = $data->code;
            $log->item_before = json_encode($data->getOriginal());
            $log->item = json_encode($data->getChanges());
            $log->ip = Request::ip();
            $log->user_create = $userName;
            $log->save();
        }
//        ActivityLogger::activitie('แก้ไขข้อมูล รายการที่ ' . $data->code, json_encode($logs));

    }


    public function deleted(EventData $data)
    {
        $userId = 0;
        $userName = '';
        if (Auth::guard('admin')->check()) {
            $userId = Request::user('admin')->code;
            $userName = Request::user('admin')->user_name;
        }

        if ($userId > 0) {
            $log = new Log;
            $log->emp_code = $userId;
            $log->mode = 'DEL';
            $log->menu = 'configs';
            $log->record = $data->code;
            $log->item_before = json_encode($data->getOriginal());
            $log->item = json_encode($data->getChanges());
            $log->ip = Request::ip();
            $log->user_create = $userName;
            $log->save();
        }
//        ActivityLogger::activitie('ลบข้อมูล รายการที่ ' . $data->code, json_encode($logs));

    }
}
