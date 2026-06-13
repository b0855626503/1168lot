<?php

namespace Gametech\Lotto\Services;

use Carbon\CarbonImmutable;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoResultSource;
use Gametech\Lotto\Services\InternalResultSources\HttpResultFetcher;
use Gametech\Lotto\Services\InternalResultSources\InternalResultService;
use Gametech\Lotto\Services\Relay\LotteryRelayTypeRegistry;
use Illuminate\Support\Facades\Cache;

class CentralLotteryResultService
{
    private const CACHE_TTL_PAST_DATE_SECONDS = 1800;

    private const CACHE_TTL_CURRENT_DATE_SECONDS = 60;

    public function __construct(
        private InternalResultService $internalResultService,
        private LotteryRelayTypeRegistry $typeRegistry,
        private HttpResultFetcher $httpFetcher,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function fetch(string $type, string $date): array
    {
        $canonicalType = strtolower(trim($type));
        $businessDate = trim($date);
        $source = $this->resolveSource($canonicalType);

        if ($source === null) {
            return $this->buildFailurePayload($canonicalType, $businessDate, [[
                'code' => 'UNSUPPORTED_TYPE',
                'message' => 'Unsupported lottery type.',
            ]]);
        }

        if ($source === 'external') {
            return $this->fetchFromUpstream($canonicalType, $businessDate);
        }

        $result = $this->internalResultService->fetch($source, ['date' => $businessDate]);
        if (($result['success'] ?? false) !== true) {
            return $this->buildFailurePayload(
                $canonicalType,
                $businessDate,
                $this->normalizeErrors($result['errors'] ?? []),
                $this->resolveNameTh($canonicalType, is_array($result['raw_result'] ?? null) ? $result['raw_result'] : [])
            );
        }

        $rawResult = is_array($result['raw_result'] ?? null) ? $result['raw_result'] : [];
        $normalizedResult = is_array($result['normalized_result'] ?? null) ? $result['normalized_result'] : [];

        return [
            'type' => $canonicalType,
            'nameTH' => $this->resolveNameTh($canonicalType, $rawResult),
            'date' => $businessDate,
            'page' => 1,
            'count' => 1,
            'results' => [[
                'id' => $this->resolveResultId($rawResult),
                'lottosName' => $this->stringOrDefault($rawResult['lottosName'] ?? null, $canonicalType),
                'lottosTH' => $this->resolveNameTh($canonicalType, $rawResult),
                'lottosDate' => $this->resolveLottosDate($businessDate, $rawResult),
                'lottosTime' => $this->resolveLottosTime($result, $rawResult),
                'lottosNumber' => $this->stringOrDefault(
                    $rawResult['lottosNumber'] ?? null,
                    $this->stringOrDefault($normalizedResult['first_prize'] ?? null, $this->stringOrDefault($normalizedResult['digit_5'] ?? null, ''))
                ),
                'lottosUnder' => $this->stringOrDefault(
                    $rawResult['lottosUnder'] ?? null,
                    $this->stringOrDefault($normalizedResult['bottom_2'] ?? null, '')
                ),
            ]],
        ];
    }

    private function resolveLookupDate(string $canonicalType, string $date): string
    {
        $marketCodes = $this->typeRegistry->marketCodesForCanonicalType($canonicalType);
        if ($marketCodes === []) {
            return $date;
        }

        $marketIds = LotteryMarket::query()->whereIn('code', $marketCodes)->pluck('id');
        $source = LottoResultSource::query()
            ->whereIn('market_id', $marketIds)
            ->where('is_active', 1)
            ->first(['lookup_date_mode', 'lookup_date_offset_days']);

        if ($source === null) {
            return $date;
        }

        $mode = (string) ($source->lookup_date_mode ?? 'ROUND_DATE');
        $offsetDays = (int) ($source->lookup_date_offset_days ?? 0);

        if ($offsetDays === 0 || ! in_array($mode, ['ROUND_DATE_MINUS_DAYS', 'ROUND_DATE_PLUS_DAYS'], true)) {
            return $date;
        }

        $base = CarbonImmutable::createFromFormat('Y-m-d', $date);
        if ($base === false) {
            return $date;
        }

        return $mode === 'ROUND_DATE_MINUS_DAYS'
            ? $base->subDays($offsetDays)->format('Y-m-d')
            : $base->addDays($offsetDays)->format('Y-m-d');
    }

    /**
     * Map a canonical relay type to the upstream (expalert) slug.
     * e.g. 'dji' → 'downjone-stock', 'dowjones-vip' → 'downjone-vip'
     */
    private function resolveUpstreamType(string $canonicalType): string
    {
        $marketCodes = $this->typeRegistry->marketCodesForCanonicalType($canonicalType);

        return $marketCodes !== [] ? $marketCodes[0] : $canonicalType;
    }

    private function resolveSource(string $canonicalType): ?string
    {
        if (in_array($canonicalType, ['dowjones-midnight', 'dowjones-extra'], true)) {
            return $canonicalType;
        }

        if ($this->typeRegistry->marketCodesForCanonicalType($canonicalType) !== []) {
            return 'external';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchFromUpstream(string $canonicalType, string $date): array
    {
        $upstreamUrl = (string) config('lottery_result_relay.upstream_get_lottery_url', '');

        if ($upstreamUrl === '') {
            return $this->buildFailurePayload($canonicalType, $date, [[
                'code' => 'UPSTREAM_NOT_CONFIGURED',
                'message' => 'Upstream get_lottery URL is not configured.',
            ]]);
        }

        $cacheKey = 'central_lottery_result:'.$canonicalType.':'.$date;
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        // Map canonical relay type to upstream (expalert) slug.
        // e.g. 'dji' → 'downjone-stock'
        $upstreamType = $this->resolveUpstreamType($canonicalType);
        $lookupDate = $this->resolveLookupDate($canonicalType, $date);
        $fetch = $this->httpFetcher->get($upstreamUrl, ['type' => $upstreamType, 'date' => $lookupDate], 15);

        if (! ($fetch['ok'] ?? false)) {
            return $this->buildFailurePayload($canonicalType, $date, [[
                'code' => (string) ($fetch['error_code'] ?? 'FETCH_FAILED'),
                'message' => (string) ($fetch['error_message'] ?? 'Failed to fetch from upstream.'),
            ]]);
        }

        $decoded = json_decode((string) ($fetch['response_body'] ?? ''), true);

        if (! is_array($decoded)) {
            return $this->buildFailurePayload($canonicalType, $date, [[
                'code' => 'INVALID_RESPONSE',
                'message' => 'Upstream returned invalid JSON.',
            ]]);
        }

        // Validate that upstream result date matches the requested date.
        // If upstream returns a different date (e.g. previous day when today's
        // result is not ready yet), reject instead of silently using wrong data.
        $upstreamResults = is_array($decoded['results'] ?? null) ? $decoded['results'] : [];
        if ($upstreamResults !== []) {
            $firstResult = (array) ($upstreamResults[0] ?? []);
            $upstreamDate = trim((string) ($firstResult['lottosDate'] ?? ''));
            if ($upstreamDate !== '') {
                try {
                    $upstreamDateParsed = CarbonImmutable::parse($upstreamDate)->timezone('Asia/Bangkok');
                } catch (\Throwable) {
                    $upstreamDateParsed = false;
                }
                if ($upstreamDateParsed !== false && $upstreamDateParsed->format('Y-m-d') !== $date) {
                    return $this->buildFailurePayload($canonicalType, $date, [[
                        'code' => 'NOT_READY',
                        'message' => 'Upstream result date '.$upstreamDateParsed->format('Y-m-d').' does not match requested date '.$date,
                    ]]);
                }
            }
        }

        // Override date with the business date the caller requested.
        // Upstream may return an offset date (e.g. actual market date),
        // but clone selection always compares against the draw's business date.
        $decoded['date'] = $date;

        Cache::put($cacheKey, $decoded, $this->resolveCacheTtl($date));

        return $decoded;
    }

    private function resolveCacheTtl(string $date): int
    {
        $parsed = CarbonImmutable::createFromFormat('Y-m-d', $date, 'Asia/Bangkok');
        if ($parsed === false) {
            return self::CACHE_TTL_CURRENT_DATE_SECONDS;
        }

        return $parsed->toDateString() < CarbonImmutable::now('Asia/Bangkok')->toDateString()
            ? self::CACHE_TTL_PAST_DATE_SECONDS
            : self::CACHE_TTL_CURRENT_DATE_SECONDS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeErrors(mixed $errors): array
    {
        if (! is_array($errors)) {
            return [[
                'code' => 'FETCH_FAILED',
                'message' => 'Failed to fetch lottery result.',
            ]];
        }

        $normalized = [];
        foreach ($errors as $error) {
            if (! is_array($error)) {
                continue;
            }

            $normalized[] = [
                'code' => (string) ($error['code'] ?? 'FETCH_FAILED'),
                'message' => (string) ($error['message'] ?? 'Failed to fetch lottery result.'),
            ];
        }

        if ($normalized === []) {
            return [[
                'code' => 'FETCH_FAILED',
                'message' => 'Failed to fetch lottery result.',
            ]];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $errors
     * @return array<string, mixed>
     */
    private function buildFailurePayload(string $canonicalType, string $businessDate, array $errors, string $nameTh = ''): array
    {
        return [
            'type' => $canonicalType,
            'nameTH' => $nameTh,
            'date' => $businessDate,
            'page' => 1,
            'count' => 0,
            'results' => [],
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $rawResult
     */
    private function resolveNameTh(string $canonicalType, array $rawResult): string
    {
        $name = $this->stringOrDefault($rawResult['lottosTH'] ?? null, '');
        if ($name !== '') {
            return $name;
        }

        return match ($canonicalType) {
            'dowjones-midnight' => 'ดาวโจนส์เที่ยงคืน',
            'dowjones-extra' => 'ดาวโจนส์ Extra',
            default => $canonicalType,
        };
    }

    /**
     * @param  array<string, mixed>  $rawResult
     */
    private function resolveLottosDate(string $businessDate, array $rawResult): string
    {
        $candidate = $this->stringOrDefault($rawResult['lottosDate'] ?? null, '');
        if ($candidate !== '') {
            return $candidate;
        }

        return CarbonImmutable::createFromFormat('Y-m-d H:i:s', $businessDate.' 00:00:00', 'Asia/Bangkok')
            ->utc()
            ->format('Y-m-d\TH:i:s.000\Z');
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $rawResult
     */
    private function resolveLottosTime(array $result, array $rawResult): ?string
    {
        $time = $this->stringOrDefault($rawResult['lottosTime'] ?? null, '');
        if ($time !== '') {
            return $time;
        }

        $meta = is_array($result['meta'] ?? null) ? $result['meta'] : [];
        $supplemental = is_array($meta['dowjones_supplemental'] ?? null) ? $meta['dowjones_supplemental'] : [];

        $startSpin = $this->stringOrDefault($supplemental['start_spin'] ?? null, '');

        return $startSpin !== '' ? $startSpin : null;
    }

    /**
     * @param  array<string, mixed>  $rawResult
     */
    private function resolveResultId(array $rawResult): int
    {
        $id = $rawResult['id'] ?? null;

        return is_numeric($id) ? (int) $id : 0;
    }

    private function stringOrDefault(mixed $value, string $default): string
    {
        if ($value === null) {
            return $default;
        }

        if (is_scalar($value)) {
            $resolved = trim((string) $value);

            return $resolved !== '' ? $resolved : $default;
        }

        return $default;
    }
}
