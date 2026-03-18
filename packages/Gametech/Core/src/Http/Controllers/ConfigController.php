<?php

namespace Gametech\Core\Http\Controllers;

use App\Http\Controllers\AppBaseController;
use Gametech\Core\Repositories\ConfigRepository;
use Illuminate\View\View;


class ConfigController extends AppBaseController
{

    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    private $configRepository;

    /**
     * Create a new controller instance.
     *
     * @param ConfigRepository $configRepo
     */
    public function __construct
    (
        ConfigRepository $configRepo
    )
    {
        $this->_config = request('_config');

        $this->configRepository = $configRepo;
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        return view($this->_config['view']);
    }
}
