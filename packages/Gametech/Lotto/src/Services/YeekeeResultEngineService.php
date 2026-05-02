<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Models\YeekeeShoot;
use Gametech\Lotto\Services\Yeekee\Exceptions\YeekeeFormulaInputException;
use Gametech\Lotto\Services\Yeekee\Formulas\FormulaRegistry;
use Gametech\Lotto\Services\Yeekee\Seed\ExternalSeedResolverService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        $context = DB::transaction(function () use ($roundId): array {
            $round = YeekeeRound::query()->lockForUpdate()->findOrFail($roundId);
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

            $shootSnapshotPayload = $this->freezeShootSnapshotIfNeeded($round);

            return [
                'round_id' => (int) $round->id,
                'shoot_close_at' => (string) $round->shoot_close_at,
                'formula_key' => $formulaKey,
                'formula_config' => $formulaConfig,
                'round_snapshot' => $snapshot,
                'shoot_snapshot_payload' => $shootSnapshotPayload,
            ];
        });

        $formulaKey = (string) $context['formula_key'];
        $formulaConfig = is_array($context['formula_config']) ? $context['formula_config'] : [];
        $shootSnapshotPayload = is_array($context['shoot_snapshot_payload']) ? $context['shoot_snapshot_payload'] : [];
        $shoots = is_array($shootSnapshotPayload['shoots'] ?? null) ? $shootSnapshotPayload['shoots'] : [];

        if ($formulaKey === 'SHOOTS_SUM_ONLY') {
            return $this->computeShootsSumOnly(
                roundId: (int) $context['round_id'],
                shootCloseAt: (string) $context['shoot_close_at'],
                shootsSnapshot: $shoots,
                formulaConfig: $formulaConfig
            );
        }

        $formula = $this->formulaRegistry->resolve($formulaKey);

        if (str_starts_with($formulaKey, 'PROVABLY_FAIR_')) {
            $roundSnapshot = is_array($context['round_snapshot']) ? $context['round_snapshot'] : [];
            $seedConfig = is_array($roundSnapshot['external_seed_config'] ?? null) ? $roundSnapshot['external_seed_config'] : [];
            $this->seedResolver->resolveForRound((int) $context['round_id'], $seedConfig);
        }

        return $formula->compute($shoots, $formulaConfig);
    }

    /**
     * @param  array<string,mixed>  $formulaConfig
     * @return array<string,mixed>
     */
    private function computeShootsSumOnly(
        int $roundId,
        string $shootCloseAt,
        array $shootsSnapshot,
        array $formulaConfig
    ): array {
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

        $cutoffAt = Carbon::parse($shootCloseAt)->subSeconds($cutoffSecondsBeforeClose);
        $cutoffAtString = $cutoffAt->format('Y-m-d H:i:s');

        $totalSum = 0;
        $includedCount = 0;
        $skippedInvalidCount = 0;
        $sampleLimit = 20;
        $sample = [];

        foreach ($shootsSnapshot as $row) {
            if (! is_array($row)) {
                $skippedInvalidCount++;

                continue;
            }

            $submittedAt = trim((string) ($row['submitted_at'] ?? ''));
            if ($submittedAt === '' || $submittedAt > $cutoffAtString) {
                continue;
            }

            $numberText = (string) ($row['number_text'] ?? '');
            if (! preg_match('/^\d+$/', $numberText)) {
                $skippedInvalidCount++;

                continue;
            }

            $numberValue = (int) ($row['number_value'] ?? -1);
            if ($numberValue < 0) {
                $skippedInvalidCount++;

                continue;
            }

            $totalSum += $numberValue;
            $includedCount++;

            if (count($sample) < $sampleLimit) {
                $sample[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'position' => (int) ($row['position'] ?? 0),
                    'number_text' => $numberText,
                    'number_value' => $numberValue,
                    'submitted_at' => $submittedAt,
                ];
            }
        }

        if ($includedCount === 0) {
            throw new YeekeeFormulaInputException(
                'FORMULA_INPUT_INSUFFICIENT',
                'ไม่มีข้อมูลเลขยิง'
            );
        }

        if ($skippedInvalidCount > 0) {
            Log::warning('yeekee.shoots_sum_only.invalid_rows_skipped', [
                'yeekee_round_id' => $roundId,
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
                        'yeekee_round_id' => $roundId,
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

    /**
     * @return array<string,mixed>
     */
    private function freezeShootSnapshotIfNeeded(YeekeeRound $round): array
    {
        $existingSnapshot = is_array($round->shoot_snapshot_json) ? $round->shoot_snapshot_json : null;
        if ($round->shoot_closed_at !== null && is_array($existingSnapshot)) {
            return $this->normalizeShootSnapshotPayload($existingSnapshot);
        }

        $shootsSnapshot = YeekeeShoot::query()
            ->where('yeekee_round_id', (int) $round->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'position', 'number_text', 'number_value', 'submitted_at'])
            ->map(static function (YeekeeShoot $row): array {
                return [
                    'id' => (int) $row->id,
                    'position' => (int) $row->position,
                    'number_text' => (string) $row->number_text,
                    'number_value' => (int) $row->number_value,
                    'submitted_at' => (string) $row->submitted_at,
                ];
            })
            ->values()
            ->all();

        $shootClosedAt = ($round->shoot_closed_at ?? now())->format('Y-m-d H:i:s');
        $payload = [
            'metadata' => [
                'round_id' => (int) $round->id,
                'lotto_draw_id' => (int) $round->lotto_draw_id,
                'market_id' => (int) $round->market_id,
                'round_no' => (int) $round->round_no,
                'round_date' => (string) $round->round_date,
                'shoot_open_at' => (string) $round->shoot_open_at,
                'shoot_close_at' => (string) $round->shoot_close_at,
                'shoot_closed_at' => $shootClosedAt,
                'shoot_count' => count($shootsSnapshot),
                'last_shoot_position' => (int) ($shootsSnapshot[count($shootsSnapshot) - 1]['position'] ?? 0),
            ],
            'shoots' => $shootsSnapshot,
        ];

        $round->forceFill([
            'shoot_snapshot_json' => $payload,
            'shoot_snapshot_hash' => hash(
                'sha256',
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: ''
            ),
            'shoot_closed_at' => $shootClosedAt,
            'shoot_count' => (int) $payload['metadata']['shoot_count'],
            'last_shoot_position' => (int) $payload['metadata']['last_shoot_position'],
        ])->save();

        return $payload;
    }

    /**
     * @param  array<string,mixed>|array<int,mixed>  $snapshot
     * @return array<string,mixed>
     */
    private function normalizeShootSnapshotPayload(array $snapshot): array
    {
        $shootsSource = is_array($snapshot['shoots'] ?? null) ? $snapshot['shoots'] : $snapshot;
        $shoots = collect($shootsSource)
            ->filter(static fn ($row): bool => is_array($row))
            ->map(static function (array $row): array {
                return [
                    'id' => (int) ($row['id'] ?? 0),
                    'position' => (int) ($row['position'] ?? 0),
                    'number_text' => (string) ($row['number_text'] ?? ''),
                    'number_value' => (int) ($row['number_value'] ?? 0),
                    'submitted_at' => (string) ($row['submitted_at'] ?? ''),
                ];
            })
            ->values()
            ->all();

        $metadataSource = is_array($snapshot['metadata'] ?? null) ? $snapshot['metadata'] : [];
        $metadata = [
            'round_id' => (int) ($metadataSource['round_id'] ?? 0),
            'lotto_draw_id' => (int) ($metadataSource['lotto_draw_id'] ?? 0),
            'market_id' => (int) ($metadataSource['market_id'] ?? 0),
            'round_no' => (int) ($metadataSource['round_no'] ?? 0),
            'round_date' => (string) ($metadataSource['round_date'] ?? ''),
            'shoot_open_at' => (string) ($metadataSource['shoot_open_at'] ?? ''),
            'shoot_close_at' => (string) ($metadataSource['shoot_close_at'] ?? ''),
            'shoot_closed_at' => (string) ($metadataSource['shoot_closed_at'] ?? ''),
            'shoot_count' => (int) ($metadataSource['shoot_count'] ?? count($shoots)),
            'last_shoot_position' => (int) ($metadataSource['last_shoot_position'] ?? ($shoots[count($shoots) - 1]['position'] ?? 0)),
        ];

        return [
            'metadata' => $metadata,
            'shoots' => $shoots,
        ];
    }
}
