<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Seamless extends JsonResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
//        dd($this);
        return [
            'username' => $this->username,
            'betStatus' => $this->betStatus,
            'gameName' => $this->gameName,
            'stake' => $this->stake,
            'payoutStatus' => $this->payoutStatus,
            'payout' => $this->payout,
            'updatedDate' => core()->formatDate($this->updatedDate, 'Y-m-d H:i:s'),
        ];
    }
}
