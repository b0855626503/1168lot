<?php

namespace Gametech\Auto\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentOutFree
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $items;

    /**
     * Create a new event instance.
     *
     * @param $items
     */
    public function __construct($items)
    {
//        dd($items);
        $this->items = $items;
    }


}
