<?php

namespace Gametech\Lotto\Services\Yeekee\Exceptions;

use RuntimeException;

class YeekeeShootCooldownException extends RuntimeException
{
    public function __construct(
        int $cooldownSeconds,
        int $remainingCooldownSeconds,
        string $nextAllowedAt,
        string $message = 'กรุณารอก่อนยิงเลขครั้งถัดไป'
    ) {
        parent::__construct($message);
        $this->cooldownSeconds = $cooldownSeconds;
        $this->remainingCooldownSeconds = $remainingCooldownSeconds;
        $this->nextAllowedAt = $nextAllowedAt;
    }

    private int $cooldownSeconds;

    private int $remainingCooldownSeconds;

    private string $nextAllowedAt;

    public function cooldownSeconds(): int
    {
        return $this->cooldownSeconds;
    }

    public function remainingCooldownSeconds(): int
    {
        return $this->remainingCooldownSeconds;
    }

    public function nextAllowedAt(): string
    {
        return $this->nextAllowedAt;
    }
}
