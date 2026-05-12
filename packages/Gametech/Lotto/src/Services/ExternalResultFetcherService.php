<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LottoResultSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalResultFetcherService
{
    public function __construct(
        private ArchiveNormalizerService $normalizer,
        private ArchiveChecksumService $checksum,
    ) {}

    /**
     * Fetch missing results from external source for a specific market + date.
     *
     * @return array<int, array>|null Normalized archive rows, or null if unavailable
     */
    public function fetchMissing(string $marketCode, string $drawDate): ?array
    {
        $source = LottoResultSource::query()
            ->whereHas('market', fn ($q) => $q->where('code', $marketCode))
            ->where('is_active', true)
            ->orderBy('priority')
            ->first();

        if (! $source) {
            Log::warning('ExternalResultFetcher: no active source for market', [
                'market_code' => $marketCode,
                'draw_date' => $drawDate,
            ]);

            return null;
        }

        $fetchedAt = now()->toIso8601String();

        try {
            $result = $this->fetchFromSource($source, $drawDate);

            if (! $result) {
                return null;
            }

            return $this->normalizeExternalResult($result, $marketCode, $drawDate, $source, $fetchedAt);
        } catch (\Exception $e) {
            Log::error('ExternalResultFetcher: fetch failed', [
                'market_code' => $marketCode,
                'draw_date' => $drawDate,
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function fetchFromSource(LottoResultSource $source, string $drawDate): ?array
    {
        $url = $this->buildFetchUrl($source, $drawDate);
        $method = strtolower($source->http_method ?? 'get');

        try {
            $response = Http::timeout($source->timeout_seconds ?: 30)
                ->withHeaders($source->request_headers_json ?? [])
                ->{$method}($url);

            if (! $response->successful()) {
                Log::warning('ExternalResultFetcher: non-successful response', [
                    'source_id' => $source->id,
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $body = $response->json();

            if (! is_array($body)) {
                return null;
            }

            return $body;
        } catch (\Exception $e) {
            Log::warning('ExternalResultFetcher: HTTP request failed', [
                'source_id' => $source->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function buildFetchUrl(LottoResultSource $source, string $drawDate): string
    {
        $url = $source->endpoint_url;

        if (empty($url)) {
            return '';
        }

        $url = str_replace('{date}', $drawDate, $url);
        $url = str_replace('{lookup_date}', $drawDate, $url);

        return $url;
    }

    protected function normalizeExternalResult(
        array $rawResult,
        string $marketCode,
        string $drawDate,
        LottoResultSource $source,
        string $fetchedAt,
    ): array {
        $sourceInfo = [
            'source_url' => $source->endpoint_url ?? 'unknown',
            'fetched_at' => $fetchedAt,
            'parser_version' => $source->pipeline_version ?? 'unknown',
            'source_id' => $source->id,
        ];

        $rows = [];

        foreach ($rawResult as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $resultSet = is_array($value) ? array_map('strval', $value) : [(string) $value];

            $rows[] = [
                'market_code' => $marketCode,
                'draw_date' => $drawDate,
                'draw_key' => $key,
                'result_set' => $resultSet,
                'result_hash' => $this->checksum->computeResultHash($resultSet),
                'source_info_json' => $sourceInfo,
            ];
        }

        return $rows;
    }
}
