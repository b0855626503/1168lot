<?php

namespace Gametech\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;



class MemberIc implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;

    public $tries = 1;

    public $maxExceptions = 3;

    public $retryAfter = 3;

    protected $item;

    protected $date;

    public function __construct($date, $item)
    {
        $this->date = $date;
        $this->item = $item;
    }


    public function handle()
    {
        $item = $this->item;
        $this->memberIcRepository = app('Gametech\Member\Repositories\MemberIcRepository');


        $data = [
            'upline_code' => $item->upline_code,
            'member_code' => $item->member_code,
            'balance' => $item->balance_total,
            'ic' => (($item->balance_total * $item->bonus) / 100),
            'date_cashback' => $item->date_cashback,
            'ip' => $item->ip,
            'emp_code' => $item->emp_code,
            'emp_name' => $item->emp_name,
        ];

        return $this->memberIcRepository->refill($data);


    }
}
