<?php

namespace Gametech\Lotto\Services\Yeekee\Seed;

use Gametech\Lotto\Models\YeekeeRound;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExternalSeedResolverService
{
    public function __construct(
        private SeedProviderRegistry $providerRegistry
    ) {}

    /**
     * @return array{provider_key:string,source_reference:string,seed_value:string,resolved_at:string,fallback_used:bool}
     */
    public function resolveForRound(int $roundId, array $seedConfig): array
    {
        return DB::transaction(function () use ($roundId, $seedConfig) {
            $round = YeekeeRound::query()->lockForUpdate()->findOrFail($roundId);
            $snapshot = is_array($round->config_snapshot_json) ? $round->config_snapshot_json : [];
            $existing = is_array($snapshot['external_seed_snapshot'] ?? null) ? $snapshot['external_seed_snapshot'] : null;
            if (is_array($existing)) {
                return $existing;
            }

            $primarySource = (string) ($seedConfig['primary_source'] ?? '');
            $this->providerRegistry->resolve($primarySource);

            $roundConfig = is_array($snapshot['round_config'] ?? null) ? $snapshot['round_config'] : [];
            $roundDurationMinutes = (int) ($roundConfig['round_duration_minutes'] ?? 15);
            $providerMeta = $this->providerRegistry->resolve($primarySource);
            $minRoundMinutes = (int) ($providerMeta['min_supported_round_duration_minutes'] ?? 15);
            $allowFastOverride = (bool) ($seedConfig['allow_fast_round_override'] ?? false);
            if ($roundDurationMinutes < $minRoundMinutes && ! $allowFastOverride) {
                throw new InvalidArgumentException('round duration ไม่รองรับ provider ที่เลือก');
            }

            $seedValue = (string) ($seedConfig['mock_seed_value'] ?? '');
            if ($seedValue === '') {
                $seedValue = hash('sha256', $primarySource.'|'.(string) $round->lotto_draw_id.'|'.(string) $round->round_date);
            }

            $resolved = [
                'provider_key' => $primarySource,
                'source_reference' => 'draw:'.(string) $round->lotto_draw_id,
                'seed_value' => $seedValue,
                'resolved_at' => now()->toDateTimeString(),
                'fallback_used' => false,
            ];

            $snapshot['external_seed_snapshot'] = $resolved;
            $round->update([
                'config_snapshot_json' => $snapshot,
            ]);

            return $resolved;
        });
    }
}
