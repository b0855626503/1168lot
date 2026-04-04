<?php

namespace Gametech\FrontendApi\Exceptions;

use RuntimeException;

class RegisterFailureException extends RuntimeException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        private string $userMessage,
        private string $errorCodeValue,
        private array $details = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($userMessage, 0, $previous);
    }

    public function userMessage(): string
    {
        return $this->userMessage;
    }

    public function errorCodeValue(): string
    {
        return $this->errorCodeValue;
    }

    /**
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return $this->details;
    }
}
