<?php

namespace Gametech\Lotto\Services\InternalResultSources;

use Gametech\Lotto\Services\InternalResultSources\Contracts\InternalResultSourceDriver;
use Gametech\Lotto\Services\InternalResultSources\Drivers\DowjonesExtraResultDriver;
use Gametech\Lotto\Services\InternalResultSources\Drivers\DowjonesMidnightResultDriver;
use Gametech\Lotto\Services\InternalResultSources\Drivers\ExpalertResultDriver;
use Gametech\Lotto\Services\InternalResultSources\Drivers\ExphuayResultDriver;
use InvalidArgumentException;

class InternalResultSourceResolver
{
    /**
     * @var array<string,InternalResultSourceDriver>
     */
    private array $drivers;

    public function __construct(
        ExphuayResultDriver $exphuay,
        DowjonesMidnightResultDriver $dowjonesMidnight,
        DowjonesExtraResultDriver $dowjonesExtra,
        ExpalertResultDriver $expalert
    ) {
        $this->drivers = [
            $exphuay->sourceKey() => $exphuay,
            $dowjonesMidnight->sourceKey() => $dowjonesMidnight,
            $dowjonesExtra->sourceKey() => $dowjonesExtra,
            $expalert->sourceKey() => $expalert,
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function resolve(string $source): InternalResultSourceDriver
    {
        $key = trim($source);
        if (! array_key_exists($key, $this->drivers)) {
            throw new InvalidArgumentException('Unsupported internal result source: ' . $source);
        }

        return $this->drivers[$key];
    }
}

