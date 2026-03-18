<?php

namespace Gametech\Marketing\Http\Controllers\Admin;

use App\Http\Controllers\AppBaseController;
use Gametech\Marketing\DataTables\MarketingMemberAllDataTable;
use Gametech\Marketing\DataTables\MarketingMemberDataTable;
use Gametech\Marketing\DataTables\MarketingTeamDataTable;
use Gametech\Marketing\Repositories\MarketingMemberRepository;
use Gametech\Marketing\Repositories\MarketingTeamRepository;
use Gametech\Marketing\Repositories\RegistrationLinkRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MarketingMemberAllController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $registrationLinkRepository;

    public function __construct(MarketingMemberRepository $repository, RegistrationLinkRepository $registrationLinkRepository)
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->registrationLinkRepository = $registrationLinkRepository;

    }

    public function index(MarketingMemberAllDataTable $marketingMemberAllDataTable)
    {
        return $marketingMemberAllDataTable->render($this->_config['view']);
    }

}
