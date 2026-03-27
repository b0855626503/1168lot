<?php

namespace Gametech\Lotto\Services\AutoResult;

use Gametech\Lotto\Exceptions\ResultValidationException;

class ResultValidator
{
    /**
     * @param array<string,mixed> $mapped
     * @return array{first_prize:string,last_2_digits:string}
     */
    public function validate(array $mapped): array
    {
        $firstPrize = preg_replace('/\D+/', '', (string) ($mapped['first_prize'] ?? ''));
        $last2Digits = preg_replace('/\D+/', '', (string) ($mapped['last_2_digits'] ?? ''));

        if ($firstPrize === '' || $last2Digits === '') {
            throw new ResultValidationException('ผลออกยังไม่พร้อม (NOT_READY)');
        }

        if (! in_array(strlen($firstPrize), [5, 6], true)) {
            throw new ResultValidationException('VALIDATION_ERROR: first_prize ต้องมี 5 หรือ 6 หลัก');
        }

        if (strlen($last2Digits) !== 2) {
            throw new ResultValidationException('VALIDATION_ERROR: last_2_digits ต้องมี 2 หลัก');
        }

        return [
            'first_prize' => $firstPrize,
            'last_2_digits' => $last2Digits,
        ];
    }
}
