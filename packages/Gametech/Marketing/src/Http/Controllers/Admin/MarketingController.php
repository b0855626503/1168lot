<?php

namespace Gametech\Marketing\Http\Controllers\Admin;

use App\Http\Controllers\AppBaseController;
use Gametech\Marketing\Repositories\MarketingCampaignRepository;


class MarketingController extends AppBaseController
{
    protected $_config;

    protected $marketingCampaignRepository;

    public function __construct(
        MarketingCampaignRepository $marketingCampaignRepository
    ) {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->marketingCampaignRepository = $marketingCampaignRepository;

    }


    public function index()
    {
        return view('marketing::admin.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('marketing::admin.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        return view('marketing::admin.edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(int $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $id)
    {
        
    }
}
