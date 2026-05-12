<?php

namespace Gametech\Lotto\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LegacyArchiveSourceClient
{
    /**
     * Fetch result rows from the legacy archive source for a given type and date.
     *
     * Returns one or more mapped field arrays suitable for LegacyArchiveResultRepository::upsert().
     *
     * Status rules:
     * - HTTP 200 with results array  → fetch_status = 'success', one row per result item
     * - HTTP 404                      → fetch_status = 'not_found', single sentinel row
     * - Any other HTTP error          → fetch_status = 'failed',   single sentinel row
     * - Connection/timeout exception  → fetch_status = 'failed',   single sentinel row
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $type, string $date): array
    {
        $baseUrl = rtrim((string) config('lotto.legacy_archive.base_url', ''), '/');
        $url = sprintf('%s?type=%s&date=%s', $baseUrl, urlencode($type), urlencode($date));
        $fetchedAt = Carbon::now();

        try {
            $response = Http::timeout(30)->get($url);

            if ($response->status() === 404) {
                return [$this->buildSentinelRow($type, $date, $url, $fetchedAt, 'not_found', null)];
            }

            if (! $response->successful()) {
                $message = sprintf(
                    'HTTP %d for type=%s date=%s',
                    $response->status(),
                    $type,
                    $date
                );

                Log::warning('LegacyArchiveSourceClient: non-successful response', [
                    'type' => $type,
                    'date' => $date,
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return [$this->buildSentinelRow($type, $date, $url, $fetchedAt, 'failed', $message)];
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                return [$this->buildSentinelRow($type, $date, $url, $fetchedAt, 'failed', 'Non-JSON response body')];
            }

            $results = $payload['results'] ?? [];

            if (! is_array($results)) {
                return [$this->buildSentinelRow($type, $date, $url, $fetchedAt, 'failed', 'Unexpected non-array results field')];
            }

            $nameTH = (string) ($payload['nameTH'] ?? '');
            $page = isset($payload['page']) ? (int) $payload['page'] : null;

            if (empty($results)) {
                return [$this->buildSentinelRow($type, $date, $url, $fetchedAt, 'not_found', null)];
            }

            $rows = [];

            foreach ($results as $item) {
                $rows[] = $this->mapResultItem($item, $type, $nameTH, $date, $page, $url, $fetchedAt);
            }

            return $rows;
        } catch (\Throwable $e) {
            Log::error('LegacyArchiveSourceClient: fetch exception', [
                'type' => $type,
                'date' => $date,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [$this->buildSentinelRow($type, $date, $url, $fetchedAt, 'failed', $e->getMessage())];
        }
    }

    /**
     * Map a single result item from the source payload to a DB field array.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function mapResultItem(
        array $item,
        string $type,
        string $nameTH,
        string $requestDate,
        ?int $page,
        string $url,
        Carbon $fetchedAt,
    ): array {
        $lottosDateRaw = isset($item['lottosDate']) ? (string) $item['lottosDate'] : null;
        $lottosDate = null;

        if ($lottosDateRaw !== null && $lottosDateRaw !== '') {
            try {
                $lottosDate = Carbon::parse($lottosDateRaw);
            } catch (\Throwable) {
                $lottosDate = null;
            }
        }

        return [
            'type' => $type,
            'name_th' => $nameTH !== '' ? $nameTH : null,
            'request_date' => $requestDate,
            'page' => $page,
            'source_result_id' => isset($item['id']) ? (int) $item['id'] : null,
            'lottos_name' => (string) ($item['lottosName'] ?? ''),
            'lottos_th' => isset($item['lottosTH']) ? (string) $item['lottosTH'] : null,
            'lottos_date' => $lottosDate,
            'lottos_date_raw' => $lottosDateRaw,
            'lottos_time' => isset($item['lottosTime']) ? (string) $item['lottosTime'] : null,
            'lottos_number' => isset($item['lottosNumber']) ? (string) $item['lottosNumber'] : null,
            'lottos_under' => isset($item['lottosUnder']) ? (string) $item['lottosUnder'] : null,
            'payload_json' => $item,
            'fetched_at' => $fetchedAt,
            'fetch_status' => 'success',
            'source_url' => $url,
            'last_error' => null,
        ];
    }

    /**
     * Build a single sentinel row for non-success outcomes.
     *
     * @return array<string, mixed>
     */
    protected function buildSentinelRow(
        string $type,
        string $date,
        string $url,
        Carbon $fetchedAt,
        string $fetchStatus,
        ?string $lastError,
    ): array {
        return [
            'type' => $type,
            'name_th' => null,
            'request_date' => $date,
            'page' => null,
            'source_result_id' => null,
            'lottos_name' => $type,
            'lottos_th' => null,
            'lottos_date' => null,
            'lottos_date_raw' => null,
            'lottos_time' => null,
            'lottos_number' => null,
            'lottos_under' => null,
            'payload_json' => null,
            'fetched_at' => $fetchedAt,
            'fetch_status' => $fetchStatus,
            'source_url' => $url,
            'last_error' => $lastError,
        ];
    }
}
