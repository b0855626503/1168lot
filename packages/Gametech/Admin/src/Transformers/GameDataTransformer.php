<?php

namespace Gametech\Admin\Transformers;



use Gametech\API\Models\GameData;
use League\Fractal\TransformerAbstract;

class GameDataTransformer extends TransformerAbstract
{

    public function transform(GameData $model)
    {

//        dd($model);
        return [
            'productId' => $model['productId'],
            'username' => $model['username'],
            'betStatus' => $model['betStatus'],
            'gameName' => $model['gameName'],
            'stake' => $model['stake'],
            'payoutStatus' => $model['payoutStatus'],
            'payout' => $model['payout'],
            'betId' => $model['betId'],
            'roundId' => $model['roundId'],
            'before_balance' => $model['before_balance'],
            'after_balance' => $model['after_balance'],
            'date_create' => $model['date_create'],
        ];
    }


}
