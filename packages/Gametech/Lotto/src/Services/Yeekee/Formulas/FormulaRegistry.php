<?php

namespace Gametech\Lotto\Services\Yeekee\Formulas;

use Gametech\Lotto\Services\Yeekee\Formulas\Contracts\YeekeeFormula;
use Gametech\Lotto\Services\Yeekee\Formulas\Presets\ShootsSumMinusPositionFormula;
use Gametech\Lotto\Services\Yeekee\Formulas\Presets\YeekeeSumOnlyFormula;
use InvalidArgumentException;

class FormulaRegistry
{
    /** @var array<string,YeekeeFormula> */
    private array $formulas;

    public function __construct()
    {
        $default = new ShootsSumMinusPositionFormula;
        $sumOnly = new YeekeeSumOnlyFormula;
        $this->formulas = [
            $default->key() => $default,
            $sumOnly->key() => $sumOnly,
        ];
    }

    public function resolve(string $key): YeekeeFormula
    {
        $normalized = trim($key);
        if ($normalized === '' || ! isset($this->formulas[$normalized])) {
            throw new InvalidArgumentException('Unsupported yeekee formula preset: '.$normalized);
        }

        return $this->formulas[$normalized];
    }
}
