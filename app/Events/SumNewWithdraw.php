<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SumNewWithdraw implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sum;

    public $type;

    public $code;

    public function __construct($sum, $type, $code)
    {
        $this->sum = $sum;
        $this->type = $type;
        $this->code = $code;
    }

    public function broadcastOn()
    {
        return new Channel(config('app.name') . '_events');
    }


}
