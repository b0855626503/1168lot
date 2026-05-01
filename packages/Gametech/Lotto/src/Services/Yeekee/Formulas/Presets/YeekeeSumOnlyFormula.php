<?php

namespace Gametech\Lotto\Services\Yeekee\Formulas\Presets;

use Gametech\Lotto\Services\Yeekee\Exceptions\YeekeeFormulaInputException;
use Gametech\Lotto\Services\Yeekee\Formulas\Contracts\YeekeeFormula;
use InvalidArgumentException;

class YeekeeSumOnlyFormula implements YeekeeFormula
{
    public function key(): string
    {
        return 'SHOOTS_SUM_ONLY';
    }

    public function compute(array $shoots, array $config): array
    {
        $modulo = (int) ($config['modulo'] ?? 100000);
        if ($modulo <= 0) {
            throw new InvalidArgumentException('FORMULA_CONFIG_INVALID: modulo ต้องมากกว่า 0');
        }

        if ($shoots === []) {
            throw new YeekeeFormulaInputException(
                'FORMULA_INPUT_INSUFFICIENT',
                'ไม่มีข้อมูลเลขยิง'
            );
        }

        $total = (int) array_sum(array_column($shoots, 'number_value'));
        $rawResult = str_pad((string) ($total % $modulo), 5, '0', STR_PAD_LEFT);

        return [
            'raw_result' => $rawResult,
            'top_3' => substr($rawResult, -3),
            'bottom_2' => substr($rawResult, 0, 2),
        ];
    }
}
