<?php

namespace Gametech\Lotto\Services\InternalResultSources;

use Carbon\Carbon;
use InvalidArgumentException;

class DateInputNormalizer
{
    /**
     * @throws InvalidArgumentException
     */
    public function normalize(?string $input): Carbon
    {
        $value = trim((string) $input);
        if ($value === '') {
            return now()->startOfDay();
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $this->parseExact($value, 'Y-m-d');
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value) === 1) {
            return $this->parseExact($value, 'd/m/Y');
        }

        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value) === 1) {
            return $this->parseExact($value, 'd-m-Y');
        }

        throw new InvalidArgumentException('Unsupported date format. Supported: Y-m-d, d/m/Y, d-m-Y');
    }

    /**
     * @throws InvalidArgumentException
     */
    private function parseExact(string $input, string $format): Carbon
    {
        try {
            $date = Carbon::createFromFormat('!' . $format, $input);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Invalid date value: ' . $input);
        }

        if (! $date instanceof Carbon || $date->format($format) !== $input) {
            throw new InvalidArgumentException('Invalid date value: ' . $input);
        }

        return $date->startOfDay();
    }
}

