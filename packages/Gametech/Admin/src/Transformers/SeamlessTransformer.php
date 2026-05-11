<?php

namespace Gametech\Admin\Transformers;

use Carbon\Carbon;
use League\Fractal\TransformerAbstract;

class SeamlessTransformer extends TransformerAbstract
{
    protected $no;

    public function __construct($no = 1)
    {
        $this->no = $no;

    }

    public function transform(array $model)
    {
        $accountingDate = $model['accountingDate'] ?? null;
        $updatedDate = $model['updatedDate'] ?? null;

        return [
            'code' => ++$this->no,
            'id' => $model['id'] ?? null,
            'betId' => $model['betId'] ?? null,
            'username' => $model['username'] ?? null,
            'currency' => $model['currency'] ?? null,
            'accountingDate' => $this->formatDateTime($accountingDate),
            'updatedDate' => $this->formatDateTime($updatedDate),
            'stake' => $model['stake'] ?? 0,
            'payout' => $model['payout'] ?? 0,
            'productId' => $model['productId'] ?? null,
            'gameCode' => $model['gameCode'] ?? null,
            'gameName' => $model['gameName'] ?? null,
            'roundId' => $model['roundId'] ?? null,
            'betStatus' => $model['betStatus'] ?? null,
            'payoutStatus' => $model['payoutStatus'] ?? null,

        ];
    }

    private function formatDateTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::createFromTimestampMs((int) $value, 'Asia/Bangkok')->format('Y-m-d H:i:s');
            }

            return Carbon::parse((string) $value)->setTimezone('Asia/Bangkok')->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}
