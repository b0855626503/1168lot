<?php

namespace Gametech\Admin\Notifications;


use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class AlertNotice extends Notification implements ShouldBroadcast
{
    public $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function via($notifiable): array
    {
        return ['broadcast'];
    }

    public function broadcastType()
    {
        return 'broadcast.message';
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => "$this->message (User $notifiable->id)"
        ]);
    }
}
