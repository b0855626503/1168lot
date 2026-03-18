<?php

namespace Gametech\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class NewMemberCashback implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uniqueFor = 10;

    public $timeout = 60;

    public $tries = 0;

    public $maxExceptions = 3;

//    public $retryAfter = 70;

    protected $item;

    protected $date;

    public function __construct($date, $item)
    {
        $this->date = $date;
        $this->item = $item;
    }

    public function tags()
    {
        return ['render', 'cashback:'.$this->item->code];
    }

    public function uniqueId()
    {
        return $this->item->code;
    }


    public function handle()
    {
        $item = $this->item;
        $this->memberCashbackRepository = app('Gametech\Member\Repositories\MemberCashbackRepository');



        $data = [
            'code' => $item->code,
            'upline_code' => $item->member_code,
            'member_code' => $item->downline_code,
            'balance' => $item->balance,
            'cashback' => $item->cashback,
            'date_cashback' => $item->date_cashback,
            'ip' => $item->ip_admin,
            'emp_code' => $item->emp_code,
            'emp_name' => $item->user_create,
        ];

        return $this->memberCashbackRepository->refill($data);


    }
}
