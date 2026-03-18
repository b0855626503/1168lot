<?php

namespace Gametech\Sms\Repositories;

use Gametech\Core\Eloquent\Repository;

class SmsRecipientRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return \Gametech\Sms\Models\SmsRecipient::class;

    }
}
