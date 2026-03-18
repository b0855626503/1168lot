<?php

namespace Gametech\API\Traits;

use Gametech\API\Models\GameData;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\UTCDateTime;

trait LogSeamlessOld
{
    public static function log($product, $username, $item, $beforebalance, $afterbalance,$update=true)
    {
        try {
            $date = 5;

            $stake = Arr::get($item, 'betAmount', 0);
            $payout = Arr::get($item, 'payoutAmount', 0);
            $isSingleState = Arr::get($item, 'isSingleState', false);
            $skipBalanceUpdate = Arr::get($item, 'skipBalanceUpdate', false);
            $transactionType = strtoupper(Arr::get($item, 'transactionType', 'UNKNOWN'));
            $productId = $product;

            $betId = Arr::get($item, 'id', Arr::get($item, 'refId', ''));
            $roundId = Arr::get($item, 'roundId', Arr::get($item, 'refId', ''));

            if (isset($item['amount'], $item['status'])) {
                if (strtoupper($item['status']) === 'DEBIT') {
                    $stake = $item['amount'];
                } else {
                    $payout = $item['amount'];
                }
            }

            $gameName = Arr::get($item, 'playInfo', '');
            $betStatus = Arr::get($item, 'status', 'UNKNOWN');

            $date_create = now()->toDateTimeString();
            $expireAt = new UTCDateTime(now()->addDays($date));

            if (empty($betId) || empty($roundId)) {
                Log::warning('Invalid game data log: missing betId or roundId', compact('productId', 'username', 'item'));

                return;
            }

            $common = [
                'productId' => $product,
                'username' => $username,
                'gameName' => $gameName,
                'date_create' => $date_create,
                'expireAt' => $expireAt,
                'betStatus' => $betStatus,
                'isSingleState' => $isSingleState,
                'skipBalanceUpdate' => $skipBalanceUpdate,
                'betId' => $betId,
                'roundId' => $roundId,

            ];

            if ($isSingleState) {

                if ($transactionType === 'BY_ROUND') {
                    $where = ['productId' => $productId, 'username' => $username, 'roundId' => $roundId];
                    $common['betId'] = $betId;
                    $common['stake'] = $stake;
                    $common['payout'] = $payout;
                    $common['before_balance'] = $beforebalance;
                    $common['after_balance'] = $afterbalance;
                } elseif ($transactionType === 'UNKNOWN') {
                    $where = ['productId' => $productId, 'username' => $username, 'betId' => $betId, 'roundId' => $roundId];
                    $common['stake'] = $stake;
                    $common['payout'] = $payout;
                    $common['before_balance'] = $beforebalance;
                    $common['after_balance'] = $afterbalance;
                } else {
                    $where = ['productId' => $productId, 'username' => $username, 'betId' => $betId];
                    $common['roundId'] = $roundId;
                    $common['stake'] = $stake;
                    $common['payout'] = $payout;
                    $common['before_balance'] = $beforebalance;
                    $common['after_balance'] = $afterbalance;
                }
            } else {

                if ($transactionType === 'BY_ROUND') {
                    $where = ['productId' => $productId, 'username' => $username, 'roundId' => $roundId];
                    $common['betId'] = $betId;
                } elseif ($transactionType === 'UNKNOWN') {
                    $where = ['productId' => $productId, 'username' => $username, 'betId' => $betId, 'roundId' => $roundId];
                } else {
                    $where = ['productId' => $productId, 'username' => $username, 'betId' => $betId];
                    $common['roundId'] = $roundId;
                }
                if ($betStatus === 'OPEN') {
                    $common['stake'] = $stake;
                    $common['payout'] = $payout;
                    $common['before_balance'] = $beforebalance;
                    $common['after_balance'] = $afterbalance;
                } elseif ($betStatus === 'SETTLED') {
                    if ($stake > 0) {
                        $common['stake'] = $stake;
                        $common['before_balance'] = $beforebalance;
                    }
                    $common['payout'] = $payout;
                    $common['after_balance'] = $afterbalance;
                } else {

                    $common['stake'] = $stake;
                    $common['payout'] = $payout;
                    $common['before_balance'] = $beforebalance;
                    $common['after_balance'] = $afterbalance;
                }
            }

            if($update){
                GameData::updateOrCreate($where, $common);
            }else{
                GameData::create($common);
            }


        } catch (\Exception $e) {
            Log::error('LogSeamless failed', [
                'error' => $e->getMessage(),
                'product' => $product,
                'username' => $username,
                'item' => $item,
            ]);
        }
    }
}
