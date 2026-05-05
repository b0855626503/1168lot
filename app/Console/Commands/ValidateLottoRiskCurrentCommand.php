<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ValidateLottoRiskCurrentCommand extends Command
{
    protected $signature = 'dashboard:lotto-risk-current-validate
        {--web-code= : Limit validation by web code}
        {--market-id= : Limit validation by market ID}
        {--round-id= : Limit validation by round ID}
        {--limit=100 : Max mismatch rows to display}
        {--tolerance=0.01 : Decimal comparison tolerance}';

    protected $description = 'Validate lotto dashboard current risk rows against latest detailed risk snapshots';

    public function handle(): int
    {
        if (! Schema::hasTable('lotto_dashboard_risk_snapshot')) {
            $this->warn('table lotto_dashboard_risk_snapshot not found');

            return 1;
        }

        if (! Schema::hasTable('lotto_dashboard_risk_current')) {
            $this->warn('table lotto_dashboard_risk_current not found');

            return 1;
        }

        $limit = max(1, (int) $this->option('limit'));
        $tolerance = max(0.0, (float) $this->option('tolerance'));

        $snapshotKeys = DB::table('lotto_dashboard_risk_snapshot as rs')
            ->select([
                'rs.web_code',
                'rs.market_id',
                'rs.round_id',
                'rs.bet_type',
                'rs.number',
            ])
            ->groupBy('rs.web_code', 'rs.market_id', 'rs.round_id', 'rs.bet_type', 'rs.number')
            ->orderBy('rs.web_code')
            ->orderBy('rs.market_id')
            ->orderBy('rs.round_id')
            ->orderBy('rs.bet_type')
            ->orderBy('rs.number');
        $this->applyFilters($snapshotKeys, 'rs');

        $checked = 0;
        $missingCurrent = 0;
        $mismatch = 0;
        $samples = [];

        foreach ($snapshotKeys->cursor() as $key) {
            $latest = DB::table('lotto_dashboard_risk_snapshot')
                ->where('web_code', $key->web_code)
                ->where('market_id', $key->market_id)
                ->where('round_id', $key->round_id)
                ->where('bet_type', $key->bet_type)
                ->where('number', $key->number)
                ->orderByDesc('snapshot_at')
                ->orderByDesc('id')
                ->first();

            if ($latest === null) {
                continue;
            }

            $current = DB::table('lotto_dashboard_risk_current')
                ->where('web_code', $key->web_code)
                ->where('market_id', $key->market_id)
                ->where('round_id', $key->round_id)
                ->where('bet_type', $key->bet_type)
                ->where('number', $key->number)
                ->first();

            $checked++;

            if ($current === null) {
                $missingCurrent++;
                $this->pushSample($samples, $limit, 'missing_current', $latest, null);
                continue;
            }

            $diffs = $this->diffRows($latest, $current, $tolerance);
            if ($diffs !== []) {
                $mismatch++;
                $this->pushSample($samples, $limit, implode(',', $diffs), $latest, $current);
            }
        }

        $duplicateCurrent = $this->countDuplicateCurrentRows();

        $this->table(
            ['metric', 'value'],
            [
                ['checked_latest_snapshot_keys', $checked],
                ['missing_current', $missingCurrent],
                ['value_mismatch', $mismatch],
                ['duplicate_current_keys', $duplicateCurrent],
            ]
        );

        if ($samples !== []) {
            $this->table(
                ['type', 'web_code', 'market_id', 'round_id', 'bet_type', 'number', 'snapshot_at'],
                $samples
            );
        }

        if ($missingCurrent > 0 || $mismatch > 0 || $duplicateCurrent > 0) {
            $this->error('lotto risk current validation failed');

            return 1;
        }

        $this->info('lotto risk current validation passed');

        return 0;
    }

    private function applyFilters($query, string $alias): void
    {
        $webCode = trim((string) ($this->option('web-code') ?? ''));
        if ($webCode !== '') {
            $query->where("{$alias}.web_code", $webCode);
        }

        $marketId = trim((string) ($this->option('market-id') ?? ''));
        if ($marketId !== '') {
            $query->where("{$alias}.market_id", (int) $marketId);
        }

        $roundId = trim((string) ($this->option('round-id') ?? ''));
        if ($roundId !== '') {
            $query->where("{$alias}.round_id", (int) $roundId);
        }
    }

    private function diffRows(object $latest, object $current, float $tolerance): array
    {
        $diffs = [];

        foreach (['stake_total', 'payout_if_hit', 'liability'] as $field) {
            if (abs((float) $latest->{$field} - (float) $current->{$field}) > $tolerance) {
                $diffs[] = $field;
            }
        }

        if ((string) $latest->snapshot_at !== (string) $current->snapshot_at) {
            $diffs[] = 'snapshot_at';
        }

        return $diffs;
    }

    /**
     * @param  array<int, array<int, string|int|null>>  $samples
     */
    private function pushSample(array &$samples, int $limit, string $type, object $latest, ?object $current): void
    {
        if (count($samples) >= $limit) {
            return;
        }

        $samples[] = [
            $type,
            (string) $latest->web_code,
            (int) $latest->market_id,
            (int) $latest->round_id,
            (string) $latest->bet_type,
            (string) $latest->number,
            $current?->snapshot_at ?? $latest->snapshot_at,
        ];
    }

    private function countDuplicateCurrentRows(): int
    {
        return (int) DB::query()
            ->fromSub(
                DB::table('lotto_dashboard_risk_current')
                    ->select([
                        'web_code',
                        'market_id',
                        'round_id',
                        'bet_type',
                        'number',
                        DB::raw('COUNT(*) as row_count'),
                    ])
                    ->groupBy('web_code', 'market_id', 'round_id', 'bet_type', 'number')
                    ->havingRaw('COUNT(*) > 1'),
                'duplicates'
            )
            ->count();
    }
}
