<?php

namespace Gametech\Member\Repositories;

use Gametech\Core\Eloquent\Repository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Throwable;

class MemberCheckinRepository extends Repository
{
    use ActivityLoggerUser;

    private $memberRepository;

    private $gameUserRepository;

    public function __construct
    (
        MemberRepository $memberRepo,
        GameUserRepository $gameUserRepo,
        App $app
    )
    {
        $this->memberRepository = $memberRepo;
        $this->gameUserRepository = $gameUserRepo;
        parent::__construct($app);
    }

    /**
     * Specify Model class name
     *
     * @return string
     */
    function model(): string
    {
        return \Gametech\Member\Models\MemberCheckin::class;

    }


}
