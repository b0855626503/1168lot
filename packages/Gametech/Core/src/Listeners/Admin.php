<?php

namespace Gametech\Core\Listeners;

use App\Events\RealTimeMessage;
use App\Events\RealTimeNewMessage;
use Exception;
use Gametech\Admin\Repositories\AdminRepository;
use Gametech\Core\Models\Log;
use Gametech\Member\Repositories\MemberLogRepository;
use Illuminate\Support\Facades\Request;


class Admin
{

    private $adminRepository;

    private $memberLogRepository;

    public function __construct(
        AdminRepository $adminRepository,
        MemberLogRepository $memberLogRepository
    )
    {
        $this->adminRepository = $adminRepository;
        $this->memberLogRepository = $memberLogRepository;
    }

    public function login($user)
    {
        $datetime = now()->toDateTimeString();

        try {

            $this->adminRepository->update(['lastlogin' => now(),'login_session' => Request::ip() ], $user->code);

            $log = new Log;
            $log->emp_code = $user->code;
            $log->mode = 'LOGIN';
            $log->menu = 'employees';
            $log->record = $user->code;
            $log->item_before = '';
            $log->item = json_encode($user);
            $log->ip = Request::ip();
            $log->user_create = $user->user_name;
            $log->save();

//            broadcast(new RealTimeMessage($user->user_name.' เข้าระบบแล้ว เวลา '.$datetime))->toOthers();
            broadcast(new RealTimeNewMessage(
                $user->user_name.' เข้าสู่ระบบ เวลา '.$datetime,
                [
                    'ui' => 'toast',
                    'as' => 'RealTime.Message.All',
                    'toast' => [
                        'className' => 'gt-toast gt-toast-admin',
                        'duration' => 30000,
                        'gravity' => 'top',
                        'position' => 'right',
                        'avatar' => '/assets/admin/icons/admin.webp',
                    ],
                ]
            ))->toOthers();

            $this->memberLogRepository->create([
                'member_code' => $user->code,
                'mode' => 'LOGIN',
                'menu' => 'login',
                'record' => $user->code,
                'remark' => 'Login success',
                'item_before' => '',
                'item' => serialize($user),
                'ip' => request()->ip(),
                'user_create' => $user->name
            ]);


        } catch (Exception $e) {
            report($e);
        }

    }

    public function logout($user)
    {
        try {

            $this->adminRepository->update(['login_session' => ''], $user->code);


            $log = new Log;
            $log->emp_code = $user->code;
            $log->mode = 'LOGOUT';
            $log->menu = 'employees';
            $log->record = $user->code;
            $log->item_before = '';
            $log->item = json_encode($user);
            $log->ip = Request::ip();
            $log->user_create = $user->user_name;
            $log->save();

            $this->memberLogRepository->create([
                'member_code' => $user->code,
                'mode' => 'LOGOUT',
                'menu' => 'login',
                'record' => $user->code,
                'remark' => 'Logout success',
                'item_before' => '',
                'item' => serialize($user),
                'ip' => request()->ip(),
                'user_create' => $user->name
            ]);


        } catch (Exception $e) {
            report($e);
        }

    }




}
