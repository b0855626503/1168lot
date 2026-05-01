<?php

namespace Gametech\Lotto\Services\Yeekee\Exceptions;

use RuntimeException;

class YeekeeFormulaInputException extends RuntimeException
{
    public function __construct(
        string $failureCode,
        string $message
    ) {
        parent::__construct($message);
        $this->failureCode = $failureCode;
    }

    private string $failureCode;

    public function failureCode(): string
    {
        return $this->failureCode;
    }
}
