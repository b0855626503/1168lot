<?php

namespace Gametech\Lotto\Services\InternalResultSources\Contracts;

interface InternalResultSourceDriver
{
    public function sourceKey(): string;

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function fetch(array $params): array;
}

