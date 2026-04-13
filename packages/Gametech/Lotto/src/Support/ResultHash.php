<?php

namespace Gametech\Lotto\Support;

class ResultHash
{
    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromPayload(array $payload): string
    {
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
