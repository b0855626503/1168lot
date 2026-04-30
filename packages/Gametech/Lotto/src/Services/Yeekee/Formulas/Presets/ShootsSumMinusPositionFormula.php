<?php

namespace Gametech\Lotto\Services\Yeekee\Formulas\Presets;

use Gametech\Lotto\Services\Yeekee\Formulas\Contracts\YeekeeFormula;
use InvalidArgumentException;

class ShootsSumMinusPositionFormula implements YeekeeFormula
{
    public function key(): string
    {
        return 'SHOOTS_SUM_MINUS_POSITION';
    }

    public function compute(array $shoots, array $config): array
    {
        $subtractPosition = (int) ($config['subtract_position'] ?? 16);
        if ($subtractPosition <= 0) {
            throw new InvalidArgumentException('subtract_position ต้องมากกว่า 0');
        }

        $sumAll = 0;
        $subtractValue = null;

        foreach ($shoots as $shoot) {
            $value = (int) ($shoot['number_value'] ?? 0);
            $sumAll += $value;
            if ((int) ($shoot['position'] ?? 0) === $subtractPosition) {
                $subtractValue = $value;
            }
        }

        if ($subtractValue === null) {
            throw new InvalidArgumentException('ไม่พบเลขยิงในตำแหน่งที่ใช้ลบ');
        }

        $baseResult = abs($sumAll - $subtractValue);
        $rawResult = str_pad((string) ($baseResult % 100000), 5, '0', STR_PAD_LEFT);

        return [
            'raw_result' => $rawResult,
            'top_3' => substr($rawResult, -3),
            'bottom_2' => substr($rawResult, 0, 2),
        ];
    }
}
