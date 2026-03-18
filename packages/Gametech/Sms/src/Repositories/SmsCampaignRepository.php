<?php

namespace Gametech\Sms\Repositories;

use Gametech\Core\Eloquent\Repository;

class SmsCampaignRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return \Gametech\Sms\Models\SmsCampaign::class;

    }
}
