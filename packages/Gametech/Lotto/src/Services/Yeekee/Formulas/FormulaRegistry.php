<?php

namespace Gametech\Lotto\Services\Yeekee\Formulas;

use Gametech\Lotto\Services\Yeekee\Formulas\Contracts\YeekeeFormula;
use Gametech\Lotto\Services\Yeekee\Formulas\Presets\ShootsSumMinusPositionFormula;
use InvalidArgumentException;

class FormulaRegistry
{
    /** @var array<string,YeekeeFormula> */
    private array $formulas;

    public function __construct()
    {
        $default = new ShootsSumMinusPositionFormula;
        $this->formulas = [
            $default->key() => $default,
        ];
    }

    public function resolve(string $key): YeekeeFormula
    {
        $normalized = trim($key);
        if ($normalized === '' || ! isset($this->formulas[$normalized])) {
            throw new InvalidArgumentException('ไม่รองรับสูตรที่ระบุ');
        }

        return $this->formulas[$normalized];
    }
}
