<?php

namespace Gametech\Lotto\Services\AutoResult;

class ResultParseContext
{
    public function __construct(
        public readonly ?string $expectedDrawDate = null
    ) {
    }
}
