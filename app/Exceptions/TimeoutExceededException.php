<?php
// app/Exceptions/TimeoutExceededException.php

namespace App\Exceptions;

use RuntimeException;

class TimeoutExceededException extends RuntimeException
{
    public function __construct(float $limitSeconds, float $elapsed)
    {
        parent::__construct(sprintf(
            'Processing time exceeded %.3f seconds (elapsed: %.3f s)',
            $limitSeconds, $elapsed
        ));
    }
}
