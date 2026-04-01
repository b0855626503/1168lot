<?php

namespace Gametech\Lotto\Exceptions;

use RuntimeException;

class LottoPackageException extends RuntimeException
{
    private string $errorCode;
    private int $httpStatus;

    public function __construct(string $errorCode, string $message, int $httpStatus)
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
        $this->httpStatus = $httpStatus;
    }

    public static function packageRequired(): self
    {
        return new self('PACKAGE_REQUIRED', 'ต้องระบุ package_id', 400);
    }

    public static function packageNotInGroup(): self
    {
        return new self('PACKAGE_NOT_IN_GROUP', 'package ไม่อยู่ใน group เดียวกับ draw', 400);
    }

    public static function packageInactive(): self
    {
        return new self('PACKAGE_INACTIVE', 'package ถูกปิดใช้งาน', 409);
    }

    public static function betTypeNotConfigured(string $betType): self
    {
        return new self('BET_TYPE_NOT_CONFIGURED', "package ไม่มีการตั้งค่าสำหรับ bet_type {$betType}", 422);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}

