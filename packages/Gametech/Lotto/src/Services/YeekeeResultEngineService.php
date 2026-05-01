<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Models\YeekeeShoot;
use Gametech\Lotto\Services\Yeekee\Formulas\FormulaRegistry;
use Gametech\Lotto\Services\Yeekee\Seed\ExternalSeedResolverService;
use Illuminate\Support\Facades\Log;

class YeekeeResultEngineService
{
    public function __construct(
        private FormulaRegistry $formulaRegistry,
        private ExternalSeedResolverService $seedResolver
    ) {}

    /**
     * @return array{raw_result:string,top_3:string,bottom_2:string}
     */
    public function computeFromRound(int $roundId): array
    {
        $round = YeekeeRound::query()->findOrFail($roundId);
        $snapshot = is_array($round->config_snapshot_json) ? $round->config_snapshot_json : [];
        $formulaConfig = is_array($snapshot['formula_config'] ?? null) ? $snapshot['formula_config'] : [];
        $formulaKey = trim((string) ($formulaConfig['preset'] ?? ''));
        if ($formulaKey === '') {
            $formulaKey = 'SHOOTS_SUM_MINUS_POSITION';
            Log::warning('yeekee.result_engine.legacy_formula_fallback', [
                'yeekee_round_id' => (int) $round->id,
                'lotto_draw_id' => (int) $round->lotto_draw_id,
                'fallback_preset' => $formulaKey,
                'reason' => 'missing formula_config',
            ]);
        }

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

        if (str_starts_with($formulaKey, 'PROVABLY_FAIR_')) {
            $seedConfig = is_array($snapshot['external_seed_config'] ?? null) ? $snapshot['external_seed_config'] : [];
            $this->seedResolver->resolveForRound($roundId, $seedConfig);
        }

        return $formula->compute($shoots, $formulaConfig);
    }
}
