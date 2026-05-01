<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Models\YeekeeShoot;
use Gametech\Lotto\Services\Yeekee\Exceptions\YeekeeFormulaInputException;
use Gametech\Lotto\Services\Yeekee\Formulas\FormulaRegistry;
use Gametech\Lotto\Services\Yeekee\Seed\ExternalSeedResolverService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class YeekeeResultEngineService
{
    public function __construct(
        private FormulaRegistry $formulaRegistry,
        private ExternalSeedResolverService $seedResolver
    ) {}

    /**
     * @return array<string,mixed>
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

        if ($formulaKey === 'SHOOTS_SUM_ONLY') {
            return $this->computeShootsSumOnly($round, $formulaConfig);
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

    /**
     * @param  array<string,mixed>  $formulaConfig
     * @return array<string,mixed>
     */
    private function computeShootsSumOnly(YeekeeRound $round, array $formulaConfig): array
    {
        $inputRules = is_array($formulaConfig['input_rules'] ?? null) ? $formulaConfig['input_rules'] : [];
        if (array_key_exists('include_status', $inputRules) || array_key_exists('exclude_cancelled', $inputRules)) {
            throw new InvalidArgumentException('FORMULA_CONFIG_INVALID: include_status/exclude_cancelled not supported');
        }

        $calculationRules = is_array($formulaConfig['calculation_rules'] ?? null) ? $formulaConfig['calculation_rules'] : [];
        $modulo = (int) ($calculationRules['modulo'] ?? $formulaConfig['modulo'] ?? 100000);
        if ($modulo <= 0) {
            throw new InvalidArgumentException('FORMULA_CONFIG_INVALID: modulo ต้องมากกว่า 0');
        }

        $cutoffSecondsBeforeClose = (int) ($inputRules['cutoff_seconds_before_close'] ?? 0);
        if ($cutoffSecondsBeforeClose < 0) {
            throw new InvalidArgumentException('FORMULA_CONFIG_INVALID: cutoff_seconds_before_close ต้องไม่ติดลบ');
        }

        $shootCloseAt = Carbon::parse((string) $round->shoot_close_at);
        $cutoffAt = $shootCloseAt->copy()->subSeconds($cutoffSecondsBeforeClose);
        $cutoffAtString = $cutoffAt->format('Y-m-d H:i:s');

        $query = YeekeeShoot::query()
            ->where('yeekee_round_id', (int) $round->id)
            ->where('submitted_at', '<=', $cutoffAtString)
            ->orderBy('id');

        $totalSum = 0;
        $includedCount = 0;
        $skippedInvalidCount = 0;
        $sampleLimit = 20;
        $sample = [];

        $query->chunkById(500, function ($rows) use (&$totalSum, &$includedCount, &$skippedInvalidCount, &$sample, $sampleLimit): void {
            foreach ($rows as $row) {
                $numberText = (string) ($row->number_text ?? '');
                if (! preg_match('/^\d+$/', $numberText)) {
                    $skippedInvalidCount++;

                    continue;
                }

                $numberValue = (int) ($row->number_value ?? -1);
                if ($numberValue < 0) {
                    $skippedInvalidCount++;

                    continue;
                }

                $totalSum += $numberValue;
                $includedCount++;

                if (count($sample) < $sampleLimit) {
                    $sample[] = [
                        'id' => (int) $row->id,
                        'position' => (int) $row->position,
                        'number_text' => $numberText,
                        'number_value' => $numberValue,
                        'submitted_at' => (string) $row->submitted_at,
                    ];
                }
            }
        });

        if ($includedCount === 0) {
            throw new YeekeeFormulaInputException(
                'FORMULA_INPUT_INSUFFICIENT',
                'ไม่มีข้อมูลเลขยิง'
            );
        }

        if ($skippedInvalidCount > 0) {
            Log::warning('yeekee.shoots_sum_only.invalid_rows_skipped', [
                'yeekee_round_id' => (int) $round->id,
                'skipped_invalid_count' => $skippedInvalidCount,
            ]);
        }

        $moduloResult = $totalSum % $modulo;
        $rawResult = str_pad((string) $moduloResult, 5, '0', STR_PAD_LEFT);
        $auditConfigSnapshot = [
            'preset' => 'SHOOTS_SUM_ONLY',
            'version' => (int) ($formulaConfig['version'] ?? 1),
            'input_rules' => [
                'cutoff_seconds_before_close' => $cutoffSecondsBeforeClose,
            ],
            'calculation_rules' => [
                'modulo' => $modulo,
            ],
        ];

        $audit = [
            'input_summary' => [
                'included_count' => $includedCount,
                'skipped_invalid_count' => $skippedInvalidCount,
                'cutoff_at' => $cutoffAtString,
                'cutoff_seconds_before_close' => $cutoffSecondsBeforeClose,
            ],
            'input_hash' => hash(
                'sha256',
                json_encode(
                    [
                        'yeekee_round_id' => (int) $round->id,
                        'cutoff_at' => $cutoffAtString,
                        'sample' => $sample,
                        'included_count' => $includedCount,
                    ],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ) ?: ''
            ),
            'sample_limit' => $sampleLimit,
            'total_sum' => $totalSum,
            'modulo_result' => $moduloResult,
            'final_digits_used' => $rawResult,
            'config_snapshot' => $auditConfigSnapshot,
        ];

        return [
            'raw_result' => $rawResult,
            'top_3' => substr($rawResult, -3),
            'bottom_2' => substr($rawResult, 0, 2),
            'formula_audit' => $audit,
        ];
    }
}
