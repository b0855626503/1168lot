<?php

namespace Gametech\Admin\Http\Controllers;

use Gametech\Admin\DataTables\MemberLogDataTable;
use Gametech\Admin\DataTables\PromotionDataTable;
use Gametech\Promotion\Repositories\PromotionAmountRepository;
use Gametech\Promotion\Repositories\PromotionRepository;
use Gametech\Promotion\Repositories\PromotionTimeRepository;
use Illuminate\Http\Request;


class MemberLogController extends AppBaseController
{
    protected $_config;
    public function __construct
    (


    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');



    }


    public function index(MemberLogDataTable $memberLogDataTable)
    {
        return $memberLogDataTable->render($this->_config['view']);
    }


}
