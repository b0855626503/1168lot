<?php

namespace App\Notifications;

use App\Notifications\Messages\SmsMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;


class GetOtp extends Notification
{
    use Queueable;

    public $content;


    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $content)
    {
        $this->content = $content;

    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [SmsChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \App\Notifications\Messages\SmsMessage
     */
    public function toSms($notifiable)
    {
        return (new SmsMessage)->content($this->content)->mobile($notifiable);
    }

//    /**
//     * Get the array representation of the notification.
//     *
//     * @param  mixed  $notifiable
//     * @return array
//     */
//    public function toArray($notifiable)
//    {
//        return [
//            //
//        ];
//    }

}
