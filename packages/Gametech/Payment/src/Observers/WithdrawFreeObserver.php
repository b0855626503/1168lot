<?php


namespace Gametech\Payment\Observers;



use App\Events\SumNewWithdraw;
use Gametech\Core\Models\Log;
use Gametech\LogAdmin\Http\Traits\ActivityLogger;
use Gametech\Payment\Models\WithdrawFree as EventData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class WithdrawFreeObserver
{
    use ActivityLogger;

    public function created(EventData $data)
    {

        $userId = 0;
        $userName = '';
        if (Auth::guard('customer')->check()) {
            $userId = Request::user('customer')->code;
            $userName = Request::user('customer')->user_name;
        }

        if ($userId > 0) {
            $log = new Log;
            $log->emp_code = $userId;
            $log->mode = 'ADD';
            $log->menu = 'withdraws_free';
            $log->record = $data->code;
            $log->item_before = json_encode($data->getOriginal());
            $log->item = json_encode($data->getChanges());
            $log->ip = Request::ip();
            $log->user_create = $userName;
            $log->save();
        }

        $withdraw = app('Gametech\Payment\Repositories\WithdrawFreeRepository')
            ->active()->waiting()
            ->count();

        broadcast(new SumNewWithdraw($withdraw,'up'));



    }

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
            $log->menu = 'withdraws_free';
            $log->record = $data->code;
            $log->item_before = json_encode($data->getOriginal());
            $log->item = json_encode($data->getChanges());
            $log->ip = Request::ip();
            $log->user_create = $userName;
            $log->save();
        }

        $withdraw = app('Gametech\Payment\Repositories\WithdrawFreeRepository')
            ->active()->waiting()
            ->count();

        broadcast(new SumNewWithdraw($withdraw,'down'));
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
            $log->menu = 'withdraws_free';
            $log->record = $data->code;
            $log->item_before = json_encode($data->getOriginal());
            $log->item = json_encode($data->getChanges());
            $log->ip = Request::ip();
            $log->user_create = $userName;
            $log->save();

        }

        $withdraw = app('Gametech\Payment\Repositories\WithdrawFreeRepository')
            ->active()->waiting()
            ->count();

        broadcast(new SumNewWithdraw($withdraw,'down'));
//        ActivityLogger::activitie('ลบข้อมูล รายการที่ ' . $data->code, json_encode($logs));

    }
}
