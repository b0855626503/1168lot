<?php

namespace Gametech\Member\Repositories;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Str;

class MemberOtpRepository extends Repository
{

    protected $memberRepository;

    public function __construct
    (
        MemberRepository $memberRepo,
        App              $app
    )
    {
        $this->memberRepository = $memberRepo;
        parent::__construct($app);
    }

    public function getOtp($mobile)
    {
        return $this->create([
            'confirm' => 'N',
            'mobile' => $mobile,
            'refer' => Str::random(5),
            'otp' => mt_rand(10000, 99999),
            'expired_at' => now()->addMinutes(3)
        ]);
    }

    public function getlatest($mobile)
    {
        return $this->model->last($mobile);
    }


    /**
     * Specify Model class name
     *
     * @return string
     */
    function model(): string
    {
        return \Gametech\Member\Models\MemberOtp::class;

    }
}
