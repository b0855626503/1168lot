<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class RealTimeUpdate implements ShouldBroadcast
{
    use SerializesModels , InteractsWithSockets;

    public $analytics;

    public function __construct()
    {
        $this->analytics = (new \App\Services\RealtimeUpdate())->getUpdate();
    }

    public function broadcastOn()
    {
        return new PrivateChannel(config('app.name') . '_events');
    }


}
