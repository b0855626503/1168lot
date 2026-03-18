<?php

namespace Gametech\Sms\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Sms\DataTables\SmsDeliveryReceiptDataTable;

class SmsDeliveryReceiptController extends AppBaseController
{
    protected $_config;

    protected $repository;


    public function __construct()
    {
        $this->_config = request('_config');

        $this->middleware('admin');

    }

    public function index(SmsDeliveryReceiptDataTable $dataTable)
    {
        return $dataTable->render($this->_config['view']);
    }
}
