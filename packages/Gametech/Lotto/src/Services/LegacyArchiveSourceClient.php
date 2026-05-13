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
     * - HTTP 200 with non-empty results array → fetch_status = 'success', one row per result item
     * - HTTP 200 with empty results array     → fetch_status = 'not_found', single sentinel row
     * - HTTP 200 with missing/non-array results or non-JSON body → fetch_status = 'failed' + Log::warning
     * - HTTP 404                              → fetch_status = 'not_found', single sentinel row
     * - Any other HTTP error                  → fetch_status = 'failed',   single sentinel row + Log::warning
     * - Connection/timeout exception          → fetch_status = 'failed',   single sentinel row + Log::error
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $type, string $date, ?string $endpointUrl = null, array $queryParams = []): array
    {
        $fetchedAt = Carbon::now();
        $baseUrl = $endpointUrl ?: config('lotto.legacy_archive.base_url');

        if (empty($baseUrl)) {
            return [$this->buildSentinelRow($type, $date, '', $fetchedAt, 'failed', 'LOTTO_LEGACY_ARCHIVE_BASE_URL is not configured')];
        }

        $baseUrl = rtrim((string) $baseUrl, '/');

        if (empty($queryParams)) {
            $queryParams = ['type' => $type, 'date' => $date];
        }

        $url = $baseUrl.'?'.http_build_query($queryParams);

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
                Log::warning('LegacyArchiveSourceClient: non-JSON response body', [
                    'type' => $type,
                    'date' => $date,
                    'url' => $url,
                ]);

                return [$this->buildSentinelRow($type, $date, $url, $fetchedAt, 'failed', 'Non-JSON response body')];
            }

            if (! array_key_exists('results', $payload)) {
                Log::warning('LegacyArchiveSourceClient: missing results key in response', [
                    'type' => $type,
                    'date' => $date,
                    'url' => $url,
                ]);

                return [$this->buildSentinelRow($type, $date, $url, $fetchedAt, 'failed', 'Missing results key in response')];
            }

            $results = $payload['results'];

            if (! is_array($results)) {
                Log::warning('LegacyArchiveSourceClient: non-array results field', [
                    'type' => $type,
                    'date' => $date,
                    'url' => $url,
                ]);

                return [$this->buildSentinelRow($type, $date, $url, $fetchedAt, 'failed', 'Unexpected non-array results field')];
            }

            $nameTH = (string) ($payload['nameTH'] ?? '');
            $page = isset($payload['page']) ? (int) $payload['page'] : null;

            if (empty($results)) {
                return [$this->buildSentinelRow($type, $date, $url, $fetchedAt, 'not_found', null, $nameTH, $page)];
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
     * Fetch result rows from exphuay.com SvelteKit data endpoint.
     *
     * One request returns ~30 days of results. The response uses SvelteKit's
     * de-duplicated JSON format where a template object maps field names to
     * array indices, and values follow at those indices.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchFromExphuay(string $type, string $endpointUrl): array
    {
        $fetchedAt = Carbon::now();
        $url = sprintf('https://exphuay.com/result/%s/__data.json?x-sveltekit-invalidated=01', urlencode($type));

        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                Log::warning('LegacyArchiveSourceClient: exphuay non-successful response', [
                    'type' => $type,
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return [];
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                return [];
            }

            $rows = $this->parseExphuayPayload($payload, $type, $url, $fetchedAt);

            if (empty($rows)) {
                return [$this->buildSentinelRow($type, '', $url, $fetchedAt, 'not_found', null)];
            }

            // Fill request_date from lottos_date for exphuay rows (safety net — mapResultItem already handles this)
            foreach ($rows as &$row) {
                if (empty($row['request_date']) && ! empty($row['lottos_date'])) {
                    $row['request_date'] = $row['lottos_date'] instanceof \DateTimeInterface
                        ? $row['lottos_date']->copy()->timezone(config('app.timezone', 'Asia/Bangkok'))->format('Y-m-d')
                        : Carbon::parse($row['lottos_date'])->timezone(config('app.timezone', 'Asia/Bangkok'))->format('Y-m-d');
                }
            }
            unset($row);

            return $rows;
        } catch (\Throwable $e) {
            Log::error('LegacyArchiveSourceClient: exphuay fetch exception', [
                'type' => $type,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Parse exphuay SvelteKit de-duplicated JSON payload.
     *
     * The SvelteKit format uses a template→absolute-index map: a template object
     * maps field names to absolute indices in the data array. Resolution is simply
     * $value = $data[$template[$fieldName]].
     *
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    protected function parseExphuayPayload(array $payload, string $type, string $url, Carbon $fetchedAt): array
    {
        $data = $payload['nodes'][1]['data'] ?? null;

        if (! is_array($data)) {
            return [];
        }

        $rows = [];

        // Current result template is at data[2]
        $current = $this->exphuayEntry($data, $data[2] ?? []);

        if ($current !== null) {
            $rows[] = $this->mapResultItem($current, $type, '', '', null, $url, $fetchedAt);
        }

        // Backward list: each index points to an entry template object
        $backwardIdx = $data[1]['backward'] ?? null;
        $backwardIndices = $backwardIdx !== null ? ($data[$backwardIdx] ?? []) : [];

        foreach ($backwardIndices as $entryTemplateIdx) {
            $entryTemplate = $data[$entryTemplateIdx] ?? null;

            if (! is_array($entryTemplate)) {
                continue;
            }

            $entry = $this->exphuayEntry($data, $entryTemplate);

            if ($entry !== null) {
                $rows[] = $this->mapResultItem($entry, $type, '', '', null, $url, $fetchedAt);
            }
        }

        return $rows;
    }

    /**
     * Resolve a single exphuay entry from the data array using a template.
     *
     * The template maps field names to absolute array indices.
     *
     * @param  array<int, mixed>  $data
     * @param  array<string, int>  $template  e.g. {lottosName:6, lottosNumber:10, ...}
     */
    protected function exphuayEntry(array $data, array $template): ?array
    {
        $fields = ['id', 'lottosName', 'lottosTH', 'lottosDate', 'lottosTime', 'lottosNumber', 'lottosUnder'];

        $entry = [];

        foreach ($fields as $field) {
            $idx = $template[$field] ?? null;

            if ($idx === null || ! isset($data[$idx])) {
                continue;
            }

            $value = $data[$idx];

            if (is_array($value)) {
                continue;
            }

            $entry[$field] = $value;
        }

        return ! empty($entry['lottosName']) && ! empty($entry['lottosNumber']) ? $entry : null;
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
        $resolvedRequestDate = $requestDate;

        if ($lottosDateRaw !== null && $lottosDateRaw !== '') {
            try {
                $lottosDate = Carbon::parse($lottosDateRaw)
                    ->timezone(config('app.timezone', 'Asia/Bangkok'));
                $resolvedRequestDate = $lottosDate->format('Y-m-d');
            } catch (\Throwable) {
                $lottosDate = null;
            }
        }

        return [
            'type' => $type,
            'name_th' => $nameTH !== '' ? $nameTH : null,
            'request_date' => $resolvedRequestDate,
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
        string $nameTH = '',
        ?int $page = null,
    ): array {
        return [
            'type' => $type,
            'name_th' => $nameTH !== '' ? $nameTH : null,
            'request_date' => $date,
            'page' => $page,
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
