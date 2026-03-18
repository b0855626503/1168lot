<?php

namespace App\Notifications;

use App\Libraries\ThSms;
use App\Notifications\Messages\SmsMessage;
use Illuminate\Notifications\Messages\NexmoMessage;
use Illuminate\Notifications\Notification;

class SmsChannel
{

    protected $thsms;

//    protected $mobile;

    public function __construct(ThSms $thsms)
    {
//        $this->mobile = $mobile;
        $this->thsms = $thsms;
    }

    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toSms($notifiable);
//        dd($message);

        if (is_string($message)) {
            $message = new SmsMessage($message);
        }

        $this->thsms->sendSms($notifiable,$message->content);

        // Send notification to the $notifiable instance...
    }
}
