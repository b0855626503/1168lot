<?php

namespace Gametech\Promotion\Repositories;

use Gametech\Core\Eloquent\Repository;

class PromotionAmountRepository extends Repository
{

    public function promotion($pro_code,$amount)
    {

        $result = $this->orderBy('code','desc')->where('pro_code',$pro_code)->active()->where('deposit_amount',$amount);
        if($result->exists()){
            return ['amount' => ($result->value('amount') * 1) ];
        }
        return ['amount' => 0 ];
    }

    public function promotionBetween($pro_code,$amount)
    {

        $result = $this->orderBy('code','desc')->where('pro_code',$pro_code)->active()->whereRaw("? between deposit_amount and deposit_stop",[$amount]);

        if($result->exists()){
            return ['amount' => ($result->value('amount') * 1) ];
        }
        return ['amount' => 0 ];
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return \Gametech\Promotion\Models\PromotionAmount::class;

    }
}
