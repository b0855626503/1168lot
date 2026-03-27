<?php

namespace Gametech\Lotto\Services\AutoResult;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoResultSource;
use Illuminate\Support\Facades\Schema;

class ResultSourceResolver
{
    public function resolve(LottoDraw $draw): ?LottoResultSource
    {
        $now = now((string) config('lotto_auto_result.timezone', (string) config('app.timezone', 'Asia/Bangkok')));

        $source = LottoResultSource::query()
            ->where('market_id', (int) $draw->market_id)
            ->where('is_active', true)
            ->where(function ($q) use ($now): void {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $now);
            })
            ->where(function ($q) use ($now): void {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
            })
            ->orderBy('priority')
            ->orderBy('id')
            ->first();

        $this->persistSnapshot($draw, $source);

        return $source;
    }

    private function persistSnapshot(LottoDraw $draw, ?LottoResultSource $source): void
    {
        $updates = [];

        if (Schema::hasColumn('lotto_draws', 'result_source_snapshot_json')) {
            $updates['result_source_snapshot_json'] = $source ? [
                'id' => (int) $source->id,
                'market_id' => (int) $source->market_id,
                'source_type' => (string) $source->source_type,
                'endpoint_url' => (string) $source->endpoint_url,
                'http_method' => (string) $source->http_method,
                'lookup_date_mode' => (string) $source->lookup_date_mode,
                'lookup_date_offset_days' => (int) $source->lookup_date_offset_days,
                'parser_type' => (string) $source->parser_type,
                'priority' => (int) $source->priority,
                'timeout_seconds' => (int) $source->timeout_seconds,
                'resolved_at' => now()->toDateTimeString(),
            ] : null;
        }

        if (Schema::hasColumn('lotto_draws', 'result_source_id')) {
            $updates['result_source_id'] = $source ? (int) $source->id : null;
        }

        if (Schema::hasColumn('lotto_draws', 'result_source_version')) {
            $updates['result_source_version'] = $source && $source->updated_at
                ? $source->updated_at->format('YmdHis')
                : null;
        }

        if ($updates !== []) {
            $draw->forceFill($updates)->save();
        }
    }
}
