<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Models\YeekeeShoot;
use Gametech\Lotto\Services\Yeekee\Formulas\FormulaRegistry;

class YeekeeResultEngineService
{
    public function __construct(
        private FormulaRegistry $formulaRegistry
    ) {}

    /**
     * @return array{raw_result:string,top_3:string,bottom_2:string}
     */
    public function computeFromRound(int $roundId): array
    {
        $round = YeekeeRound::query()->findOrFail($roundId);
        $snapshot = is_array($round->config_snapshot_json) ? $round->config_snapshot_json : [];
        $formulaConfig = is_array($snapshot['formula_config'] ?? null) ? $snapshot['formula_config'] : [];
        $formulaKey = (string) ($formulaConfig['preset'] ?? 'SHOOTS_SUM_MINUS_POSITION');

        $shoots = YeekeeShoot::query()
            ->where('yeekee_round_id', $roundId)
            ->orderBy('position')
            ->get(['position', 'number_text', 'number_value'])
            ->map(static function ($row): array {
                return [
                    'position' => (int) $row->position,
                    'number_text' => (string) $row->number_text,
                    'number_value' => (int) $row->number_value,
                ];
            })
            ->all();

        $formula = $this->formulaRegistry->resolve($formulaKey);

        return $formula->compute($shoots, $formulaConfig);
    }
}
