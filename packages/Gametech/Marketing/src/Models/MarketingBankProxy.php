<?php

namespace Gametech\Marketing\Models;

use Gametech\Payment\Models\BankProxy;

class MarketingBankProxy extends BankProxy
{

    public function marketingTeams()
    {
        return $this->hasMany(MarketingTeamProxy::modelClass(), 'bank_code', 'code');
    }
}
